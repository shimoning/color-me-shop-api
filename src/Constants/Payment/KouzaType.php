<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Constants\Payment;

use Shimoning\ColorMeShopApi\Aliases;
use Shimoning\ColorMeShopApi\Constants\FallbackEnum;

/**
 * 振込先口座の種別。
 * UNKNOWN は API 仕様外の応答値を示す番兵。元値は Entity::getRaw() で確認できる。
 * Entity::toArrayRecursive() では元値でなく番兵の __unknown__ になる。
 */
enum KouzaType: string implements FallbackEnum
{
    case SAVING = 'saving';  // 普通
    case CHECKING = 'checking';  // 当座
    case UNKNOWN = '__unknown__'; // API 仕様外の応答値

    public static function fallbackCase(): static
    {
        return self::UNKNOWN;
    }
}

// 0.32.0 の enum 移動の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(KouzaType::class);
