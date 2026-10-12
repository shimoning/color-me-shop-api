<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Constants\Sale;

use Shimoning\ColorMeShopApi\Aliases;

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

// 0.32.0 の enum 移動の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(MailType::class);
