<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Customer;

use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 顧客ショップポイントの増減 (POST /v1/customers/{customer_id}/points) の入力。
 *
 * 公式 OpenAPI では `points` をトップレベルに持つ object で、`points` は required かつ
 * `nullable: false` の integer である。正の値が加算、負の値が減算を表す。
 *
 * `points` は REQUIRED_FIELDS として公開し、Services\Customer::changePoints() が送信前に
 * 明示を確認する (ADR 0015)。nullable: false のため非 null のプロパティとして宣言しており、
 * 明示した `null` は型として拒否する。
 *
 * 値の範囲 (保有ポイントを超える減算など) は公式 OpenAPI に定義がないため、API 側の判断に委ねる。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
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
