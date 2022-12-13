<?php

namespace Shimoning\ColorMeShopApi\Constants;

enum MailState: string
{
    case NOT_YET = 'not_yet';
    case SENT = 'sent';
    case PASS = 'pass';

    public function name(): string
    {
        return match ($this) {
            self::NOT_YET   => '未送信',
            self::SENT      => '送信済み',
            self::PASS      => '送信しない',
        };
    }
}
