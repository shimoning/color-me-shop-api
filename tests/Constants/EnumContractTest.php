<?php

namespace Shimoning\ColorMeShopApi\Tests\Constants;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * src/Constants 配下の全 enum が満たすべき共通契約を検証する。
 *
 * 個別の enum ごとにテストを書く代わりに、ケースの追加漏れ・
 * name() の match 網羅漏れ (UnhandledMatchError)・値の重複を横断的に検出する。
 */
class EnumContractTest extends TestCase
{
    /**
     * 契約テストの対象外にする enum の短縮名。
     *
     * @var array<string>
     */
    private const EXCLUDED = [];

    public static function enumProvider(): array
    {
        $cases = [];
        foreach (\glob(__DIR__ . '/../../src/Constants/*.php') as $path) {
            $shortName = \basename($path, '.php');
            if (\in_array($shortName, self::EXCLUDED, true)) {
                continue;
            }
            $cases[$shortName] = ['Shimoning\\ColorMeShopApi\\Constants\\' . $shortName];
        }
        return $cases;
    }

    #[DataProvider('enumProvider')]
    public function test_BackedEnumとして定義されている(string $enum): void
    {
        $this->assertTrue(\enum_exists($enum), $enum . ' が enum として存在しない');
        $this->assertTrue(\is_subclass_of($enum, \BackedEnum::class), $enum . ' が BackedEnum ではない');
    }

    #[DataProvider('enumProvider')]
    public function test_ケースが1つ以上定義されている(string $enum): void
    {
        $this->assertNotEmpty($enum::cases(), $enum . ' にケースが定義されていない');
    }

    #[DataProvider('enumProvider')]
    public function test_backed_valueが重複していない(string $enum): void
    {
        $values = \array_map(fn($case) => $case->value, $enum::cases());

        $this->assertSame(
            \count($values),
            \count(\array_unique($values, \SORT_REGULAR)),
            $enum . ' に重複した値がある',
        );
    }

    #[DataProvider('enumProvider')]
    public function test_全ケースがbacked_valueから復元できる(string $enum): void
    {
        foreach ($enum::cases() as $case) {
            $this->assertSame($case, $enum::tryFrom($case->value));
            $this->assertSame($case, $enum::from($case->value));
        }
    }

    #[DataProvider('enumProvider')]
    public function test_未定義の値に対してtryFromはnullを返す(string $enum): void
    {
        $isInt = \is_int($enum::cases()[0]->value);

        $this->assertNull($enum::tryFrom($isInt ? \PHP_INT_MAX : '__undefined__'));
    }

    /**
     * name() は match で実装されているため、ケースを追加して match を更新し忘れると
     * UnhandledMatchError になる。全ケースを呼び出して網羅を保証する。
     */
    #[DataProvider('enumProvider')]
    public function test_nameメソッドは全ケースで非空の文字列を返す(string $enum): void
    {
        if (! \method_exists($enum, 'name')) {
            $this->assertTrue(true, $enum . ' は name() を持たない');
            return;
        }

        foreach ($enum::cases() as $case) {
            $this->assertNotSame('', $case->name(), $enum . '::' . $case->name . ' の name() が空文字');
        }
    }
}
