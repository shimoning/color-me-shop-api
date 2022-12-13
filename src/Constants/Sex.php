<?php

namespace Shimoning\ColorMeShopApi\Constants;

enum Sex: string
{
    case MALE   = 'male';
    case FEMALE = 'female';

    public function name(): string
    {
        return match ($this) {
            self::MALE      => '男性',
            self::FEMALE    => '女性',
        };
    }
}
