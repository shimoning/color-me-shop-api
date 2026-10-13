<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Constants\Sale;

use Shimoning\ColorMeShopApi\Aliases;

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

// 0.32.0 の enum 移動の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(MailState::class);
