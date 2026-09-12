<?php

namespace Shimoning\ColorMeShopApi\Exceptions;

/**
 * API レスポンスに存在するフィールドの値または型が不正な場合の例外。
 */
class InvalidFieldException extends ColorMeApiException
{
    public static function forArrayElement(
        string $class,
        string $apiField,
        string $expected,
        \Throwable $previous,
    ): self {
        return new self(
            \sprintf(
                '%s の API フィールド『%s』が不正です。配列要素を %s に変換できませんでした。原因: %s',
                $class,
                $apiField,
                $expected,
                self::arrayElementCause($previous),
            ),
            0,
            $previous,
        );
    }

    private static function arrayElementCause(\Throwable $previous): string
    {
        if (
            $previous instanceof \UnexpectedValueException
            && $previous->getMessage() === '未知の enum 値です。'
        ) {
            return '未知の enum 値です。';
        }

        if ($previous instanceof \TypeError) {
            return '配列要素の型が不正です。';
        }

        return '配列要素を変換できませんでした。';
    }

    public static function for(
        string $class,
        string $apiField,
        string $expected,
        mixed $actual,
        ?\Throwable $previous = null,
    ): self {
        $actualType = \get_debug_type($actual);
        if ($expected === $actualType) {
            return new self(
                \sprintf(
                    '%s の API フィールド『%s』が不正です。%s として扱える値に変換できませんでした。',
                    $class,
                    $apiField,
                    $expected,
                ),
                0,
                $previous,
            );
        }

        return new self(
            \sprintf(
                '%s の API フィールド『%s』が不正です。%s を期待しましたが %s でした。',
                $class,
                $apiField,
                $expected,
                $actualType,
            ),
            0,
            $previous,
        );
    }
}
