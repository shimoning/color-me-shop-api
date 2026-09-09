<?php

namespace Shimoning\ColorMeShopApi\Constants;

/**
 * 商品価格の消費税区分。
 */
enum TaxType: string
{
    case EXCLUDED   = 'excluded'; // 外税
    case INCLUDED   = 'included'; // 内税

    /**
     * 消費税区分の日本語名を取得する。
     *
     * @return string
     */
    public function name(): string
    {
        return match ($this) {
            self::EXCLUDED  => '外税',
            self::INCLUDED  => '内税',
        };
    }
}
