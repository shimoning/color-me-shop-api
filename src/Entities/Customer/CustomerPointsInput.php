<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Customer;

use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 顧客ショップポイントの増減 (POST /v1/customers/{customer_id}/points) の入力。
 *
 * 正の値は加算、負の値は減算を表す。未指定は送信前に、`null` は構築時に拒否する。
 * 値の範囲は API の検証に委ねる。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 * @see docs/adr/0015-model-customer-write-api.md
 */
class CustomerPointsInput extends Entity implements RequestEntity
{
    /**
     * 公式 OpenAPI が required とするフィールド。
     * Services\Customer::changePoints() が送信前に明示を確認する。
     */
    public const REQUIRED_FIELDS = ['points'];

    protected int $points;
}
