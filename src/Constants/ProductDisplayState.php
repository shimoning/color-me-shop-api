<?php

namespace Shimoning\ColorMeShopApi\Constants;

enum ProductDisplayState: string
{
    case SHOWING                = 'showing';
    case HIDDEN                 = 'hidden';
    case SHOWING_FOR_MEMBERS    = 'showing_for_members';
    case SALE_FOR_MEMBERS       = 'sale_for_members';

    public function name(): string
    {
        return match ($this) {
            self::SHOWING               => '掲載状態',
            self::HIDDEN                => '非掲載状態',
            self::SHOWING_FOR_MEMBERS   => '会員にのみ掲載',
            self::SALE_FOR_MEMBERS      => '掲載状態だが購入は会員のみ可能',
        };
    }
}
