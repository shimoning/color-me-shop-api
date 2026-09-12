<?php

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * static フィールドを API レスポンスで上書きしないことの検証用テストダブル。
 */
class StaticFieldEntity extends Entity
{
    private static string $sharedState = 'original';

    public static function getSharedState(): string
    {
        return self::$sharedState;
    }

    public static function resetSharedState(): void
    {
        self::$sharedState = 'original';
    }
}
