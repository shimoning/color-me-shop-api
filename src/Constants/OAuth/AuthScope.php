<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Constants\OAuth;

use Shimoning\ColorMeShopApi\Aliases;

/**
 * OAuth 認証で要求できるアクセススコープ。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
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

// 0.32.0 の enum 移動の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(AuthScope::class);
