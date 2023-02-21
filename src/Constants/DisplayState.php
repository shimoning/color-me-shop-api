<?php

namespace Shimoning\ColorMeShopApi\Constants;

enum DisplayState: string
{
    case SHOWING    = 'showing';
    case HIDDEN     = 'hidden';

    public function name(): string
    {
        return match ($this) {
            self::SHOWING   => '表示する',
            self::HIDDEN    => '表示しない',
        };
    }
}
