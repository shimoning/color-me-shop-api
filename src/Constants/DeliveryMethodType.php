<?php

namespace Shimoning\ColorMeShopApi\Constants;

/**
 * 配送業者の種別。
 */
enum DeliveryMethodType: string
{
    case OTHER          = 'other';  // そのほか
    case YAMATO         = 'yamato'; // クロネコヤマト
    case YAMATO_PICKUP  = 'yamato_pickup'; // ヤマト自宅外受け取り
    case SAGAWA         = 'sagawa';  // 佐川急便
    case JP             = 'jp';  // 日本郵便

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
        };
    }
}
