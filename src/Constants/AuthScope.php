<?php

namespace Shimoning\ColorMeShopApi\Constants;

/**
 * OAuth 認証で要求できるアクセススコープ。
 *
 * 公式 OpenAPI (https://api.shop-pro.jp/v1/spec/open_api.json) を 2026-09-17 に取得。
 * 出典は info.description の scope 表であり、enum: 制約ではない。
 */
enum AuthScope: string
{
    case READ_PRODUCTS = 'read_products'; // 商品データの参照
    case WRITE_PRODUCTS = 'write_products'; // 商品データの更新
    case READ_SALES = 'read_sales'; // 受注データの参照
    case WRITE_SALES = 'write_sales'; // 受注データの更新
    case READ_SHOP_COUPONS = 'read_shop_coupons'; // ショップクーポンの参照
    case WRITE_SHOP_COUPONS = 'write_shop_coupons'; // ショップクーポンの更新
    case READ_TEMPLATES = 'read_templates'; // テンプレートの参照
    case WRITE_TEMPLATES = 'write_templates'; // テンプレートの追加・更新
    case READ_ANALYTICS = 'read_analytics'; // アナリティクスの参照
}
