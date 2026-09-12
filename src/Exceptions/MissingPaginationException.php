<?php

namespace Shimoning\ColorMeShopApi\Exceptions;

/**
 * API レスポンスのページネーション情報が欠損している場合の例外。
 *
 * meta キー自体が存在しない場合、または meta 内の必須キー
 * (total / limit / offset) が欠損している場合に getter から投げられる。
 */
class MissingPaginationException extends MissingFieldException
{
    public static function for(string $class, string $apiField): self
    {
        $exception = parent::for($class, $apiField);

        return new self(
            $exception->getMessage(),
            $exception->getCode(),
            $exception->getPrevious(),
        );
    }
}
