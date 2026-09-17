<?php

namespace Shimoning\ColorMeShopApi\Constants;

/**
 * ショップポイントの付与状態。
 *
 * 公式 OpenAPI の sale response は `canceled`、更新 request のみ `cenceled` と記載される（typo の疑い）。
 * 本ライブラリは `canceled` を送信する。実 API がどちらを受理するかは未検証。
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
