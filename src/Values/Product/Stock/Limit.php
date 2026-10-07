<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Values\Product\Stock;

/**
 * 在庫一覧 GET (`/v1/stocks`) の取得件数。上限は 50。
 *
 * @see docs/api-pagination-limit-observation.md
 * @see docs/adr/0036-validate-limit-per-api.md
 */
final class Limit extends \Shimoning\ColorMeShopApi\Values\Limit
{
    protected const MAX = 50;
}
