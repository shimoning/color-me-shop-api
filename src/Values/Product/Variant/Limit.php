<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Values\Product\Variant;

/**
 * 商品バリエーション一覧 GET (`/v1/products/{product_id}/variants`) の取得件数。上限は 100。
 *
 * 公式 OpenAPI との差分: 上限を 50 と説明しているが、実 API では 100 (2026-10-07)。
 *
 * @see docs/api-pagination-limit-observation.md
 * @see docs/adr/0036-validate-limit-per-api.md
 */
final class Limit extends \Shimoning\ColorMeShopApi\Values\Limit
{
    protected const MAX = 100;
}
