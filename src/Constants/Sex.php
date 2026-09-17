<?php

namespace Shimoning\ColorMeShopApi\Constants;

/**
 * 顧客の性別。
 *
 * OpenAPI (2026-09-17 取得) の customer.sex、sale.customer.sex、
 * GET /v1/customers の検索条件 sex に準拠。
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
enum Sex: string
{
    case MALE           = 'male'; // 男性
    case FEMALE         = 'female'; // 女性
    case NOT_APPLICABLE = 'not_applicable'; // 該当なし

    /**
     * 性別の日本語名を取得する。
     *
     * @return string
     */
    public function name(): string
    {
        return match ($this) {
            self::MALE           => '男性',
            self::FEMALE         => '女性',
            self::NOT_APPLICABLE => '該当なし',
        };
    }
}
