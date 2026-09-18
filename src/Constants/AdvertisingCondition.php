<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Constants;

/**
 * 公式 OpenAPI product_advertisings の enum 定義。
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
enum AdvertisingCondition: string
{
    case NEW = 'new';
    case USED = 'used';
}
