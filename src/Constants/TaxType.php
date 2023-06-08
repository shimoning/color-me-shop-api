<?php

namespace Shimoning\ColorMeShopApi\Constants;

enum TaxType: string
{
    case EXCLUDED   = 'excluded';
    case INCLUDED   = 'included';

    public function name(): string
    {
        return match ($this) {
            self::EXCLUDED  => '外税',
            self::INCLUDED  => '内税',
        };
    }
}
