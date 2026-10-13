<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Constants\Shop;

use Shimoning\ColorMeShopApi\Aliases;

/**
 * ショップの契約プラン。
 */
enum ContractPlan: string
{
    case UNKNOWN    = 'unknown'; // 不明

    case REGULAR    = 'regular'; // レギュラー
    case LARGE      = 'large'; // ラージ
    case PREMIUM    = 'premium'; // プレミアム

    case FREE       = 'free'; // 無料プラン
    case ECONOMY    = 'economy'; // エコノミー
    case SMALL      = 'small'; // スモール
    case PLATINUM   = 'platinum'; // プラチナ
    case DORMANT    = 'dormant'; // 休眠

    case LOLIPOP    = 'lolipop'; // ロリポップ
    case HETEML     = 'heteml'; // Heteml
    case GOOPE      = 'goope'; // GOOPE

    /**
     * 契約プランの日本語名を取得する。
     *
     * @return string
     */
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

// 0.32.0 の enum 移動の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(ContractPlan::class);
