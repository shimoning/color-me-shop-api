<?php

namespace Shimoning\ColorMeShopApi\Constants;

/**
 * OAuth 認証で要求できるアクセススコープ。
 */
enum AuthScope: string
{
    case READ_PRODUCTS = 'read_products'; // 商品データの参照
    case WRITE_PRODUCTS = 'write_products'; // 商品データの更新
    case READ_SALES = 'read_sales'; // 受注データの参照
    case WRITE_SALES = 'write_sales'; // 受注データの更新
    case READ_SHOP_COUPONS = 'read_shop_coupons'; // ショップクーポンの参照
}
