<?php

namespace Shimoning\ColorMeShopApi\Constants;

/**
 * 商品の表示および販売状態。
 *
 * 公式 OpenAPI の productGroup response と本 enum は `showing` / `hidden` /
 * `showing_for_members` / `sale_for_members` だが、グループ作成・更新 request は
 * `showing` / `hidden` / `members_only` と記載される。実 API の商品入力でも本 enum の4値が
 * 受理され、`members_only` は 422 になるため、OpenAPI の request 定義の誤りと判断する。
 */
enum ProductDisplayState: string
{
    case SHOWING                = 'showing'; // 掲載
    case HIDDEN                 = 'hidden'; // 非掲載
    case SHOWING_FOR_MEMBERS    = 'showing_for_members'; // 会員にのみ掲載
    case SALE_FOR_MEMBERS       = 'sale_for_members'; // 購入は会員のみ可能

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
            self::SHOWING_FOR_MEMBERS   => '会員にのみ掲載',
            self::SALE_FOR_MEMBERS      => '掲載状態だが購入は会員のみ可能',
        };
    }
}
