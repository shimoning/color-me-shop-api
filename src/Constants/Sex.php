<?php

namespace Shimoning\ColorMeShopApi\Constants;

/**
 * 顧客の性別。
 *
 * OpenAPI (2026-09-17 取得) の customer.sex、sale.customer.sex、
 * GET /v1/customers の検索条件 sex に準拠。
 * GET /v1/customers の sex の説明では、`not_applicable` は「未回答」。
 * UNKNOWN は API 仕様外の応答値を示す番兵。元値は Entity::getRaw() で確認できる。
 * Entity::toArrayRecursive() では元値でなく番兵の __unknown__ になる。
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
enum Sex: string implements FallbackEnum
{
    case MALE           = 'male'; // 男性
    case FEMALE         = 'female'; // 女性
    case NOT_APPLICABLE = 'not_applicable'; // 未回答
    case UNKNOWN = '__unknown__'; // API 仕様外の応答値

    public static function fallbackCase(): static
    {
        return self::UNKNOWN;
    }

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
            self::NOT_APPLICABLE => '未回答',
            self::UNKNOWN        => '不明',
        };
    }
}
