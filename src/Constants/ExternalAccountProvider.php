<?php

namespace Shimoning\ColorMeShopApi\Constants;

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
