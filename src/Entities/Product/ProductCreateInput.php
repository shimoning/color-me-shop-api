<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 商品の作成 (POST /v1/products) の `product` 入力。
 *
 * 明示したフィールドだけを送信し、明示した `null` も送信する。`display_state` は
 * `ProductDisplayState` の4値を受け付け、`members_only` は拒否する。`unlisted` は送信できない。
 *
 * 公式 OpenAPI との差分: nullable 指定のないフィールドも `null` を指定でき、受理可否は API に委ねる。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 * @see docs/api-product-structure.md
 * @see docs/adr/0014-model-product-write-api.md
 */
class ProductCreateInput extends Entity implements RequestEntity
{
    public const FIELD_TYPES = [
        'displayState' => ['enum' => ProductDisplayState::class],
    ];

    protected ?string $name;
    protected ?int $price;
    protected ?int $categoryIdBig;
    protected ?int $cost;
    protected ?int $salesPrice;
    protected ?int $membersPrice;
    protected ?string $modelNumber;
    protected ?string $expl;
    protected ?string $simpleExpl;
    protected ?string $smartphoneExpl;
    protected ?ProductDisplayState $displayState;
    protected ?bool $stockManaged;
    protected ?bool $taxReduced;
}
