<?php

namespace Shimoning\ColorMeShopApi\Constants;

enum DeliveryChargeType: string
{
    case FIXED      = 'fixed'; // 固定額
    case BY_PRICE   = 'by_price'; // 注文金額によって決定
    case BY_AREA    = 'by_area'; // 配送先都道府県によって決定
    case BY_WEIGHT  = 'by_weight'; // 商品重量によって決定
}
