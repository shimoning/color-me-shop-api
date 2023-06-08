<?php

namespace Shimoning\ColorMeShopApi\Constants;

enum ContractPlan: string
{
    case UNKNOWN    = 'unknown';

    case REGULAR    = 'regular';
    case LARGE      = 'large';
    case PREMIUM    = 'premium';

    case FREE       = 'free';
    case ECONOMY    = 'economy';
    case SMALL      = 'small';
    case PLATINUM   = 'platinum';
    case DORMANT    = 'dormant';

    case LOLIPOP    = 'lolipop';
    case HETEML     = 'heteml';
    case GOOPE      = 'goope';

    public function name(): string
    {
        return match ($this) {
            self::UNKNOWN   => '不明',

            self::REGULAR   => 'レギュラー',
            self::LARGE     => 'ラージ',
            self::PREMIUM   => 'プレミアム',

            self::FREE      => '無料プラン',
            self::ECONOMY   => 'エコノミー',
            self::SMALL     => 'スモール',
            self::PLATINUM  => 'プラチナ',
            self::DORMANT   => '休眠',

            self::LOLIPOP   => 'ロリポップ',
            self::HETEML    => 'Heteml',
            self::GOOPE     => 'GOOPE',
        };
    }
}
