<?php

namespace Shimoning\ColorMeShopApi\Constants;

enum ErrorCode: string
{
    case UNAUTHORIZED = '401010';
    case NOT_FOUND = '404100';
    // case VALIDATE_ERROR_STOCK   = '422022';
    case VALIDATE_ERROR_FIELD = '422210';

    /**
     * エラーコードをキーにしたメッセージの一覧
     *
     * @return array<string, string>
     */
    static public function message(): array
    {
        return [
            self::UNAUTHORIZED->value => 'このリソースにアクセスできません。有効なアクセストークンが見つからないか、必要なスコープが付与されていません。',
            self::NOT_FOUND->value => 'レコードが見つかりませんでした。',
            // self::VALIDATE_ERROR_STOCK->value => '',
            self::VALIDATE_ERROR_FIELD->value => 'パラメータが指定されていません。',
        ];
    }
}
