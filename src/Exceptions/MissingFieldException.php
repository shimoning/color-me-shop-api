<?php

namespace Shimoning\ColorMeShopApi\Exceptions;

/**
 * API レスポンスにフィールドが存在しない場合の例外。
 */
class MissingFieldException extends ColorMeApiException
{
    public static function for(string $class, string $apiField): self
    {
        return new self(\sprintf(
            '%s の API フィールド『%s』が欠損しています。',
            $class,
            $apiField,
        ));
    }
}
