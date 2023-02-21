<?php

namespace Shimoning\ColorMeShopApi\Constants;

enum DeliveryChargeType: string
{
    case FIXED      = 'fixed'; // 固定額
    case BY_PRICE   = 'by_price'; // 注文金額によって決定
    case BY_AREA    = 'by_area'; // 配送先都道府県によって決定
    case BY_WEIGHT  = 'by_weight'; // 商品重量によって決定

    public function name(): string
    {
        return match ($this) {
            self::FIXED     => '全国一律',
            self::BY_PRICE  => '注文金額にて設定',
            self::BY_AREA   => '送付先にて設定',
            self::BY_WEIGHT => '商品重量を基準に設定',
        };
    }
}
