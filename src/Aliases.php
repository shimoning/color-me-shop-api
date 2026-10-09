<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi;

/**
 * 非推奨のクラス名から現行名への対応表。
 *
 * 新しいコードでは現行名を使うこと。旧名で直列化された Entity のデータも復元できる。
 * Service は直列化を想定しないため、旧名での復元は対象外である。
 *
 * @see docs/class-aliases.md
 * @see docs/adr/0016-unify-request-input-entity-names.md
 * @see docs/adr/0031-rename-sales-service-to-sale.md
 */
final class Aliases
{
    /**
     * 旧クラス名 => 新クラス名。
     *
     * @var array<class-string, class-string>
     */
    public const MAP = [
        'Shimoning\\ColorMeShopApi\\Contracts\\RequestEntity'
            => Entities\RequestEntity::class,
        'Shimoning\\ColorMeShopApi\\Services\\Sales'
            => Services\Sale::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Product\\ProductVariantInput'
            => Entities\Product\VariantInput::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Sales\\Sale'
            => Entities\Sale\Sale::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleCreateInput'
            => Entities\Sale\SaleCreateInput::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleUpdateInput'
            => Entities\Sale\SaleUpdateInput::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SearchParameters'
            => Entities\Sale\SearchParameters::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleApplication'
            => Entities\Sale\Application::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleCustomization'
            => Entities\Sale\Customization::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleDelivery'
            => Entities\Sale\Delivery::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleDetail'
            => Entities\Sale\Detail::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleSegment'
            => Entities\Sale\Segment::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleShopCoupon'
            => Entities\Sale\ShopCoupon::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleTotals'
            => Entities\Sale\Totals::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleCustomerCreateInput'
            => Entities\Sale\CustomerCreateInput::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleDeliveryCreateInput'
            => Entities\Sale\DeliveryCreateInput::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleDeliveryUpdateInput'
            => Entities\Sale\DeliveryUpdateInput::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleDetailCreateInput'
            => Entities\Sale\DetailCreateInput::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Sales\\Stat'
            => Entities\Sale\Stat\Stat::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Gift\\GiftCard'
            => Entities\Gift\Card::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Gift\\GiftNoshi'
            => Entities\Gift\Noshi::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Gift\\GiftType'
            => Entities\Gift\Type::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Gift\\GiftWrapping'
            => Entities\Gift\Wrapping::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Delivery\\DeliveryDate'
            => Entities\Delivery\Date\Date::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Delivery\\DeliveryDateDays'
            => Entities\Delivery\Date\Days::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Delivery\\DeliveryDateTimes'
            => Entities\Delivery\Date\Times::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Customer\\Points'
            => Entities\Customer\Points\Points::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Customer\\CustomerPointsInput'
            => Entities\Customer\Points\PointsInput::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Customer\\Membership'
            => Entities\Customer\Membership\Membership::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Customer\\MembershipAggregationPeriod'
            => Entities\Customer\Membership\AggregationPeriod::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Customer\\MembershipProgress'
            => Entities\Customer\Membership\Progress::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Customer\\NextMembership'
            => Entities\Customer\Membership\NextMembership::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Product\\ProductStocksIncrementInput'
            => Entities\Product\StocksIncrementInput::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Product\\Variant'
            => Entities\Product\Variant\Variant::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Product\\VariantOption'
            => Entities\Product\Variant\Option::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Product\\VariantUpdateInput'
            => Entities\Product\Variant\VariantUpdateInput::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Product\\VariantSearchParameters'
            => Entities\Product\Variant\SearchParameters::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Product\\Option'
            => Entities\Product\Option\Option::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Product\\OptionCreateInput'
            => Entities\Product\Option\OptionCreateInput::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Product\\OptionValue'
            => Entities\Product\Option\Value\Value::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Product\\OptionValueCreateInput'
            => Entities\Product\Option\Value\ValueCreateInput::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Product\\Pickup'
            => Entities\Product\Pickup\Pickup::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Product\\PickupInput'
            => Entities\Product\Pickup\PickupInput::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Product\\ProductImage'
            => Entities\Product\Image\Image::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Product\\Advertising'
            => Entities\Product\Advertising\Advertising::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Product\\AdvertisingSearchParameters'
            => Entities\Product\Advertising\SearchParameters::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Product\\Group'
            => Entities\Product\Group\Group::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Product\\GroupInput'
            => Entities\Product\Group\GroupInput::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Product\\Category'
            => Entities\Product\Category\Category::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Product\\BigCategory'
            => Entities\Product\Category\BigCategory::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Product\\CategoryInput'
            => Entities\Product\Category\CategoryInput::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Product\\SmallCategory'
            => Entities\Product\Category\SmallCategory::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Product\\CategoryChildInput'
            => Entities\Product\Category\ChildInput::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Product\\MetaTag'
            => Entities\Common\MetaTag::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Product\\MetaTagInput'
            => Entities\Common\MetaTagInput::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Stock\\Stock'
            => Entities\Product\Stock\Stock::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Stock\\SearchParameters'
            => Entities\Product\Stock\SearchParameters::class,
    ];

    private static bool $registered = false;

    /**
     * 読み込み済みの新クラスに対して旧名を定義する。
     *
     * @param class-string $current
     */
    public static function defineLegacyAlias(string $current): void
    {
        foreach (self::MAP as $legacy => $target) {
            if (
                $target === $current
                && ! \class_exists($legacy, false)
                && ! \interface_exists($legacy, false)
            ) {
                \class_alias($current, $legacy, false);
            }
        }
    }

    /**
     * 旧クラス名を解決する遅延 autoloader を登録する。
     *
     * composer の `autoload.files` から1度だけ呼ばれる。多重登録は行わない。
     */
    public static function register(): void
    {
        if (self::$registered) {
            return;
        }
        self::$registered = true;

        \spl_autoload_register(static function (string $class): void {
            $current = self::MAP[$class] ?? null;
            if ($current === null) {
                return;
            }

            // 新クラスの読み込み時にも別名が登録されるため、読み込み後に二重定義を避ける。
            if (\class_exists($current) || \interface_exists($current)) {
                self::defineLegacyAlias($current);
            }
        });
    }
}
