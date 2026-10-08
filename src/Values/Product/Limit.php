<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Values\Product;

/**
 * 商品一覧 GET (`/v1/products`) の取得件数。上限は 50。
 *
 * @see docs/api-pagination-limit-observation.md
 * @see docs/adr/0036-validate-limit-per-api.md
 */
final class Limit extends \Shimoning\ColorMeShopApi\Values\Limit
{
    protected const MAX = 50;
}
