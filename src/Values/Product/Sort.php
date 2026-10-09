<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Values\Product;

/**
 * 商品一覧 GET (`/v1/products`) の並び順。
 *
 * 使用できる列は make_date、update_date、sales_price、price、members_price。
 *
 * @see docs/api-product-sort-observation.md
 * @see docs/adr/0039-validate-product-sort-fields.md
 */
final class Sort extends \Shimoning\ColorMeShopApi\Values\Sort
{
    protected const FIELDS = [
        'make_date',
        'update_date',
        'sales_price',
        'price',
        'members_price',
    ];
}
