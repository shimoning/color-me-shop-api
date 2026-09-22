<?php

namespace Shimoning\ColorMeShopApi\Constants;

/**
 * 商品の表示および販売状態。
 *
 * 商品の応答・入力・検索条件で使う。商品入力の実測 (2026-09-20) で本 enum の4値が受理され、
 * `members_only` は 422 になった。
 *
 * 商品グループには使わない。公式 OpenAPI の `productGroup` response は本 enum と同じ4値を列挙するが、
 * 実 API の観測 (2026-09-21) ではグループの `display_state` は `showing` / `hidden` / `members_only` の
 * 3値で、`showing_for_members` / `sale_for_members` は書き込みで 422、読み取りでも観測されなかった。
 * グループは `GroupDisplayState` (実測の 3 値に response 定義の 2 値を加えた 5 値) を使う
 * (docs/enum-openapi-audit.md)。
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
