<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Values\Customer;

/**
 * 顧客一覧 GET (`/v1/customers`) の取得件数。上限は 100。
 *
 * @see docs/api-pagination-limit-observation.md
 * @see docs/adr/0036-validate-limit-per-api.md
 */
final class Limit extends \Shimoning\ColorMeShopApi\Values\Limit
{
    protected const MAX = 100;
}
