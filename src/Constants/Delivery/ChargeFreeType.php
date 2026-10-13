<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Constants\Delivery;

use Shimoning\ColorMeShopApi\Aliases;

/**
 * 配送料を無料にする条件。
 */
enum ChargeFreeType: string
{
    case NOT_FREE       = 'not_free'; // 有料
    case FREE_TO_LIMIT  = 'free_to_limit'; // 注文金額が一定以上の場合は無料
    case FREE           = 'free'; // 無料

    /**
     * 無料条件の日本語名を取得する。
     *
     * @return string
     */
    public function name(): string
    {
        return match ($this) {
            self::NOT_FREE      => '請求する',
            self::FREE_TO_LIMIT => '請求する（無料となる上限金額あり）',
            self::FREE          => '請求しない',
        };
    }
}

// 0.32.0 の enum 移動の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(ChargeFreeType::class);
