<?php

namespace Shimoning\ColorMeShopApi\Constants;

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
