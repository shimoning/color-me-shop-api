<?php

namespace Shimoning\ColorMeShopApi\Constants;

enum PointState: string
{
    case ASSUMED    = 'assumed';
    case FIXED      = 'fixed';
    case CANCELED   = 'canceled';

    public function name(): string
    {
        return match ($this) {
            self::ASSUMED   => '仮付与',
            self::FIXED     => '確定済み',
            self::CANCELED  => 'キャンセル済み',
        };
    }
}
