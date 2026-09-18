<?php

namespace Shimoning\ColorMeShopApi\Constants;

/**
 * 配送業者の種別。
 * UNKNOWN は API 仕様外の応答値を示す番兵。元値は Entity::getRaw() で確認できる。
 * Entity::toArrayRecursive() では元値でなく番兵の __unknown__ になる。
 */
enum DeliveryMethodType: string implements FallbackEnum
{
    case OTHER          = 'other';  // そのほか
    case YAMATO         = 'yamato'; // クロネコヤマト
    case YAMATO_PICKUP  = 'yamato_pickup'; // ヤマト自宅外受け取り
    case SAGAWA         = 'sagawa';  // 佐川急便
    case JP             = 'jp';  // 日本郵便
    case UNKNOWN        = '__unknown__'; // API 仕様外の応答値

    public static function fallbackCase(): static
    {
        return self::UNKNOWN;
    }

    /**
     * 配送業者の日本語名を取得する。
     *
     * @return string
     */
    public function name(): string
    {
        return match ($this) {
            self::OTHER         => 'その他',
            self::YAMATO        => 'クロネコヤマト',
            self::YAMATO_PICKUP => 'クロネコヤマト (自宅外受け取り)',
            self::SAGAWA        => '佐川急便',
            self::JP            => '日本郵便',
            self::UNKNOWN       => '不明',
        };
    }
}
