<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi;

/**
 * 非推奨の Entity 名から現行名への対応表。
 *
 * 新しいコードでは現行名を使うこと。旧名で直列化されたデータも復元できる。
 *
 * @see docs/adr/0016-unify-request-input-entity-names.md
 */
final class Aliases
{
    /**
     * 旧クラス名 => 新クラス名。
     *
     * @var array<class-string, class-string>
     */
    public const MAP = [
        'Shimoning\\ColorMeShopApi\\Entities\\Product\\OptionInput'
            => Entities\Product\OptionCreateInput::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Product\\OptionValueInput'
            => Entities\Product\OptionValueCreateInput::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Product\\VariantInput'
            => Entities\Product\VariantUpdateInput::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleDeliveryUpdater'
            => Entities\Sales\DeliveryUpdateInput::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleUpdater'
            => Entities\Sales\SaleUpdateInput::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleApplication'
            => Entities\Sales\Application::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleCustomization'
            => Entities\Sales\Customization::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleDelivery'
            => Entities\Sales\Delivery::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleDetail'
            => Entities\Sales\Detail::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleSegment'
            => Entities\Sales\Segment::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleShopCoupon'
            => Entities\Sales\ShopCoupon::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleTotals'
            => Entities\Sales\Totals::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleCustomerCreateInput'
            => Entities\Sales\CustomerCreateInput::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleDeliveryCreateInput'
            => Entities\Sales\DeliveryCreateInput::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleDeliveryUpdateInput'
            => Entities\Sales\DeliveryUpdateInput::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleDetailCreateInput'
            => Entities\Sales\DetailCreateInput::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Gift\\GiftCard'
            => Entities\Gift\Card::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Gift\\GiftNoshi'
            => Entities\Gift\Noshi::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Gift\\GiftType'
            => Entities\Gift\Type::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Gift\\GiftWrapping'
            => Entities\Gift\Wrapping::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Delivery\\DeliveryDate'
            => Entities\Delivery\Date::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Delivery\\DeliveryDateDays'
            => Entities\Delivery\DateDays::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Delivery\\DeliveryDateTimes'
            => Entities\Delivery\DateTimes::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Product\\ProductStocksIncrementInput'
            => Entities\Product\StocksIncrementInput::class,
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
            if ($target === $current && ! \class_exists($legacy, false)) {
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
            if (\class_exists($current)) {
                self::defineLegacyAlias($current);
            }
        });
    }
}
