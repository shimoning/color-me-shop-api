<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Constants\Shop;

use Shimoning\ColorMeShopApi\Aliases;

/**
 * ショップの利用状態。
 */
enum State: string
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

// 0.32.0 の enum 移動の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(State::class);
