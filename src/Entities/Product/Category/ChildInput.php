<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product\Category;

use Shimoning\ColorMeShopApi\Aliases;

use Shimoning\ColorMeShopApi\Constants\CategoryDisplayState;
use Shimoning\ColorMeShopApi\Entities\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Entities\Common\MetaTagInput;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

/**
 * 小カテゴリーの作成 (POST /v1/categories/{category_id}/children) と
 * 更新 (PUT /v1/categories/{category_id}/children/{id}) の `category` 入力。
 *
 * 明示したフィールドだけを送信し、明示した `null` も送信する。`display_state` は
 * `CategoryDisplayState` の3値に限り、`meta_tag` は `MetaTagInput` へ変換する。不正な形状は
 * 構築時に `InvalidFieldException` で拒否する。作成時の `name` の必須性は API の検証に委ねる。
 *
 * 公式 OpenAPI との差分: nullable 指定のないフィールドも `null` を指定できる。`expl` は `null` で
 * クリアされず、`meta_tag` の部分更新は省略したキーを `null` に置換する (2026-09-21)。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 * @see docs/api-product-structure.md
 * @see docs/adr/0010-split-category-into-big-and-small.md
 * @see docs/adr/0014-model-product-write-api.md
 */
class ChildInput extends Entity implements RequestEntity
{
    public const FIELD_TYPES = [
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

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(ChildInput::class);
