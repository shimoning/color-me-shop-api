<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Constants\Product\Advertising;

use Shimoning\ColorMeShopApi\Aliases;

/**
 * 公式 OpenAPI product_advertisings の enum 定義。
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
enum AdvertisingCondition: string
{
    case NEW = 'new';
    case USED = 'used';
}

// 0.32.0 の enum 移動の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(AdvertisingCondition::class);
