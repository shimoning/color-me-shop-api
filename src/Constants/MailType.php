<?php

namespace Shimoning\ColorMeShopApi\Constants;

enum MailType: string
{
    case ACCEPTED   = 'accepted';
    case PAID       = 'paid';
    case DELIVERED  = 'delivered';

    public function name(): string
    {
        return match ($this) {
            self::ACCEPTED  => '受注メール',
            self::PAID      => '入金確認メール',
            self::DELIVERED => '商品発送メール',
        };
    }
}
