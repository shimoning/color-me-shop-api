<?php

namespace Shimoning\ColorMeShopApi\Constants;

/**
 * API が返す代表的なエラーコード。
 */
enum ErrorCode: string implements FallbackEnum
{
    case UNAUTHORIZED = '401010'; // 認証または権限のエラー
    case NOT_FOUND = '404100'; // 対象レコードが存在しない
    /** 実測で観測。公式 OpenAPI にコード固有の意味の説明なし。message() は汎用文言。 */
    case VALIDATE_ERROR_422007 = '422007';
    /** 公式 OpenAPI: バリエーション間で在庫数の設定状態が揃わないエラー。 */
    case VALIDATE_ERROR_STOCK = '422022';
    case VALIDATE_ERROR_FIELD = '422210'; // 必須パラメータの不足
    /** 実測で観測。応答メッセージは Internal Server Error。 */
    case INTERNAL_SERVER_ERROR = '500000';
    /** API 仕様上の値ではない、未知のコード用の番兵。 */
    case UNKNOWN = '__unknown__';

    public static function fallbackCase(): static
    {
        return self::UNKNOWN;
    }

    /**
     * エラーコードをキーにしたメッセージの一覧
     *
     * 数字のみのコードは PHP の仕様により int キーになり、番兵は string キーになる。
     *
     * @return array<int|string, string>
     */
    static public function message(): array
    {
        return [
            self::UNAUTHORIZED->value => 'このリソースにアクセスできません。有効なアクセストークンが見つからないか、必要なスコープが付与されていません。',
            self::NOT_FOUND->value => 'レコードが見つかりませんでした。',
            self::VALIDATE_ERROR_422007->value => '入力内容に誤りがあります。',
            self::VALIDATE_ERROR_STOCK->value => 'バリエーションの在庫数をすべて指定してください。',
            self::VALIDATE_ERROR_FIELD->value => 'パラメータが指定されていません。',
            self::INTERNAL_SERVER_ERROR->value => 'Internal Server Error',
            self::UNKNOWN->value => '不明なエラーが発生しました。',
        ];
    }
}
