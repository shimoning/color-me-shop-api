<?php

namespace Shimoning\ColorMeShopApi\Constants;

enum OpenState: string
{
    case OPENED     = 'opened';
    case CLOSED     = 'closed';
    case PREPARE    = 'prepare';
    case PAUSED     = 'paused';

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
