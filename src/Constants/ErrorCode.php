<?php

namespace Shimoning\ColorMeShopApi\Constants;

/**
 * API が返す代表的なエラーコード。
 */
enum ErrorCode: string
{
    case UNAUTHORIZED = '401010'; // 認証または権限のエラー
    case NOT_FOUND = '404100'; // 対象レコードが存在しない
    // case VALIDATE_ERROR_STOCK   = '422022';
    case VALIDATE_ERROR_FIELD = '422210'; // 必須パラメータの不足

    /**
     * エラーコードをキーにしたメッセージの一覧
     *
     * エラーコードは数値のみで構成されるため、PHP の仕様により配列のキーは int になる。
     *
     * @return array<int, string>
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
