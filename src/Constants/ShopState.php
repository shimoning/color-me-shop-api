<?php

namespace Shimoning\ColorMeShopApi\Constants;

enum ShopState: string
{
    case ENABLED    = 'enabled';
    case SUSPENDED  = 'suspended';
    case UNSIGNED   = 'unsigned';

    public function name(): string
    {
        return match ($this) {
            self::ENABLED   => '有効',
            self::SUSPENDED => '停止',
            self::UNSIGNED  => '未登録',
        };
    }
}
