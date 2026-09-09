<?php

namespace Shimoning\ColorMeShopApi\Constants;

/**
 * 受注メールの送信状態。
 */
enum MailState: string
{
    case NOT_YET = 'not_yet'; // 未送信
    case SENT = 'sent'; // 送信済み
    case PASS = 'pass'; // 送信しない

    /**
     * 送信状態の日本語名を取得する。
     *
     * @return string
     */
    public function name(): string
    {
        return match ($this) {
            self::NOT_YET   => '未送信',
            self::SENT      => '送信済み',
            self::PASS      => '送信しない',
        };
    }
}
