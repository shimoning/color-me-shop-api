<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Constants\CategoryDisplayState;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

/**
 * 大カテゴリーの作成 (POST /v1/categories) と更新 (PUT /v1/categories/{id}) の `category` 入力。
 *
 * 公式 OpenAPI の両 `category` object は同じプロパティ集合で、作成と更新で共用する (ADR 0014)。
 * 作成側では `name` が required だが、更新側に required 指定はない。共用のため型では区別せず、
 * `name` のない作成要求は API の検証 (422) に委ねる。
 * 小カテゴリーの入力は同じ形の `CategoryChildInput` で、互いに代入できない。
 *
 * 直列化の契約:
 * - コンストラクタ配列で明示したフィールドだけを送信し、明示した `null` も送信する。
 * - 指定しなかったフィールドは送信しない。
 * - 公式 OpenAPI で nullable なのは `expl` と `meta_tag` の各値だけだが、ProductInput と同じく
 *   全フィールドを nullable にし、`null` の受理は API 側に委ねる。
 *
 * `display_state` は request 定義と応答の双方で `showing` / `hidden` / `members_only`
 * (`CategoryDisplayState`) で、商品・グループの `showing_for_members` などは拒否する。
 * 実 API の観測 (2026-09-21) では、`expl` は明示した `null` を送っても旧値のまま残り (クリアされない)、
 * 空文字 `""` は保存された。`meta_tag` の部分更新はマージではなく置換で、送らなかったキーは `null` になる。
 * 出典: docs/api-product-structure.md の「2026-09-21 の追加観測（グループ・カテゴリーの書き込み smoke test）」。
 *
 * `meta_tag` は `MetaTagInput` へ変換する。`title` / `keywords` / `description` のいずれも持たない配列は
 * JSON で `[]` になり OpenAPI の object 定義に合わないため、構築時に `InvalidFieldException` で拒否する。
 * 値の範囲 (`sort` の `minimum` など) は API 側の検証に委ね、ライブラリでは検証しない。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
class CategoryInput extends Entity implements RequestEntity
{
    public const OBJECT_FIELDS = [
        'displayState' => ['enum' => CategoryDisplayState::class],
        'metaTag' => ['allowNull' => true, 'entity' => MetaTagInput::class],
    ];

    protected ?string $name;
    protected ?string $expl;
    protected ?int $sort;
    protected ?CategoryDisplayState $displayState;
    protected ?MetaTagInput $metaTag;

    /**
     * @param array<string, mixed> $data
     * @throws InvalidFieldException 値の型や `meta_tag` の形状が公式 OpenAPI の定義に合わない場合
     */
    public function __construct(array $data)
    {
        MetaTagInput::assertOwnerField(self::class, $data['meta_tag'] ?? null);
        parent::__construct($data);
    }
}
