<?php

namespace Shimoning\ColorMeShopApi\Constants;

/**
 * 未知の backing value を専用 case へ変換できる enum の契約。
 */
interface FallbackEnum extends \BackedEnum
{
    public static function fallbackCase(): static;
}
