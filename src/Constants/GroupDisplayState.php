<?php

namespace Shimoning\ColorMeShopApi\Constants;

/**
 * 商品グループの表示状態。
 *
 * 公式 OpenAPI との差分: 応答定義にだけある `showing_for_members` / `sale_for_members` は、実 API が
 * 書き込みを 422 で拒否する (2026-09-21)。応答の受理のためだけに含める。
 *
 * @see docs/api-product-structure.md
 * @see docs/enum-openapi-audit.md
 */
enum GroupDisplayState: string
{
    case SHOWING                = 'showing'; // 掲載
    case HIDDEN                 = 'hidden'; // 非掲載
    case MEMBER_ONLY            = 'members_only'; // 会員にのみ掲載
    case SHOWING_FOR_MEMBERS    = 'showing_for_members'; // 会員にのみ掲載 (公式 response 定義のみ。PUT では 422)
    case SALE_FOR_MEMBERS       = 'sale_for_members'; // 会員にのみ販売 (公式 response 定義のみ。PUT では 422)

    /**
     * 表示状態の日本語名を取得する。
     *
     * @return string
     */
    public function name(): string
    {
        return match ($this) {
            self::SHOWING               => '掲載状態',
            self::HIDDEN                => '非掲載状態',
            self::MEMBER_ONLY           => '会員にのみ掲載',
            self::SHOWING_FOR_MEMBERS   => '会員にのみ掲載',
            self::SALE_FOR_MEMBERS      => '会員にのみ販売',
        };
    }
}
