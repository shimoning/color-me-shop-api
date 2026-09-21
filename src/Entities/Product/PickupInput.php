<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Constants\PickupType;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * おすすめ商品情報の作成・更新 (POST / PUT /v1/products/{product_id}/pickups) の入力。
 *
 * 公式 OpenAPI では `pickup_type` と `order_num` をトップレベルに持ち、required 指定はない。
 * `pickup_type` は `PickupType` のバッキング値 (0 / 1 / 3 / 4) で指定し、未定義値は拒否する。
 * コンストラクタ配列で明示したフィールドだけを送信し、明示した `null` も送信する (ADR 0014)。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
class PickupInput extends Entity implements RequestEntity
{
    public const OBJECT_FIELDS = [
        'pickupType' => ['enum' => PickupType::class],
    ];

    protected ?PickupType $pickupType;
    protected ?int $orderNum;
}
