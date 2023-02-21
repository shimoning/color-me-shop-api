<?php

namespace Shimoning\ColorMeShopApi\Constants;

enum DeliveryMethodType: string
{
    case OTHER          = 'other';  // そのほか
    case YAMATO         = 'yamato'; // クロネコヤマト
    case YAMATO_PICKUP  = 'yamato_pickup'; // ヤマト自宅外受け取り
    case SAGAWA         = 'sagawa';  // 佐川急便
    case JP             = 'jp';  // 日本郵便

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
