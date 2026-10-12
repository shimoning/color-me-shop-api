<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Constants\Customer;

use Shimoning\ColorMeShopApi\Aliases;
use Shimoning\ColorMeShopApi\Constants\FallbackEnum;

/**
 * 顧客に関連付けられた外部システムの種別。
 */
enum ExternalAccountProvider: int implements FallbackEnum
{
    case LINE = 0;

    /**
     * API 仕様に存在しない未知の provider を表す番兵。
     * 実際の API 値としては使用しない。
     */
    case UNKNOWN = -1;

    public static function fallbackCase(): static
    {
        return self::UNKNOWN;
    }
}

// 0.32.0 の enum 移動の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(ExternalAccountProvider::class);
