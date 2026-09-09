<?php

namespace Shimoning\ColorMeShopApi\Constants;

/**
 * 送信する受注メールの種別。
 */
enum MailType: string
{
    case ACCEPTED   = 'accepted'; // 受注メール
    case PAID       = 'paid'; // 入金確認メール
    case DELIVERED  = 'delivered'; // 商品発送メール

    /**
     * メール種別の日本語名を取得する。
     *
     * @return string
     */
    public function name(): string
    {
        return match ($this) {
            self::ACCEPTED  => '受注メール',
            self::PAID      => '入金確認メール',
            self::DELIVERED => '商品発送メール',
        };
    }
}
