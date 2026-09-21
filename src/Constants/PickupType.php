<?php

namespace Shimoning\ColorMeShopApi\Constants;

/**
 * おすすめ商品情報 (pickup) の種別。
 *
 * 公式 OpenAPI の商品ピックアップ作成・更新 request が列挙する `0`、`1`、`3`、`4` に対応する。
 * 要求側で使う enum のため未知値のフォールバックは設けない (ADR 0013、ADR 0014)。
 */
enum PickupType: int
{
    case RECOMMENDED    = 0; // おすすめ商品
    case BEST_SELLER    = 1; // 売れ筋商品
    case NEW_ARRIVAL    = 3; // 新着商品
    case FEATURED       = 4; // イチオシ商品

    /**
     * 種別の日本語名を取得する。
     *
     * @return string
     */
    public function name(): string
    {
        return match ($this) {
            self::RECOMMENDED   => 'おすすめ商品',
            self::BEST_SELLER   => '売れ筋商品',
            self::NEW_ARRIVAL   => '新着商品',
            self::FEATURED      => 'イチオシ商品',
        };
    }
}
