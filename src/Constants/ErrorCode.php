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
     * 実測で観測 (2026-09-21、グループ・カテゴリーの書き込み)。公式 OpenAPI にコード固有の説明なし。
     * 応答メッセージは「showing, hidden, members_only のいずれかを選択してください。」
     * (`field: group.display_state`)、「Disp flgを正しく選択してください。」(`field: product_category.disp_flg`)
     * で、いずれも選択肢にない値に対して返った。商品 PUT の不正な `display_state` (2026-09-20) も同コード。
     * 出典: docs/api-product-structure.md の「2026-09-21 の追加観測（グループ・カテゴリーの書き込み smoke test）」。
     */
    case VALIDATE_ERROR_CHOICE = '422001';
    /**
     * 実測で観測 (2026-09-25、顧客のフリガナに数値文字参照を含めて PUT したとき)。
     * 応答メッセージは「フリガナを正しく入力してください。」(`field: customer.furigana`)。
     */
    case VALIDATE_ERROR_FURIGANA = '422003';
    /** 実測で観測。公式 OpenAPI にコード固有の意味の説明なし。message() は汎用文言。 */
    case VALIDATE_ERROR_422007 = '422007';
    /**
     * 実測で観測 (2026-09-21、カテゴリーの `sort: -1`)。公式 OpenAPI にコード固有の説明なし。
     * 応答メッセージは「Order numは0以上の値を入力してください。」(`field: product_category.order_num`) で、
     * 数値の範囲外に対して返った。他のフィールドでの観測はない。
     * 出典: docs/api-product-structure.md の「2026-09-21 の追加観測（グループ・カテゴリーの書き込み smoke test）」。
     */
    case VALIDATE_ERROR_RANGE = '422014';
    /** 公式 OpenAPI: バリエーション間で在庫数の設定状態が揃わないエラー。 */
    case VALIDATE_ERROR_STOCK = '422022';
    /**
     * 実測で観測 (2026-09-25、顧客ポイント増減で `points` が欠落または null)。
     * 応答メッセージは「リクエストパラメータの形式が不正です。」(`field: points`)。
     */
    case VALIDATE_ERROR_FORMAT = '422100';
    case VALIDATE_ERROR_FIELD = '422210'; // 必須パラメータの不足
    /**
     * 実測で観測 (2026-09-25、顧客のフリガナに ヷヸヹヺ を含めたとき)。
     * 応答メッセージは「利用できない文字 ヷ が含まれています。」など、拒否した文字を含む。
     * message() の X は拒否した文字を表す。
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
