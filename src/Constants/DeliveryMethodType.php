<?php

namespace Shimoning\ColorMeShopApi\Constants;

enum DeliveryMethodType: string
{
    case OTHER          = 'other';  // そのほか
    case YAMATO         = 'yamato'; // クロネコヤマト
    case YAMATO_PICKUP  = 'yamato_pickup'; // ヤマト自宅外受け取り
    case SAGAWA         = 'sagawa';  // 佐川急便
    case JP             = 'jp';  // 日本郵便
}
