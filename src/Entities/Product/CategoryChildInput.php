<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Constants\CategoryDisplayState;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

/**
 * 小カテゴリーの作成 (POST /v1/categories/{category_id}/children) と
 * 更新 (PUT /v1/categories/{category_id}/children/{id}) の `category` 入力。
 *
 * 公式 OpenAPI の `category` object は大カテゴリーの作成・更新と同じプロパティ集合で、作成と更新で
 * 共用する (ADR 0014)。作成側では `name` が required だが、更新側に required 指定はない。
 * 共用のため型では区別せず、`name` のない作成要求は API の検証 (422) に委ねる。
 * 大カテゴリーの入力 `CategoryInput` とは同じ形だが、応答の `BigCategory` / `SmallCategory` の分割
 * (ADR 0010) に合わせて別の型にし、互いに代入できないようにしている。
 *
 * 直列化の契約、`display_state` (`CategoryDisplayState`)、`meta_tag` (`MetaTagInput`) の扱いは
 * `CategoryInput` と同じ。実 API の観測 (2026-09-21) で `expl` の明示 `null` がクリアされず
 * `meta_tag` の部分更新が置換になる点も大カテゴリーと同じ。
 * 出典: docs/api-product-structure.md の「2026-09-21 の追加観測（グループ・カテゴリーの書き込み smoke test）」。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
class CategoryChildInput extends Entity implements RequestEntity
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
