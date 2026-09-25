<?php

namespace Shimoning\ColorMeShopApi\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Shimoning\ColorMeShopApi\Aliases;

/**
 * 0.14.0 で改名した要求側入力 Entity の旧クラス名が、非推奨の別名として解決できることを固定する。
 *
 * 他のテストによる別名の事前登録を避けるため、各テストを状態を引き継がない別プロセスで実行する。
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class AliasesTest extends TestCase
{
    #[DataProvider('aliasProvider')]
    public function test_新名だけで生成しても旧名で型判定できる(string $legacy, string $current): void
    {
        $this->assertFalse(\class_exists($legacy, false));
        $this->assertFalse(\class_exists($current, false));

        $input = new $current([]);

        // instanceof 自体は旧名を autoload しないため、事前に class_exists($legacy) を呼ばない。
        $this->assertTrue($input instanceof $legacy);
    }

    public function test_旧名の受注更新から生成した配送先を旧名で型判定して渡せる(): void
    {
        $this->assertFalse(\class_exists(\Shimoning\ColorMeShopApi\Entities\Sales\SaleDeliveryUpdater::class, false));
        $sale = new \Shimoning\ColorMeShopApi\Entities\Sales\SaleUpdater([
            'sale_deliveries' => [['id' => 1]],
        ]);
        $delivery = $sale->getSaleDeliveries()[0];

        $this->assertTrue($delivery instanceof \Shimoning\ColorMeShopApi\Entities\Sales\SaleDeliveryUpdater);
        $acceptLegacy = static fn(\Shimoning\ColorMeShopApi\Entities\Sales\SaleDeliveryUpdater $value): object => $value;
        $this->assertSame($delivery, $acceptLegacy($delivery));
    }

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
