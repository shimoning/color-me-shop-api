<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Constants\Shop;

use Shimoning\ColorMeShopApi\Aliases;

/**
 * 消費税額の端数処理方法。
 */
enum TaxRoundingMethod: string
{
    case ROUND_OFF  = 'round_off'; // 四捨五入
    case ROUND_DOWN = 'round_down'; // 切り捨て
    case ROUND_UP   = 'round_up'; // 切り上げ

    /**
     * 端数処理方法の日本語名を取得する。
     *
     * @return string
     */
    public function name(): string
    {
        return match ($this) {
            self::ROUND_OFF     => '四捨五入',
            self::ROUND_DOWN    => '切り捨て',
            self::ROUND_UP      => '切り上げ',
        };
    }
}

// 0.32.0 の enum 移動の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(TaxRoundingMethod::class);
