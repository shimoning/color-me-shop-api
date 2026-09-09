<?php

namespace Shimoning\ColorMeShopApi\Constants;

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
