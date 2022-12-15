<?php

namespace Shimoning\ColorMeShopApi\Constants;

enum AuthScope: string
{
    case READ_PRODUCTS = 'read_products';
    case WRITE_PRODUCTS = 'write_products';
    case READ_SALES = 'read_sales';
    case WRITE_SALES = 'write_sales';
    case READ_SHOP_COUPONS = 'read_shop_coupons';
}
