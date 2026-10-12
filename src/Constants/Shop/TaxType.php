<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Constants\Shop;

use Shimoning\ColorMeShopApi\Aliases;

/**
 * 商品価格の消費税区分。
 */
enum TaxType: string
{
    case EXCLUDED   = 'excluded'; // 外税
    case INCLUDED   = 'included'; // 内税

    /**
     * 消費税区分の日本語名を取得する。
     *
     * @return string
     */
    public function name(): string
    {
        return match ($this) {
            self::EXCLUDED  => '外税',
            self::INCLUDED  => '内税',
        };
    }
}

// 0.32.0 の enum 移動の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(TaxType::class);
