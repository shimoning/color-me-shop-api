# ColorMe Shop API エラー応答の実測記録

## この文書の位置づけ

この文書は、ColorMe Shop API の実 API から収集したエラー応答の一次情報を、ライブラリのエラーハンドリング設計と将来の判断の根拠として保存するものであり、現在のライブラリ仕様を記述するものではない。2026-09-12 にテスト用ショップで、認証失敗、存在しないリソース、入力検証、ルーティング、壊れた JSON、OAuth の各条件を収集した。

ここに記録した内容は収集時点の実測結果であり、API 側の仕様変更によって古くなる可能性がある。公開リポジトリには `account_id`、実在 ID、アクセストークン、OAuth の client ID・secret、ショップ名、ショップ URL を掲載しない。エラーの `code`、`message`、`status`、`field` は API の語彙と挙動を示すため保持する。

## エラー応答の基本構造

ColorMe API 本体では、トップレベルの `errors` は配列で、各要素に次のキーが現れた。

| キー | 型 | 観測結果 |
| --- | --- | --- |
| `code` | integer | OAuth を除く ColorMe API 本体のエラーで存在し、OpenAPI と一致した |
| `message` | string | 常に存在した |
| `status` | integer | 常に存在した |
| `field` | string | バリデーションエラーで現れることがある。認証失敗、存在しないリソース、ルーティングエラー、サーバーエラーでは未観測 |

`errors` に複数要素が入る応答も観測した。`field` がない要素は `code`、`message`、`status` を持ったが、特定のキー集合だけに固定して解釈すべきではない。

## 条件別の観測一覧

| 操作と条件 | HTTP | `errors` 要素のキー |
| --- | ---: | --- |
| `GET /v1/shop`、`Authorization` ヘッダーなし | 401 | `code`, `message`, `status` |
| `GET /v1/shop`、値のない Bearer 認証 | 401 | `code`, `message`, `status` |
| `GET /v1/shop`、固定の不正な Bearer トークン | 401 | `code`, `message`, `status` |
| `GET /v1/shop`、Basic 認証のダミー値 | 401 | `code`, `message`, `status` |
| `GET /v1/sales/{sale_id}`、存在しない受注 | 404 | `code`, `message`, `status` |
| `GET /v1/customers/{customer_id}`、存在しない顧客 | 404 | `code`, `message`, `status` |
| `GET /v1/groups/{group_id}`、存在しない商品グループ | 404 | `code`, `message`, `status` |
| `GET /v1/products/{product_id}`、存在しない商品 | 404 | `code`, `message`, `status` |
| `PUT /v1/categories/{category_id}`、存在しない ID と空の `category` object | 422 | `code`, `field`, `message`, `status` |
| 同エンドポイント、存在しない ID と妥当な `name` | 404 | `code`, `message`, `status` |
| `PUT /v1/groups/{group_id}`、存在しない ID と空の `group` object | 404 | `code`, `message`, `status` |
| `PUT /v1/customers/{customer_id}`、存在しない ID と空の `customer` object | 404 | `code`, `message`, `status` |
| `POST /v1/groups`、名前が空 | 422 | `code`, `field`, `message`, `status` |
| `POST /v1/groups`、名前が array | 422 | `code`, `field`, `message`, `status` |
| `POST /v1/groups`、名前が100文字を超過 | 422 | `code`, `field`, `message`, `status` |
| `POST /v1/groups`、空の名前と不正な `display_state` | 422 | `code`, `field`, `message`, `status` |
| `PUT /v1/sales/{sale_id}`、`point_state` が不正な enum 値 | 422 | `code`, `field`, `message`, `status` |
| `PUT /v1/sales/{sale_id}`、途中で切れた JSON body | 500 | `code`, `message`, `status` |
| `PATCH /v1/shop`、未対応の HTTP method | 404 | `code`, `message`, `status` |
| 存在しない固定 API path への GET | 404 | `code`, `message`, `status` |
| `POST /v1/groups`、途中で切れた JSON body | 500 | `code`, `message`, `status` |
| `POST /v1/groups`、必須 root key なし（`{}`） | 422 | `code`, `field`, `message`, `status` |
| `POST /v1/groups`、`group: null` | 422 | `code`, `field`, `message`, `status` |
| `POST /v1/customers`、必須 root key なし（`{}`） | 422 | `code`, `field`, `message`, `status` |
| `POST /v1/categories`、必須 root key なし（`{}`） | 422 | `code`, `field`, `message`, `status` |
| `POST /v1/groups`、`Content-Type: text/plain` と非 JSON body | 422 | `code`, `field`, `message`, `status` |
| `POST /v1/groups`、JSON の root が array（`[]`） | 422 | `code`, `field`, `message`, `status` |
| `POST /oauth/token`、不正なダミークライアントによるトークン交換 | 401 | `errors` キー自体がない |

不正な `point_state` では、単一の応答に `field` 付きのエラー要素が複数含まれた。OAuth を除き、`code`、`message`、`status` の組み合わせと、これに `field` を加えた組み合わせを観測した。OpenAPI が例示する `{field, message}` だけの要素は未観測である。

## 代表的なエラー語彙

| 条件 | `code` | `message` | `status` / `field` |
| --- | ---: | --- | --- |
| 認証失敗 | `401010` | `このリソースにアクセスできません。有効なアクセストークンが見つからないか、必要なスコープが付与されていません。` | `status: 401`、`field` なし |
| 存在しないリソース | `404100` | `データが見つかりません。` | `status: 404`、`field` なし |
| 空の商品グループ名 | `422007` | `Nameを入力してください。` | `status: 422`、`field: "product_group.name"` |
| 壊れた JSON body | `500000` | `Internal Server Error` | `status: 500`、`field` なし |

これらは実測したコード・構造であり、すべての同一 HTTP status が同じ `code` になるとは一般化しない。

## OAuth のエラー形式

`POST /oauth/token` のエラーは ColorMe API 本体の `errors` 配列ではなく、OAuth 2.0 の形式で返った。不正なダミークライアント情報と認可コードを送信したとき、`error: "invalid_client"` と `error_description: "クライアント認証に失敗しました。クライアントIDが正しいかご確認ください。"` を観測した。

この応答を ColorMe API 本体と同じ `errors` 配列として解釈してはならない。ライブラリでは OAuth エラーを専用クラスで扱う。

## 公式 OpenAPI との差分

- OpenAPI は `POST /v1/groups` の 422 応答を `{field, message}` のみと記述するが、実測した該当応答は `code` と `status` も持つ `{code, field, message, status}` だった。
- `{field, message}` だけの要素は未観測だった。
- `getSales`、`getCustomers`、`statSales` では、不正な日付、存在しない日付、範囲外または非数値の `limit`、負の `offset`、不正な enum・boolean・ID、逆転した日付範囲、array の `limit` が HTTP 200 となり、エラーにならなかった。
- 途中で切れた JSON body は、確認した各エンドポイントで HTTP 400 ではなく HTTP 500 になった。
- HTTP 403 は再現できなかった。使用したトークンでは、安全な読み取り・書き込み scope 確認用リクエストが認証エラーではなく 404 または 422 まで到達した。
- OpenAPI のエラー応答スキーマでは、`errors[]` に `required` と `additionalProperties` がないため、各フィールドの必須性や未知フィールドの有無を確定できない。

検索条件について「OpenAPI 上で不正なら 4xx になる」とは仮定できず、エラー要素も特定のキー集合だけに固定して解釈すべきではない。

## ライブラリでの扱い

- `Communicator\Errors` は、`errors` 配列内で object 形状の要素をすべて保持する。既知フィールドの一部に問題があっても要素全体を捨てない。
- `code`、`message`、`status`、`field` はフィールド単位で検証し、不正なフィールドだけを欠損として扱う。未知の追加フィールドがあっても object 自体は保持する。
- 欠損扱いになったフィールドの getter は `MissingFieldException` を送出する。呼び出し側は全応答に同じキーが揃うと仮定しない。
- 構造化できなかった情報や欠損扱いになった値は、`Errors::getResponse()` が保持する元レスポンスの `getRawBody()` で確認できる。
- OAuth 応答は `errors` キーを持たないため、専用クラスで扱う。

## 再収集の概要

再収集はテスト用ショップとダミー認証値を使い、実データの作成、通知、意図しない更新が起きない操作に限定する。認証情報やリクエストの生ダンプは保存しない。

1. `GET /v1/shop` に認証ヘッダーなし、空の Bearer、不正な Bearer、Basic のダミー値を送る。OAuth は `POST /oauth/token` にダミーのクライアント情報と不正な認可コードを送る。
2. 存在しない受注・顧客・商品グループ・商品・カテゴリー ID を取得または更新系 endpoint に指定する。未対応 method と存在しない固定 API path も使う。404 を確認する更新では、入力検証が先行しない妥当で無害な body を使う。
3. 作成 endpoint で必須 root key の欠損、`null`、空・型不正・上限超過の名前、不正な `display_state`、不正な `Content-Type`、array の root を試す。受注更新では状態変更されない不正な enum 値だけを送る。
4. `Content-Type: application/json` で途中までの JSON を商品グループ作成と受注更新へ送る。受注更新側には更新可能な値を含めない。
5. 検索条件では、上記の各種不正値を送り、status とトップレベルキーだけを記録する。成功応答本文は保存しない。
6. 各条件について HTTP status、生レスポンス、JSON parse 結果、`errors` 要素のキーと `code` の型を記録する。公開前に実在 ID、認証情報、クライアント情報、ショップ識別情報が含まれないことを確認する。

API の仕様変更を検知するため、再収集後は条件、キーの有無と型、代表的なエラー語彙、OpenAPI との差分、収集日を新しい観測へ更新する。
