<?php

namespace Shimoning\ColorMeShopApi\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Aliases;

/**
 * 0.14.0 で改名した要求側入力 Entity の旧クラス名が、非推奨の別名として解決できることを固定する。
 *
 * 別名は遅延 autoloader で解決するため、旧名を参照するまで新クラスは読み込まれない。
 */
class AliasesTest extends TestCase
{
    public static function aliasProvider(): array
    {
        $cases = [];
        foreach (Aliases::MAP as $legacy => $current) {
            $cases[$legacy] = [$legacy, $current];
        }
        return $cases;
    }

    #[DataProvider('aliasProvider')]
    public function test_旧クラス名は新クラスの別名として解決できる(string $legacy, string $current): void
    {
        $this->assertTrue(\class_exists($legacy), $legacy . ' が解決できない');
        $this->assertSame(
            (new \ReflectionClass($current))->getName(),
            (new \ReflectionClass($legacy))->getName(),
            $legacy . ' が ' . $current . ' を指していない',
        );
    }

    #[DataProvider('aliasProvider')]
    public function test_別名の対応先クラスは実在する(string $legacy, string $current): void
    {
        $this->assertTrue(\class_exists($current), $current . ' が存在しない');
    }

    public function test_旧クラス名で生成したインスタンスは新クラスのインスタンスになる(): void
    {
        $legacy = 'Shimoning\\ColorMeShopApi\\Entities\\Product\\OptionValueInput';
        $input = new $legacy(['name' => 'L']);

        $this->assertInstanceOf(\Shimoning\ColorMeShopApi\Entities\Product\OptionValueCreateInput::class, $input);
        $this->assertSame(['name' => 'L'], $input->toArrayRecursive());
    }

    public function test_別名の対応表は改名した5クラスを網羅する(): void
    {
        $this->assertSame([
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\OptionInput',
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\OptionValueInput',
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\VariantInput',
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleDeliveryUpdater',
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleUpdater',
        ], \array_keys(Aliases::MAP));
    }
}
