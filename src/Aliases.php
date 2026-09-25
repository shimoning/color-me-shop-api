<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi;

/**
 * 0.14.0 で改名した要求側入力 Entity の、旧クラス名から新クラス名への対応表。
 *
 * 旧名は非推奨であり、次のメジャーな変更で削除する。新しいコードでは新名を使うこと。
 * 旧名を先に参照した場合は遅延 autoloader で解決し、新名を先に参照した場合は
 * クラス定義直後に別名を登録する。登録時に全対応先クラスを読み込むことはない。
 * 旧名で `unserialize()` された直列化データも、この autoloader を経由して復元できる。
 *
 * 改名の根拠は ADR 0016 を参照。
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
            => Entities\Sales\SaleDeliveryUpdateInput::class,
        'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleUpdater'
            => Entities\Sales\SaleUpdateInput::class,
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
