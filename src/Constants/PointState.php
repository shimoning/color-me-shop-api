<?php

namespace Shimoning\ColorMeShopApi\Constants;

/**
 * ショップポイントの付与状態。
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
