<?php

namespace Shimoning\ColorMeShopApi\Constants;

enum ErrorCode: string
{
    case NOT_FOUND              = 404100;
    // case VALIDATE_ERROR_STOCK   = 422022;
    case VALIDATE_ERROR_FIELD   = 422210;

    static public function message()
    {
        return [
            self::NOT_FOUND => 'レコードが見つかりませんでした。',
            // self::VALIDATE_ERROR_STOCK => '',
            self::VALIDATE_ERROR_FIELD => 'パラメータが指定されていません。',
        ];
    }
}
