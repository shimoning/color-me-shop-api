<?php

namespace Shimoning\ColorMeShopApi\Constants;

enum ErrorCode: string
{
    case UNAUTHORIZED = 401010;
    case NOT_FOUND = 404100;
    // case VALIDATE_ERROR_STOCK   = 422022;
    case VALIDATE_ERROR_FIELD = 422210;

    static public function message()
    {
        return [
            self::UNAUTHORIZED => 'このリソースにアクセスできません。有効なアクセストークンが見つからないか、必要なスコープが付与されていません。',
            self::NOT_FOUND => 'レコードが見つかりませんでした。',
            // self::VALIDATE_ERROR_STOCK => '',
            self::VALIDATE_ERROR_FIELD => 'パラメータが指定されていません。',
        ];
    }
}
