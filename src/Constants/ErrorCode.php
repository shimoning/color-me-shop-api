<?php

namespace Shimoning\ColorMeShopApi\Constants;

/**
 * API が返す代表的なエラーコード。
 */
enum ErrorCode: string implements FallbackEnum
{
    case UNAUTHORIZED = '401010'; // 認証または権限のエラー
    case NOT_FOUND = '404100'; // 対象レコードが存在しない
    /**
     * 選択肢にない値を指定した場合のエラー (2026-09-21)。
     *
     * 公式 OpenAPI との差分: コード固有の説明はない。
     *
     * @see docs/api-product-structure.md
     */
    case VALIDATE_ERROR_CHOICE = '422001';
    /**
     * 観測では、顧客のフリガナに数値文字参照を含めて更新したときに返った (2026-09-25)。
     *
     * @see docs/api-customer-structure.md
     */
    case VALIDATE_ERROR_FURIGANA = '422003';
    /** 公式 OpenAPI との差分: コード固有の意味は記載されていない。 */
    case VALIDATE_ERROR_422007 = '422007';
    /**
     * 観測では、カテゴリーの `sort` に負の値を指定したときに返った (2026-09-21)。
     *
     * 公式 OpenAPI との差分: コード固有の説明はない。
     *
     * @see docs/api-product-structure.md
     */
    case VALIDATE_ERROR_RANGE = '422014';
    /** 公式 OpenAPI: バリエーション間で在庫数の設定状態が揃わないエラー。 */
    case VALIDATE_ERROR_STOCK = '422022';
    /**
     * 顧客ポイント増減の `points` が欠落または null の場合のエラー (2026-09-25)。
     *
     * @see docs/api-customer-structure.md
     */
    case VALIDATE_ERROR_FORMAT = '422100';
    case VALIDATE_ERROR_FIELD = '422210'; // 必須パラメータの不足
    /**
     * 観測では、顧客のフリガナに `ヷヸヹヺ` を含めたときに返った (2026-09-25)。
     * message() の X は拒否した文字を表す。
     *
     * @see docs/api-customer-structure.md
     */
    case VALIDATE_ERROR_CHARACTER = '422250';
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
            self::VALIDATE_ERROR_CHOICE->value => '選択肢にない値が指定されています。',
            self::VALIDATE_ERROR_FURIGANA->value => 'フリガナを正しく入力してください。',
            self::VALIDATE_ERROR_422007->value => '入力内容に誤りがあります。',
            self::VALIDATE_ERROR_RANGE->value => '数値が許容範囲外です。',
            self::VALIDATE_ERROR_STOCK->value => 'バリエーションの在庫数をすべて指定してください。',
            self::VALIDATE_ERROR_FORMAT->value => 'リクエストパラメータの形式が不正です。',
            self::VALIDATE_ERROR_FIELD->value => 'パラメータが指定されていません。',
            self::VALIDATE_ERROR_CHARACTER->value => '利用できない文字 X が含まれています。',
            self::INTERNAL_SERVER_ERROR->value => 'Internal Server Error',
            self::UNKNOWN->value => '不明なエラーが発生しました。',
        ];
    }
}
