# ColorMe Shop API エラー応答の実測記録

## この文書の位置づけ

この文書は、ColorMe Shop API の実 API から収集したエラー応答の一次情報を、ライブラリのエラーハンドリング設計と将来の判断の根拠として保存するものである。2026-09-12 にテスト用ショップで28ケースを収集した。

ここに記録した内容は収集時点の実測結果であり、API 側の仕様変更によって古くなる可能性がある。内容を更新するときは、推測や過去の応答の流用ではなく、テスト用ショップで対象ケースを再収集する必要がある。

公開リポジトリへ保存するため、実在する受注 ID などは `<sale_id>` のようなプレースホルダーへ置換し、アカウント、認証情報、ショップ名、ショップ URL は掲載していない。エラーの `code`、`message`、`status`、`field` は実測値をそのまま記録する。

## エラー応答の基本構造

ColorMe API 本体のエラー応答は、次の構造だった。

```text
{"errors":[{"code":<integer>,"message":<string>,"status":<integer>,"field":<string>}]}
```

- `errors` は配列であり、1つの応答に複数の要素が入る場合がある。
- `code` は integer だった。全28ケース中、OAuth を除く27ケースで確認でき、公式 OpenAPI の記述とも一致する。
- `field` はバリデーションエラーで付与される。認証失敗、存在しないリソース、ルーティングエラー、サーバーエラーでは観測しなかった。
- `field` がない場合、各要素は `code`、`message`、`status` の3キーだった。

## HTTP ステータス別の観測一覧

収集した28ケースの内訳は、401 が5件、404 が9件、422 が12件、500 が2件だった。「キー」は `errors` 配列の各要素が持つキーを示す。

| No. | 操作の概要 | HTTP ステータス | `errors` 要素のキー |
| ---: | --- | ---: | --- |
| 1 | `GET /v1/shop`、`Authorization` ヘッダーなし | 401 | `code`, `message`, `status` |
| 2 | `GET /v1/shop`、値のない Bearer 認証 | 401 | `code`, `message`, `status` |
| 3 | `GET /v1/shop`、固定の不正な Bearer トークン | 401 | `code`, `message`, `status` |
| 4 | `GET /v1/shop`、Basic 認証のダミー値 | 401 | `code`, `message`, `status` |
| 5 | `GET /v1/sales/<nonexistent_sale_id>`、存在しない受注 | 404 | `code`, `message`, `status` |
| 6 | `GET /v1/customers/<nonexistent_customer_id>`、存在しない顧客 | 404 | `code`, `message`, `status` |
| 7 | `GET /v1/groups/<nonexistent_group_id>`、存在しない商品グループ | 404 | `code`, `message`, `status` |
| 8 | `GET /v1/products/<nonexistent_product_id>`、存在しない商品 | 404 | `code`, `message`, `status` |
| 9 | `PUT /v1/categories/<nonexistent_category_id>`、空の `category` オブジェクト | 422 | `code`, `field`, `message`, `status` |
| 10 | `PUT /v1/categories/<nonexistent_category_id>`、妥当な `name` | 404 | `code`, `message`, `status` |
| 11 | `PUT /v1/groups/<nonexistent_group_id>`、空の `group` オブジェクト | 404 | `code`, `message`, `status` |
| 12 | `PUT /v1/customers/<nonexistent_customer_id>`、空の `customer` オブジェクト | 404 | `code`, `message`, `status` |
| 13 | `POST /v1/groups`、商品グループ名が空 | 422 | `code`, `field`, `message`, `status` |
| 14 | `POST /v1/groups`、商品グループ名が配列 | 422 | `code`, `field`, `message`, `status` |
| 15 | `POST /v1/groups`、商品グループ名が100文字を超過 | 422 | `code`, `field`, `message`, `status` |
| 16 | `POST /v1/groups`、空の名前と不正な `display_state` | 422 | `code`, `field`, `message`, `status` |
| 17 | `PUT /v1/sales/<sale_id>`、`point_state` が不正な enum 値 | 422 | `code`, `field`, `message`, `status` |
| 18 | `PUT /v1/sales/<sale_id>`、途中で切れた JSON ボディ | 500 | `code`, `message`, `status` |
| 19 | `PATCH /v1/shop`、未対応の HTTP メソッド | 404 | `code`, `message`, `status` |
| 20 | `GET /v1/definitely-not-an-endpoint-corpus`、存在しない API パス | 404 | `code`, `message`, `status` |
| 21 | `POST /v1/groups`、途中で切れた JSON ボディ | 500 | `code`, `message`, `status` |
| 22 | `POST /v1/groups`、必須ルートキーなし（`{}`） | 422 | `code`, `field`, `message`, `status` |
| 23 | `POST /v1/groups`、`group` が `null` | 422 | `code`, `field`, `message`, `status` |
| 24 | `POST /v1/customers`、必須ルートキーなし（`{}`） | 422 | `code`, `field`, `message`, `status` |
| 25 | `POST /v1/categories`、必須ルートキーなし（`{}`） | 422 | `code`, `field`, `message`, `status` |
| 26 | `POST /v1/groups`、`Content-Type: text/plain` と非 JSON ボディ | 422 | `code`, `field`, `message`, `status` |
| 27 | `POST /v1/groups`、JSON のルート要素が配列（`[]`） | 422 | `code`, `field`, `message`, `status` |
| 28 | `POST /oauth/token`、不正なダミークライアントによるトークン交換 | 401 | 該当なし（`errors` キー自体がない） |

## `errors` 要素のキー組み合わせ

ケース単位の集計は次のとおりだった。

| キーの組み合わせ | ケース数 |
| --- | ---: |
| `code` + `message` + `status` | 15 |
| `code` + `field` + `message` + `status` | 12 |
| `errors` キー自体がない | 1（OAuth） |

`PUT /v1/sales/<sale_id>` の不正な `point_state` では1つの応答に2要素が含まれたため、要素数で数えると `field` 付きは13要素になる。この表は28件という収集ケース数に合わせ、ケース単位で集計している。

## 代表的な生レスポンス

収集時のレスポンスボディを JSON として整形し、Unicode エスケープを日本語へ戻した。値の型も実際の応答どおりである。

### 401: 認証失敗

不正なアクセストークンで `GET /v1/shop` を呼び出した例。

```json
{
  "errors": [
    {
      "code": 401010,
      "message": "このリソースにアクセスできません。有効なアクセストークンが見つからないか、必要なスコープが付与されていません。",
      "status": 401
    }
  ]
}
```

### 404: 存在しないリソース

存在しない受注 ID で `GET /v1/sales/<nonexistent_sale_id>` を呼び出した例。

```json
{
  "errors": [
    {
      "code": 404100,
      "message": "データが見つかりません。",
      "status": 404
    }
  ]
}
```

### 422: `field` 付きのバリデーションエラー

空の商品グループ名で `POST /v1/groups` を呼び出した例。

```json
{
  "errors": [
    {
      "code": 422007,
      "field": "product_group.name",
      "message": "Nameを入力してください。",
      "status": 422
    }
  ]
}
```

### 500: 壊れた JSON ボディ

途中で切れた JSON ボディで `POST /v1/groups` を呼び出した例。

```json
{
  "errors": [
    {
      "code": 500000,
      "message": "Internal Server Error",
      "status": 500
    }
  ]
}
```

## OAuth のエラー形式

`POST /oauth/token` のエラーは ColorMe API 本体の `errors` 配列ではなく、OAuth 2.0 の標準形式で返った。ダミーの `client_id`、`client_secret`、認可コードを送信したときに実際に観測した応答は次のとおりである。

```json
{
  "error": "invalid_client",
  "error_description": "クライアント認証に失敗しました。クライアントIDが正しいかご確認ください。"
}
```

したがって、この応答を ColorMe API 本体と同じ `errors` 配列として解釈してはならない。ライブラリでは OAuth エラーを専用クラスで扱う方針であり、対応は別途進行中である。

## 公式 OpenAPI との差分

実測によって、次の差分または未記載事項が判明した。

- 公式 OpenAPI は `POST /v1/groups` の422応答を `{field, message}` のみと記述しているが、該当する実測応答はすべて `code` と `status` も持つ `{code, field, message, status}` だった。
- `{field, message}` のみの要素は、今回の全28ケースで1件も観測しなかった。
- `getSales`、`getCustomers`、`statSales` に対する不正な検索値23種類は、すべて HTTP 200 で返り、エラーにならなかった。対象には不正な日付、存在しない日付、範囲外または非数値の `limit`、負の `offset`、不正な enum・boolean・ID、逆転した日付範囲、配列の `limit` が含まれる。
- 途中で切れた JSON ボディは、確認した2つのエンドポイントで HTTP 400 ではなく HTTP 500 になった。
- HTTP 403 は再現できなかった。今回のトークンでは、安全な読み取り・書き込みスコープ確認用リクエストが認証エラーではなく404または422まで到達した。
- 公式 OpenAPI のエラー応答スキーマでは、`errors` 配列の `items` に `required` と `additionalProperties` が指定されていない。このため、OpenAPI の記述だけから各フィールドの必須性や未知フィールドの有無を確定できない。

この結果から、検索条件については「OpenAPI 上で不正なら4xxになる」と仮定できず、エラー要素についても特定のキー集合だけに固定して解釈すべきではない。

## ライブラリでの扱い

上記の実測結果を踏まえ、ライブラリ側のエラーハンドリングは次の方針とする。

- `Communicator\Errors` は、`errors` 配列内で object 形状の要素を全件保持する。既知フィールドの一部に問題があっても、要素全体を捨てない。
- `code`、`message`、`status`、`field` はフィールド単位で検証し、型などが不正なフィールドだけを欠損として扱う。未知の追加フィールドがあっても、object 形状自体は保持する。
- 欠損扱いになったフィールドの getter を呼ぶと `MissingFieldException` を送出する。呼び出し側は、すべての実 API 応答に同じキーが揃うと仮定しない。
- `errors` 配列として構造化できなかった情報や、型検証で欠損扱いになった値を確認する必要がある場合は、`Errors::getResponse()` が保持する元レスポンスの生ボディ（`getRawBody()`）を参照できる。
- OAuth の応答は `errors` キーを持たないため、この処理系ではなく専用クラスで扱う。

## 再収集の概要

再収集は必ずテスト用ショップとダミーの認証値を使い、実データの作成、通知、意図しない更新が起きない操作に限定する。認証情報やリクエストの生ダンプは保存しない。

1. 401 は、`GET /v1/shop` に対して `Authorization` ヘッダーなし、値のない Bearer、不正なダミー Bearer、Basic のダミー値をそれぞれ送る。OAuth の401は、`POST /oauth/token` にダミーのクライアント情報と不正な認可コードをフォーム送信する。
2. 404 は、明らかに存在しない受注・顧客・商品グループ・商品・カテゴリー ID を取得または更新系エンドポイントに指定する。加えて、`PATCH /v1/shop` のような未対応メソッドと、固定の存在しない API パスを使う。更新系では空ボディが先に422になる場合があるため、404を確認するときは妥当だが無害な body を使う。
3. 422 は、`POST /v1/groups`、`POST /v1/customers`、`POST /v1/categories` で必須ルートキーを省く。さらに、`group: null`、空・型不正・上限超過の名前、不正な `display_state`、不正な `Content-Type`、配列のルート要素を試す。受注更新では実在 ID を記録せず `<sale_id>` とし、状態変更されない不正な enum 値だけを送る。
4. 500 は、`Content-Type: application/json` を指定し、途中で切れた JSON を `POST /v1/groups` と `PUT /v1/sales/<sale_id>` に送る。受注更新側の壊れた body には更新可能な値を含めない。
5. 検索条件の挙動を再確認するときは、`getSales`、`getCustomers`、`statSales` に不正値23種類を送り、ステータスとトップレベルキーだけを記録する。成功応答の本文にはショップを特定できる情報が含まれ得るため保存しない。
6. 各ケースについて HTTP ステータス、生レスポンスボディ、JSON パース結果、`errors` 要素のキーと `code` の型を記録する。公開用文書へ転記する前に、`account_id`、各種実在 ID、アクセストークン、クライアント ID、シークレット、ショップ名、ショップ URL が含まれていないことを再確認する。

API の仕様変更を検知するため、再収集後はこの文書の件数・キー統計・代表レスポンスを新しい実測値で更新し、収集日も置き換える。
