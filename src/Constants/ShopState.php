<?php

namespace Shimoning\ColorMeShopApi\Constants;

/**
 * ショップの利用状態。
 */
enum ShopState: string
{
    case ENABLED    = 'enabled'; // 有効
    case SUSPENDED  = 'suspended'; // 停止
    case UNSIGNED   = 'unsigned'; // 未登録

    /**
     * 利用状態の日本語名を取得する。
     *
     * @return string
     */
    public function name(): string
    {
        return match ($this) {
            self::ENABLED   => '有効',
            self::SUSPENDED => '停止',
            self::UNSIGNED  => '未登録',
        };
    }
}
