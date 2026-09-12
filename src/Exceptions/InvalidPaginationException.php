<?php

namespace Shimoning\ColorMeShopApi\Exceptions;

/**
 * API レスポンスのページネーション情報の値または型が不正な場合の例外。
 *
 * meta が配列でない場合、または存在する必須値 (total / limit / offset) が
 * null や int 以外の場合に投げられる。
 */
class InvalidPaginationException extends InvalidFieldException
{
    public static function forArrayElement(
        string $class,
        string $apiField,
        string $expected,
        \Throwable $previous,
    ): self {
        return self::fromParent(parent::forArrayElement(
            $class,
            $apiField,
            $expected,
            $previous,
        ));
    }

    public static function for(
        string $class,
        string $apiField,
        string $expected,
        mixed $actual,
        ?\Throwable $previous = null,
    ): self {
        return self::fromParent(parent::for(
            $class,
            $apiField,
            $expected,
            $actual,
            $previous,
        ));
    }

    private static function fromParent(InvalidFieldException $exception): self
    {
        return new self(
            $exception->getMessage(),
            $exception->getCode(),
            $exception->getPrevious(),
        );
    }
}
