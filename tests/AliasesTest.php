<?php

namespace Shimoning\ColorMeShopApi\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Shimoning\ColorMeShopApi\Aliases;

/**
 * 改名したクラスの旧名が、非推奨の別名として解決できることを固定する。
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

        if ((new \ReflectionClass($current))->isAbstract()) {
            $this->assertTrue(\class_exists($legacy));
            $this->assertSame($current, (new \ReflectionClass($legacy))->getName());
            return;
        }

        $input = self::newInstance($current, $data);

        // instanceof 自体は旧名を autoload しないため、事前に class_exists($legacy) を呼ばない。
        $this->assertTrue($input instanceof $legacy);
    }

    #[DataProvider('aliasProvider')]
    public function test_新名で生成したインスタンスを旧名の型宣言へ渡せる(
        string $legacy,
        string $current,
        array $data,
    ): void {
        if ((new \ReflectionClass($current))->isAbstract()) {
            $this->assertTrue(\class_exists($legacy));
            return;
        }

        $instance = self::newInstance($current, $data);
        /** @var callable(object): object $acceptLegacy */
        $acceptLegacy = eval('return static function (\\' . $legacy . ' $value): object { return $value; };');

        $this->assertSame($instance, $acceptLegacy($instance));
    }

    #[DataProvider('removedAliasProvider')]
    public function test_0_14_0で導入した旧名のうち再利用しない名前は解決しない(string $removed): void
    {
        $this->assertFalse(\class_exists($removed));
    }

    /** @return array<string, array{string}> */
    public static function removedAliasProvider(): array
    {
        return [
            'Product\\OptionInput' => ['Shimoning\\ColorMeShopApi\\Entities\\Product\\OptionInput'],
            'Product\\OptionValueInput' => ['Shimoning\\ColorMeShopApi\\Entities\\Product\\OptionValueInput'],
            'Sales\\SaleUpdater' => ['Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleUpdater'],
            'Sales\\SaleDeliveryUpdater' => [
                'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleDeliveryUpdater',
            ],
        ];
    }

    public function test_旧Product_VariantInput_aliasはvariant更新入力として解決しない(): void
    {
        $input = new \Shimoning\ColorMeShopApi\Entities\Product\VariantInput(['stocks' => 0]);

        $this->assertNotInstanceOf(
            \Shimoning\ColorMeShopApi\Entities\Product\Variant\VariantUpdateInput::class,
            $input,
        );
    }

    public function test_RequestEntityの旧名はinterface_existsで新名の別名として解決できる(): void
    {
        $legacy = 'Shimoning\\ColorMeShopApi\\Contracts\\RequestEntity';
        $current = \Shimoning\ColorMeShopApi\Entities\RequestEntity::class;

        $this->assertFalse(\interface_exists($legacy, false));
        $this->assertFalse(\interface_exists($current, false));
        $this->assertTrue(\interface_exists($legacy));
        $this->assertSame($current, (new \ReflectionClass($legacy))->getName());
    }

    public function test_RequestEntityは新名の読み込み時に旧名も定義する(): void
    {
        $legacy = 'Shimoning\\ColorMeShopApi\\Contracts\\RequestEntity';
        $current = \Shimoning\ColorMeShopApi\Entities\RequestEntity::class;

        $this->assertFalse(\interface_exists($legacy, false));
        $this->assertFalse(\interface_exists($current, false));
        $this->assertTrue(\interface_exists($current));
        $this->assertTrue(\interface_exists($legacy, false));
        $this->assertSame($current, (new \ReflectionClass($legacy))->getName());

        $input = new \Shimoning\ColorMeShopApi\Entities\Customer\CustomerUpdateInput([]);
        /** @var callable(object): object $acceptLegacy */
        $acceptLegacy = eval('return static function (\\' . $legacy . ' $value): object { return $value; };');

        $this->assertSame($input, $acceptLegacy($input));
    }

    public static function aliasProvider(): array
    {
        $cases = [];
        foreach (Aliases::MAP as $legacy => $current) {
            if (
                $current === \Shimoning\ColorMeShopApi\Entities\RequestEntity::class
                || $current === \Shimoning\ColorMeShopApi\Services\OAuth\Options::class
            ) {
                continue;
            }
            $data = match ($current) {
                \Shimoning\ColorMeShopApi\Entities\Product\StocksIncrementInput::class => ['increment' => 0],
                \Shimoning\ColorMeShopApi\Entities\Product\VariantInput::class => ['stocks' => 0],
                default => [],
            };
            $cases[$legacy] = [$legacy, $current, $data];
        }
        return $cases;
    }

    /** @return array<string, array{class-string, class-string}> */
    public static function sameNameCollisionProvider(): array
    {
        return [
            'Advertising' => [
                'Shimoning\\ColorMeShopApi\\Entities\\Product\\Advertising',
                \Shimoning\ColorMeShopApi\Entities\Product\Advertising\Advertising::class,
            ],
            'Group' => [
                'Shimoning\\ColorMeShopApi\\Entities\\Product\\Group',
                \Shimoning\ColorMeShopApi\Entities\Product\Group\Group::class,
            ],
            'Category' => [
                'Shimoning\\ColorMeShopApi\\Entities\\Product\\Category',
                \Shimoning\ColorMeShopApi\Entities\Product\Category\Category::class,
            ],
            'Stock' => [
                'Shimoning\\ColorMeShopApi\\Entities\\Stock\\Stock',
                \Shimoning\ColorMeShopApi\Entities\Product\Stock\Stock::class,
            ],
        ];
    }

    #[DataProvider('sameNameCollisionProvider')]
    public function test_旧クラス名と新namespace名が同じでも遅延解決できる(
        string $legacy,
        string $current,
    ): void
    {
        $this->assertFalse(\class_exists($legacy, false));
        $this->assertFalse(\class_exists($current, false));
        $this->assertTrue(\class_exists($legacy));
        $this->assertSame($current, (new \ReflectionClass($legacy))->getName());
    }

    #[DataProvider('sameNameCollisionProvider')]
    public function test_旧クラス名と新namespace名が同じでも新名から別名を定義して復元できる(
        string $legacy,
        string $current,
    ): void
    {
        $reflection = new \ReflectionClass($current);
        Aliases::defineLegacyAlias($current);

        $this->assertTrue(\class_exists($legacy, false));
        $this->assertSame($current, (new \ReflectionClass($legacy))->getName());

        // 抽象クラスは unserialize でも生成できないため、別名の実体確認までとする。
        if ($reflection->isAbstract()) {
            return;
        }

        $serialized = \sprintf('O:%d:"%s":0:{}', \strlen($legacy), $legacy);
        $this->assertInstanceOf($current, \unserialize($serialized));
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
        if ((new \ReflectionClass($current))->isAbstract()) {
            $this->assertTrue(\class_exists($legacy));
            $this->assertSame($current, (new \ReflectionClass($legacy))->getName());
            return;
        }

        $entity = self::newInstance($legacy, $data);

        $this->assertInstanceOf($current, $entity);
    }

    #[DataProvider('aliasProvider')]
    public function test_旧クラス名でserializeされたデータをunserializeできる(
        string $legacy,
        string $current,
        array $data,
    ): void
    {
        // Service は HTTP クライアントなどの実行時依存を持つため、Entity 向けの直列化互換性の対象外。
        if (\is_a($current, \Shimoning\ColorMeShopApi\Services\Service::class, true)) {
            $this->assertTrue(\class_exists($legacy));
            $this->assertSame($current, (new \ReflectionClass($legacy))->getName());
            return;
        }

        if ((new \ReflectionClass($current))->isAbstract()) {
            $this->assertTrue(\class_exists($legacy));
            $this->assertSame($current, (new \ReflectionClass($legacy))->getName());
            return;
        }

        $serialized = \sprintf('O:%d:"%s":0:{}', \strlen($legacy), $legacy);

        $entity = \unserialize($serialized);

        $this->assertInstanceOf($current, $entity);
        $this->assertInstanceOf($legacy, $entity);
    }

    public function test_別名の対応表はすべての改名を網羅する(): void
    {
        $this->assertSame([
            'Shimoning\\ColorMeShopApi\\Contracts\\RequestEntity'
                => \Shimoning\ColorMeShopApi\Entities\RequestEntity::class,
            'Shimoning\\ColorMeShopApi\\Services\\Sales'
                => \Shimoning\ColorMeShopApi\Services\Sale::class,
            'Shimoning\\ColorMeShopApi\\Entities\\OAuth\\Options'
                => \Shimoning\ColorMeShopApi\Services\OAuth\Options::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\ProductVariantInput'
                => \Shimoning\ColorMeShopApi\Entities\Product\VariantInput::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\Sale'
                => \Shimoning\ColorMeShopApi\Entities\Sale\Sale::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleCreateInput'
                => \Shimoning\ColorMeShopApi\Entities\Sale\SaleCreateInput::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleUpdateInput'
                => \Shimoning\ColorMeShopApi\Entities\Sale\SaleUpdateInput::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SearchParameters'
                => \Shimoning\ColorMeShopApi\Entities\Sale\SearchParameters::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleApplication'
                => \Shimoning\ColorMeShopApi\Entities\Sale\Application::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleCustomization'
                => \Shimoning\ColorMeShopApi\Entities\Sale\Customization::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleDelivery'
                => \Shimoning\ColorMeShopApi\Entities\Sale\Delivery::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleDetail'
                => \Shimoning\ColorMeShopApi\Entities\Sale\Detail::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleSegment'
                => \Shimoning\ColorMeShopApi\Entities\Sale\Segment::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleShopCoupon'
                => \Shimoning\ColorMeShopApi\Entities\Sale\ShopCoupon::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleTotals'
                => \Shimoning\ColorMeShopApi\Entities\Sale\Totals::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleCustomerCreateInput'
                => \Shimoning\ColorMeShopApi\Entities\Sale\CustomerCreateInput::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleDeliveryCreateInput'
                => \Shimoning\ColorMeShopApi\Entities\Sale\DeliveryCreateInput::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleDeliveryUpdateInput'
                => \Shimoning\ColorMeShopApi\Entities\Sale\DeliveryUpdateInput::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleDetailCreateInput'
                => \Shimoning\ColorMeShopApi\Entities\Sale\DetailCreateInput::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Sales\\Stat'
                => \Shimoning\ColorMeShopApi\Entities\Sale\Stat\Stat::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Gift\\GiftCard'
                => \Shimoning\ColorMeShopApi\Entities\Gift\Card::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Gift\\GiftNoshi'
                => \Shimoning\ColorMeShopApi\Entities\Gift\Noshi::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Gift\\GiftType'
                => \Shimoning\ColorMeShopApi\Entities\Gift\Type::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Gift\\GiftWrapping'
                => \Shimoning\ColorMeShopApi\Entities\Gift\Wrapping::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Delivery\\DeliveryDate'
                => \Shimoning\ColorMeShopApi\Entities\Delivery\Date\Date::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Delivery\\DeliveryDateDays'
                => \Shimoning\ColorMeShopApi\Entities\Delivery\Date\Days::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Delivery\\DeliveryDateTimes'
                => \Shimoning\ColorMeShopApi\Entities\Delivery\Date\Times::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Customer\\Points'
                => \Shimoning\ColorMeShopApi\Entities\Customer\Points\Points::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Customer\\CustomerPointsInput'
                => \Shimoning\ColorMeShopApi\Entities\Customer\Points\PointsInput::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Customer\\Membership'
                => \Shimoning\ColorMeShopApi\Entities\Customer\Membership\Membership::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Customer\\MembershipAggregationPeriod'
                => \Shimoning\ColorMeShopApi\Entities\Customer\Membership\AggregationPeriod::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Customer\\MembershipProgress'
                => \Shimoning\ColorMeShopApi\Entities\Customer\Membership\Progress::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Customer\\NextMembership'
                => \Shimoning\ColorMeShopApi\Entities\Customer\Membership\NextMembership::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\ProductStocksIncrementInput'
                => \Shimoning\ColorMeShopApi\Entities\Product\StocksIncrementInput::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\Variant'
                => \Shimoning\ColorMeShopApi\Entities\Product\Variant\Variant::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\VariantOption'
                => \Shimoning\ColorMeShopApi\Entities\Product\Variant\Option::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\VariantUpdateInput'
                => \Shimoning\ColorMeShopApi\Entities\Product\Variant\VariantUpdateInput::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\VariantSearchParameters'
                => \Shimoning\ColorMeShopApi\Entities\Product\Variant\SearchParameters::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\Option'
                => \Shimoning\ColorMeShopApi\Entities\Product\Option\Option::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\OptionCreateInput'
                => \Shimoning\ColorMeShopApi\Entities\Product\Option\OptionCreateInput::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\OptionValue'
                => \Shimoning\ColorMeShopApi\Entities\Product\Option\Value\Value::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\OptionValueCreateInput'
                => \Shimoning\ColorMeShopApi\Entities\Product\Option\Value\ValueCreateInput::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\Pickup'
                => \Shimoning\ColorMeShopApi\Entities\Product\Pickup\Pickup::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\PickupInput'
                => \Shimoning\ColorMeShopApi\Entities\Product\Pickup\PickupInput::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\ProductImage'
                => \Shimoning\ColorMeShopApi\Entities\Product\Image\Image::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\Advertising'
                => \Shimoning\ColorMeShopApi\Entities\Product\Advertising\Advertising::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\AdvertisingSearchParameters'
                => \Shimoning\ColorMeShopApi\Entities\Product\Advertising\SearchParameters::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\Group'
                => \Shimoning\ColorMeShopApi\Entities\Product\Group\Group::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\GroupInput'
                => \Shimoning\ColorMeShopApi\Entities\Product\Group\GroupInput::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\Category'
                => \Shimoning\ColorMeShopApi\Entities\Product\Category\Category::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\BigCategory'
                => \Shimoning\ColorMeShopApi\Entities\Product\Category\BigCategory::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\CategoryInput'
                => \Shimoning\ColorMeShopApi\Entities\Product\Category\CategoryInput::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\SmallCategory'
                => \Shimoning\ColorMeShopApi\Entities\Product\Category\SmallCategory::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\CategoryChildInput'
                => \Shimoning\ColorMeShopApi\Entities\Product\Category\ChildInput::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\MetaTag'
                => \Shimoning\ColorMeShopApi\Entities\Common\MetaTag::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Product\\MetaTagInput'
                => \Shimoning\ColorMeShopApi\Entities\Common\MetaTagInput::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Stock\\Stock'
                => \Shimoning\ColorMeShopApi\Entities\Product\Stock\Stock::class,
            'Shimoning\\ColorMeShopApi\\Entities\\Stock\\SearchParameters'
                => \Shimoning\ColorMeShopApi\Entities\Product\Stock\SearchParameters::class,
        ], Aliases::MAP);
    }

    /**
     * @param class-string $class
     * @param array<string, mixed> $data
     */
    private static function newInstance(string $class, array $data): object
    {
        if (\is_a($class, \Shimoning\ColorMeShopApi\Services\Service::class, true)) {
            return new $class('dummy-token');
        }

        return new $class($data);
    }
}
