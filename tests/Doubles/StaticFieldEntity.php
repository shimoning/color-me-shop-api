<?php

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * static フィールドを API レスポンスで上書きしないことの検証用テストダブル。
 */
class StaticFieldEntity extends Entity
{
    public static string $publicSharedState = 'original';
    protected static string $protectedSharedState = 'original';
    private static string $privateSharedState = 'original';

    /**
     * @return array{public: string, protected: string, private: string}
     */
    public static function getSharedStates(): array
    {
        return [
            'public' => self::$publicSharedState,
            'protected' => self::$protectedSharedState,
            'private' => self::$privateSharedState,
        ];
    }

    public static function resetSharedState(): void
    {
        self::$publicSharedState = 'original';
        self::$protectedSharedState = 'original';
        self::$privateSharedState = 'original';
    }
}
