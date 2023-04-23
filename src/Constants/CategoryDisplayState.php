<?php

namespace Shimoning\ColorMeShopApi\Constants;

enum CategoryDisplayState: string
{
    case SHOWING        = 'showing';
    case HIDDEN         = 'hidden';
    case MEMBER_ONLY    = 'members_only';

    public function name(): string
    {
        return match ($this) {
            self::SHOWING       => '掲載状態',
            self::HIDDEN        => '非掲載状態',
            self::MEMBER_ONLY   => '会員にのみ掲載',
        };
    }
}
