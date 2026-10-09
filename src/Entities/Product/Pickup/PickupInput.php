<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product\Pickup;

use Shimoning\ColorMeShopApi\Aliases;

use Shimoning\ColorMeShopApi\Constants\PickupType;
use Shimoning\ColorMeShopApi\Entities\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * おすすめ商品情報の作成・更新 (POST / PUT /v1/products/{product_id}/pickups) の入力。
 *
 * `pickup_type` は `PickupType` のバッキング値 (0 / 1 / 3 / 4) または `PickupType` のインスタンスで
 * 指定し (インスタンスはバッキング値へ正規化して送信する)、未定義値と他の enum のインスタンスは拒否する。
 * 明示したフィールドだけを送信し、明示した `null` も送信する。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 * @see docs/adr/0014-model-product-write-api.md
 */
class PickupInput extends Entity implements RequestEntity
{
    public const FIELD_TYPES = [
        'pickupType' => ['enum' => PickupType::class],
    ];

    protected ?PickupType $pickupType;
    protected ?int $orderNum;
}

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(PickupInput::class);
