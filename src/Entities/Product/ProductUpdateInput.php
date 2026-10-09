<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;
use Shimoning\ColorMeShopApi\Entities\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 商品の更新 (PUT /v1/products/{id}) の `product` 入力。
 *
 * 明示したフィールドだけを送信する部分更新で、明示した `null` も送信する。`sales_price` と `price` は
 * 明示した `null` でクリアできることを確認している (2026-09-20、2026-09-21)。`display_state` は
 * `ProductDisplayState` の4値を受け付け、`members_only` は拒否する。`unlisted` は送信できない。
 * `group_ids` / `stocks` / `variants` の不正な形状は構築時に `InvalidFieldException` で拒否する。
 *
 * 公式 OpenAPI との差分: nullable 指定のないトップレベルフィールドも `null` を指定でき、受理可否は
 * API に委ねる。ネストした `variants[].stocks` は `null` を受け付けない。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 * @see docs/api-product-structure.md
 * @see docs/adr/0014-model-product-write-api.md
 */
class ProductUpdateInput extends Entity implements RequestEntity
{
    public const FIELD_TYPES = [
        'displayState' => ['enum' => ProductDisplayState::class],
        'stocks' => [
            'entity' => StocksIncrementInput::class,
            'orScalar' => 'int',
            'allowNull' => true,
        ],
        'groupIds' => ['array' => true, 'scalar' => 'int'],
        'variants' => [
            'array' => true,
            'entity' => VariantInput::class,
            'strictList' => true,
            'allowNull' => true,
        ],
    ];

    protected ?string $name;
    protected ?int $price;
    protected ?int $categoryIdBig;
    protected ?int $categoryIdSmall;
    protected ?int $cost;
    protected ?int $salesPrice;
    protected ?int $membersPrice;
    protected ?string $modelNumber;
    protected ?string $expl;
    protected ?string $simpleExpl;
    protected ?string $smartphoneExpl;
    protected ?ProductDisplayState $displayState;
    protected ?bool $stockManaged;
    /** 更新専用。整数の絶対値か increment object */
    protected StocksIncrementInput|int|null $stocks;
    /** @var list<int>|null 更新専用 */
    protected ?array $groupIds;
    /** @var list<VariantInput>|null 更新専用 */
    protected ?array $variants;
    protected ?bool $taxReduced;
}
