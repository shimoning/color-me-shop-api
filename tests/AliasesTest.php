<?php

namespace Shimoning\ColorMeShopApi\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Shimoning\ColorMeShopApi\Aliases;

/**
 * 改名した Entity の旧クラス名が、非推奨の別名として解決できることを固定する。
 *
 * 他のテストによる別名の事前登録を避けるため、各テストを状態を引き継がない別プロセスで実行する。
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class AliasesTest extends TestCase
{
    #[DataProvider('aliasProvider')]
    public function test_新名だけで生成しても旧名で型判定できる(
        string $legacy,
        string $current,
        array $data,
    ): void
    {
        $this->assertFalse(\class_exists($legacy, false));
        $this->assertFalse(\class_exists($current, false));

        $input = new $current($data);

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
            $data = $current === \Shimoning\ColorMeShopApi\Entities\Product\StocksIncrementInput::class
                ? ['increment' => 0]
                : [];
            $cases[$legacy] = [$legacy, $current, $data];
        }
        return $cases;
    }

    #[DataProvider('aliasProvider')]
    public function test_旧クラス名は新クラスの別名として解決できる(
        string $legacy,
        string $current,
        array $data,
    ): void
    {
        $this->assertTrue(\class_exists($legacy), $legacy . ' が解決できない');
        $this->assertSame(
            (new \ReflectionClass($current))->getName(),
            (new \ReflectionClass($legacy))->getName(),
            $legacy . ' が ' . $current . ' を指していない',
        );
    }

    #[DataProvider('aliasProvider')]
    public function test_別名の対応先クラスは実在する(
        string $legacy,
        string $current,
        array $data,
    ): void
    {
        $this->assertTrue(\class_exists($current), $current . ' が存在しない');
    }

    #[DataProvider('aliasProvider')]
    public function test_旧クラス名で生成したインスタンスは新クラスのインスタンスになる(
        string $legacy,
        string $current,
        array $data,
    ): void
    {
        $entity = new $legacy($data);

        $this->assertInstanceOf($current, $entity);
    }

    #[DataProvider('aliasProvider')]
    public function test_旧クラス名でserializeされたデータをunserializeできる(
        string $legacy,
        string $current,
        array $data,
    ): void
    {
        $serialized = \sprintf('O:%d:"%s":0:{}', \strlen($legacy), $legacy);

        $entity = \unserialize($serialized);

        $this->assertInstanceOf($current, $entity);
        $this->assertInstanceOf($legacy, $entity);
    }

    public function test_別名の対応表はすべての改名を網羅する(): void
    {
        $this->assertSame([
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\OptionInput'
                => \Shimoning\ColorMeShopApi\Entities\Product\OptionCreateInput::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\OptionValueInput'
                => \Shimoning\ColorMeShopApi\Entities\Product\OptionValueCreateInput::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\VariantInput'
                => \Shimoning\ColorMeShopApi\Entities\Product\VariantUpdateInput::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleDeliveryUpdater'
                => \Shimoning\ColorMeShopApi\Entities\Sales\DeliveryUpdateInput::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleUpdater'
                => \Shimoning\ColorMeShopApi\Entities\Sales\SaleUpdateInput::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleApplication'
                => \Shimoning\ColorMeShopApi\Entities\Sales\Application::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleCustomization'
                => \Shimoning\ColorMeShopApi\Entities\Sales\Customization::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleDelivery'
                => \Shimoning\ColorMeShopApi\Entities\Sales\Delivery::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleDetail'
                => \Shimoning\ColorMeShopApi\Entities\Sales\Detail::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleSegment'
                => \Shimoning\ColorMeShopApi\Entities\Sales\Segment::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleShopCoupon'
                => \Shimoning\ColorMeShopApi\Entities\Sales\ShopCoupon::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleTotals'
                => \Shimoning\ColorMeShopApi\Entities\Sales\Totals::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleCustomerCreateInput'
                => \Shimoning\ColorMeShopApi\Entities\Sales\CustomerCreateInput::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleDeliveryCreateInput'
                => \Shimoning\ColorMeShopApi\Entities\Sales\DeliveryCreateInput::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleDeliveryUpdateInput'
                => \Shimoning\ColorMeShopApi\Entities\Sales\DeliveryUpdateInput::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleDetailCreateInput'
                => \Shimoning\ColorMeShopApi\Entities\Sales\DetailCreateInput::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Gift\\GiftCard'
                => \Shimoning\ColorMeShopApi\Entities\Gift\Card::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Gift\\GiftNoshi'
                => \Shimoning\ColorMeShopApi\Entities\Gift\Noshi::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Gift\\GiftType'
                => \Shimoning\ColorMeShopApi\Entities\Gift\Type::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Gift\\GiftWrapping'
                => \Shimoning\ColorMeShopApi\Entities\Gift\Wrapping::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Delivery\\DeliveryDate'
                => \Shimoning\ColorMeShopApi\Entities\Delivery\Date::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Delivery\\DeliveryDateDays'
                => \Shimoning\ColorMeShopApi\Entities\Delivery\DateDays::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Delivery\\DeliveryDateTimes'
                => \Shimoning\ColorMeShopApi\Entities\Delivery\DateTimes::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\ProductStocksIncrementInput'
                => \Shimoning\ColorMeShopApi\Entities\Product\StocksIncrementInput::class,
        ], Aliases::MAP);
    }
}
