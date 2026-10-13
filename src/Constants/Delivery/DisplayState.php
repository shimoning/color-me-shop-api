<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Constants\Delivery;

use Shimoning\ColorMeShopApi\Aliases;

/**
 * 汎用的な表示状態。
 */
enum DisplayState: string
{
    case SHOWING    = 'showing'; // 表示
    case HIDDEN     = 'hidden'; // 非表示

    /**
     * 表示状態の日本語名を取得する。
     *
     * @return string
     */
    public function name(): string
    {
        return match ($this) {
            self::SHOWING   => '表示する',
            self::HIDDEN    => '表示しない',
        };
    }
}

// 0.32.0 の enum 移動の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(DisplayState::class);
