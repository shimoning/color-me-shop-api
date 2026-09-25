# ColorMe Shop API 顧客書き込み応答の実測記録

## この文書の位置づけと収集条件

この文書は、顧客書き込み API の Entity 設計と [ADR 0015](adr/0015-model-customer-write-api.md) の判断の
ため、実 API の挙動を記録する。現在のライブラリ仕様ではない。実測の出典はこの文書を追加・更新した
各コミットであり、[ADR 0000](adr/0000-record-architecture-decisions.md) の収集条件・検証可能性の規則に
従う。

収集日は **2026-09-25（Asia/Tokyo）**。対象はテスト用ショップ。本ライブラリの `Communicator\Request`
で認証済みの POST / PUT / GET を実行した。公式との比較には、2026-09-24 に取得した
[公式 OpenAPI](https://api.shop-pro.jp/v1/spec/open_api.json) の顧客の各 request / response スキーマを
用いた。

掲載しない情報は商品の記録と同じ規則に従う。ショップ識別子、アクセストークン、認証ヘッダー、実 ID の
一部、投入した氏名・住所・メールアドレス・電話番号などは載せない。構造判断に必要なキー、型、`null`・
欠損、HTTP status、エラーコード、受理・拒否の別だけを残す。投入した値はいずれも検証用の架空のもので
ある。

顧客を削除する API は公式に存在しないため、この検証で作成した顧客はショップに残る。

## 顧客データの追加（POST /v1/customers）

公式 OpenAPI が required とする `name` / `mail` / `pref_id` / `postal` / `address1` / `tel` だけを
送って `200` を得た。応答のトップレベルは `customer` で、キー集合は単体 GET の `customer` と一致した。

| 条件 | HTTP | エラーコード | `field` |
| --- | ---: | --- | --- |
| required 6項目のみ | 200 | | |
| `mail` を欠く | 422 | 422007 | `customer.email` |
| 空の `customer` | 422 | 422210 | `customer` |
| 範囲外の `pref_id` | 422 | 422001 | `customer.prefecture_id` |

要求のキー名と `field` の名前は一致しない。`mail` の欠落は `customer.email`、`pref_id` の範囲外は
`customer.prefecture_id` として返った。

`add_member` に真を送ると、応答の `member` が真になった。

公式 OpenAPI の作成 request にないキーを同時に送ったところ、`sex` だけが反映され、`tel_mobile` /
`memo` / `points` / `member` / `sales_count` はいずれも無視された（HTTP は `200`）。

## 顧客データの更新（PUT /v1/customers/{customer_id}）

公式 OpenAPI の更新 request には required 指定の子プロパティがないが、実 API は一部を必須とした。
`name` / `mail` / `pref_id` / `postal` / `address1` / `tel` の6項目を送って `200` を得たうえで、
1項目ずつ除いて確認した。

| 除いた項目 | HTTP | エラーコード | `field` |
| --- | ---: | --- | --- |
| `name` | 422 | 422007 | `customer.name` |
| `address1` | 422 | 422007 | `customer.address1` |
| `mail` | 200 | | |
| `pref_id` | 200 | | |
| `postal` | 200 | | |
| `tel` | 200 | | |

必須は `name` と `address1` の2項目だけだった。

部分更新として機能することを確認した。全項目へ値を入れた状態から `name` と `address1` だけを送り、
前後の応答を比較したところ、省略した `mail` / `postal` / `tel` / `pref_id` / `fax` / `other` /
`furigana` / `sex` / `birthday` / `receive_mail_magazine` はいずれも元の値を保持していた。

公式 OpenAPI が nullable とする項目へ明示的な `null` を送ると、`200` でクリアされた。対象は
`furigana` / `address2` / `fax` / `sex` / `birthday` / `hojin` / `busho` / `answer_free_form1` /
`other` である。`receive_mail_magazine` だけは `null` を送ると偽になった。

`tel_mobile` は書き込めなかった。`null` → 値 → 別の値 → `null` の順に送ったが、いずれも HTTP は
`200` で、PUT の応答と直後の GET がともに `null` のままだった。商品の `unlisted` と同じ挙動である。

公式 OpenAPI の更新 request にないキーを1つずつ送った結果は次のとおりで、いずれも HTTP は `200` で
ありながら値は変わらなかった。対象は `tel_mobile` / `memo` / `points` / `member` / `sales_count` /
`add_member` / `make_date` / `update_date` / `pref_name` / `account_id` である。

その他の観測は次のとおり。

| 条件 | HTTP | 結果 |
| --- | ---: | --- |
| 空の `customer` | 422 | code 422210、`field: customer` |
| 存在しない顧客 ID | 404 | code 404100 |
| `sex` に未定義の値 | 422 | `field: customer.sex` |
| `sex` に `not_applicable` | 200 | 反映された |
| `birthday` に `format: date` でない文字列 | 200 | 受理された |

## フリガナの許容文字

公式 OpenAPI のフリガナのパターン `^[ァ-ヶー 　ヷヸヹヺ]*$` が許容する全93文字を、更新の `furigana`
として実 API へ送って確認した。

拒否されたのは `ヷ`（U+30F7）、`ヸ`（U+30F8）、`ヹ`（U+30F9）、`ヺ`（U+30FA）の4文字だけで、いずれも
`422` / code `422250` だった。応答メッセージは拒否した文字を含む。

残る89文字、すなわち `ァ`（U+30A1）から `ヶ`（U+30F6）まで、`ー`（U+30FC）、半角スペース、全角
スペースはすべて `200` で受理された。空文字も `200` で受理された。

実 API が受理する範囲は `^[ァ-ヶー 　]*$` であり、公式 OpenAPI のパターンより狭い。

## ショップポイントの増減（POST /v1/customers/{customer_id}/points）

応答は他の顧客 API と異なり `customer` などのキーで包まれず、`customer_id` と `points` を
トップレベルに持つ。`points` は増減後の保有ポイント数である。

| 送った `points` | HTTP | 結果 |
| --- | ---: | --- |
| 正の整数 | 200 | 加算され、増減後の値が返る |
| 負の整数（残高の範囲内） | 200 | 減算され、増減後の値が返る |
| `0` | 200 | 変化なし |
| 保有ポイントを超える負の整数 | 422 | code 422014、`field: customer.points` |
| キーを含めない | 422 | code 422100、`field: points` |
| `null` | 422 | code 422100、`field: points` |
| 整数の文字列 | 200 | 受理され、加算された |
| （存在しない顧客 ID） | 404 | code 404100 |

公式 OpenAPI はこの操作に `200` しか定義しておらず、失敗応答の定義がない。上記の `422` と `404` は
実測による。`points` は `nullable: false` の integer と定義されているが、実 API は整数の文字列も
受理した。

## 応答の追加観測

顧客応答に `memo` キーが常に存在し、値は空文字だった。公式 OpenAPI の `customer` 応答スキーマには
`memo` の定義がない。書き込みもできなかった。

新規に作成した直後の顧客の応答には `external_accounts` キーがなかった。`membership` は `null` として
存在した。

## 実測で観測したエラーコード

`Constants\ErrorCode` に未登録だったもの。

| コード | メッセージ | 観測条件 |
| --- | --- | --- |
| 422100 | リクエストパラメータの形式が不正です。 | ポイント増減で `points` が欠落または `null` |
| 422250 | 利用できない文字 X が含まれています。 | フリガナに `ヷヸヹヺ` を含めたとき。X は拒否した文字 |

既に登録済みだったもの。

| コード | 観測条件 |
| --- | --- |
| 404100 | 存在しない顧客 ID への更新・ポイント増減 |
| 422001 | 範囲外の `pref_id` での作成 |
| 422007 | 作成・更新での必須項目の欠落 |
| 422014 | 保有ポイントを超えるポイント減算 |
| 422210 | 空の `customer` での作成・更新 |
