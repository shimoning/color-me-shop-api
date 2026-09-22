<?php

namespace Shimoning\ColorMeShopApi\Constants;

/**
 * 商品グループの表示状態。
 *
 * 実 API の観測 (2026-09-21) で、グループの `display_state` は `showing` / `hidden` / `members_only` の
 * 3値だった。`PUT /v1/groups/{id}` は `members_only` を受理して GET も `members_only` を返し、
 * `showing_for_members` / `sale_for_members` は 422 (`field: group.display_state`) で拒否された。
 * これは公式 OpenAPI のグループ作成・更新 request の enum と一致する。
 *
 * `SHOWING_FOR_MEMBERS` / `SALE_FOR_MEMBERS` の 2 値は公式 OpenAPI の `productGroup` response 定義に
 * 基づき、応答の受理のみを目的として含める。PUT では 422 (2026-09-21 実測) で、読み取りでも観測して
 * いないが、管理画面等で設定された既存グループが応答で返す可能性を否定できないため、未知値として
 * 一覧全体が読めなくなる (0.11.0 以前の `Product\Group` で起きた) 失敗を避ける。書き込み側の
 * `Product\GroupInput` はこの 2 値を送信前に `InvalidFieldException` で拒否する
 * (`GroupInput::WRITABLE_DISPLAY_STATES`)。
 *
 * 商品の 4 値 (`ProductDisplayState`)、カテゴリーの 3 値 (`CategoryDisplayState`) とは別リソースの
 * 表示状態であり、値の集合が重なっても互いに代入できない。case 名は既存の
 * `CategoryDisplayState::MEMBER_ONLY` に揃えている。
 *
 * 出典: docs/api-product-structure.md の「2026-09-21 の追加観測（グループ・カテゴリーの書き込み smoke test）」、
 * docs/enum-openapi-audit.md。
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
