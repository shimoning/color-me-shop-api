<?php

namespace Shimoning\ColorMeShopApi\Constants;

enum TaxRoundingMethod: string
{
    case ROUND_OFF  = 'round_off';
    case ROUND_DOWN = 'round_down';
    case ROUND_UP   = 'round_up';

    public function name(): string
    {
        return match ($this) {
            self::ROUND_OFF     => '四捨五入',
            self::ROUND_DOWN    => '切り捨て',
            self::ROUND_UP      => '切り上げ',
        };
    }
}
