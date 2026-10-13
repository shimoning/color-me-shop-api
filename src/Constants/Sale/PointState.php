<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Constants\Sale;

use Shimoning\ColorMeShopApi\Aliases;

/**
 * ショップポイントの付与状態。
 *
 * 公式 OpenAPI との差分: 更新 request の `cenceled` は response の `canceled` と一致しない。
 * 本ライブラリは `canceled` を送信する。
 */
enum PointState: string
{
    case ASSUMED    = 'assumed'; // 仮付与
    case FIXED      = 'fixed'; // 確定済み
    case CANCELED   = 'canceled'; // キャンセル済み

    /**
     * 付与状態の日本語名を取得する。
     *
     * @return string
     */
    public function name(): string
    {
        return match ($this) {
            self::ASSUMED   => '仮付与',
            self::FIXED     => '確定済み',
            self::CANCELED  => 'キャンセル済み',
        };
    }
}

// 0.32.0 の enum 移動の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(PointState::class);
