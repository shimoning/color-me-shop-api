<?php

namespace Shimoning\ColorMeShopApi\Exceptions;

/**
 * API レスポンスに存在するフィールドの値または型が不正な場合の例外。
 */
class InvalidFieldException extends ColorMeApiException
{
    public static function for(
        string $class,
        string $apiField,
        string $expected,
        mixed $actual,
        ?\Throwable $previous = null,
    ): self {
        return new self(
            \sprintf(
                '%s の API フィールド『%s』が不正です。%s を期待しましたが %s でした。',
                $class,
                $apiField,
                $expected,
                \get_debug_type($actual),
            ),
            0,
            $previous,
        );
    }
}
