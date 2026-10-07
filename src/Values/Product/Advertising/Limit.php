<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Values\Product\Advertising;

/**
 * 商品広告一覧 GET (`/v1/product_advertisings`) の取得件数。上限は 250。
 *
 * @see docs/api-pagination-limit-observation.md
 * @see docs/adr/0036-validate-limit-per-api.md
 */
final class Limit extends \Shimoning\ColorMeShopApi\Values\Limit
{
    protected const MAX = 250;
}
