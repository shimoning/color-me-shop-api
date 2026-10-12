<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Constants\Shop;

use Shimoning\ColorMeShopApi\Aliases;

/**
 * ショップの開店状態。
 */
enum OpenState: string
{
    case OPENED     = 'opened'; // 開店
    case CLOSED     = 'closed'; // 閉店（工事中）
    case PREPARE    = 'prepare'; // 準備中
    case PAUSED     = 'paused'; // 休止中

    /**
     * 開店状態の日本語名を取得する。
     *
     * @return string
     */
    public function name(): string
    {
        return match ($this) {
            self::OPENED    => '開店',
            self::CLOSED    => '閉店(工事中)',
            self::PREPARE   => '準備中',
            self::PAUSED    => '休止中',
        };
    }
}

// 0.32.0 の enum 移動の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(OpenState::class);
