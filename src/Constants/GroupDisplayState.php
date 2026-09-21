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
 * 公式 OpenAPI の `productGroup` response が列挙する `showing_for_members` / `sale_for_members` は、
 * 書き込みで拒否され読み取りでも観測されなかったため、本 enum には含めない。応答側で万一出現した場合は
 * `Product\Group` の構築時に `InvalidFieldException` になる (未知値のフォールバックは設けない)。
 * 商品の 4 値 (`ProductDisplayState`)、カテゴリーの 3 値 (`CategoryDisplayState`) とは別リソースの
 * 表示状態であり、値の集合は同じでも互いに代入できない。
 *
 * 出典: docs/api-product-structure.md の「2026-09-21 の追加観測（グループ・カテゴリーの書き込み smoke test）」、
 * docs/enum-openapi-audit.md。
 */
enum GroupDisplayState: string
{
    case SHOWING        = 'showing'; // 掲載
    case HIDDEN         = 'hidden'; // 非掲載
    case MEMBERS_ONLY   = 'members_only'; // 会員にのみ掲載

    /**
     * 表示状態の日本語名を取得する。
     *
     * @return string
     */
    public function name(): string
    {
        return match ($this) {
            self::SHOWING       => '掲載状態',
            self::HIDDEN        => '非掲載状態',
            self::MEMBERS_ONLY  => '会員にのみ掲載',
        };
    }
}
