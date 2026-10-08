# 一覧取得の検索条件のクエリ形式の実測記録

## この文書の位置づけと収集条件

この文書は、一覧取得 API の検索条件のうち、複数の値を指定する項目と真偽値の項目について、クエリの形式による
実 API の挙動の違いを記録する。Issue #105 の判断のための記録で、現在のライブラリ仕様ではない。実測の出典は
この文書を追加・更新した各コミットであり、[ADR 0000](adr/0000-record-architecture-decisions.md) の収集条件・
検証可能性の規則に従う。

収集日は **2026-10-08（Asia/Tokyo）**。対象はテスト用ショップ。本ライブラリの `Communicator\Request` で GET だけを
送り、応答の HTTP ステータス、`meta.total`、`errors[]` の `code` と `message`、要素のキーの一覧を記録した。検索に
使った顧客・受注・決済の ID は、同じショップの一覧から取得し、記録しない。顧客・受注の内容も記録しない。

公式 OpenAPI は `https://api.shop-pro.jp/v1/spec/open_api.json` を同日に取得した。

## 複数の ID を指定する項目

公式 OpenAPI では、次の項目はいずれも `type: string` で、「カンマ区切りで複数指定可能」と説明されている。

| API | パラメータ |
| --- | --- |
| `GET /v1/customers` | `ids` |
| `GET /v1/sales` | `ids`、`customer_ids`、`payment_ids` |

それぞれに、PHP の `http_build_query()` が配列から作る形式（`ids[0]=1&ids[1]=2`）と、配列でない文字列の形式
（複数件ならカンマ区切りの `ids=1,2`、1 件なら `ids=1`）を送った。

| API | パラメータ | 配列の形式 | 文字列の形式 |
| --- | --- | --- | --- |
| `GET /v1/customers` | `ids`（2 件） | HTTP 500 | カンマ区切りの 2 件で HTTP 200、`meta.total=2`。1 件で HTTP 200、`meta.total=1` |
| `GET /v1/sales` | `ids`（1 件） | HTTP 500 | 1 件で HTTP 200、`meta.total=1` |
| `GET /v1/sales` | `customer_ids`（1 件） | HTTP 500 | 1 件で HTTP 200、`meta.total=1` |
| `GET /v1/sales` | `payment_ids`（1 件） | HTTP 500 | 1 件で HTTP 200、`meta.total=1` |

HTTP 500 の応答は、いずれも `errors[]` の `code` が `500000`、`message` が `Internal Server Error` だった。

**配列の形式は、4 つの項目すべてで HTTP 500 になった。配列でない文字列の形式は HTTP 200 になった。**

上の受注の 3 項目は、日付を指定しない受注一覧に受注が 1 件しか返らなかったため、1 件で確かめた。同日に
`make_date_min=2020-01-01` を指定して過去の受注 5 件を取得し、そこから選んだ 3 件の ID で、複数件の指定も確かめた。
送った GET には同じ `make_date_min` を付けた。「期待した件数」は、取得した 5 件のうち指定した ID に当てはまる受注の
件数である。

| パラメータ（3 件） | 配列の形式 | カンマ区切り | 期待した件数 |
| --- | --- | --- | ---: |
| `ids` | HTTP 500 | HTTP 200、`meta.total=3` | 3 |
| `customer_ids` | HTTP 500 | HTTP 200、`meta.total=3` | 3 |
| `payment_ids` | HTTP 500 | HTTP 200、`meta.total=4` | 4 |

カンマ区切りで返った受注は、いずれも指定した ID に当てはまるものだけだった。**受注の 3 項目も、複数件のカンマ
区切りで正しく絞り込まれた。**

## 受注一覧の `fields`

公式 OpenAPI の `GET /v1/sales` の `fields` は `type: string` で、「レスポンスJSONのキーをカンマ区切りで指定」と
説明されている。

| 送った形式 | 応答 |
| --- | --- |
| 配列の形式（`fields[0]=id&fields[1]=paid`） | HTTP 200、`meta.total=1`、受注の要素はキーを 1 つも持たなかった |
| カンマ区切り（`fields=id,paid`） | HTTP 200、`meta.total=1`、受注の要素のキーは `id`、`paid` だった |

**配列の形式はエラーにならず、中身のない受注の要素が返った。**

## 顧客一覧の `fields`

公式 OpenAPI の `GET /v1/customers` には `fields` パラメータがない（同日に取得した OpenAPI で、`ids`、`name`、`furigana`、
`mail`、`postal`、`tel`、`line_uid`、`membership_id`、`sex`、`member`、`receive_mail_magazine`、`make_date_min`、
`make_date_max`、`update_date_min`、`update_date_max`、`limit`、`offset` の 17 個）。同日に、`limit=2` を付けて次を送った。

| 送った形式 | 応答 |
| --- | --- |
| `fields` を指定しない | HTTP 200、2 件、要素のキーは 29 個 |
| `fields=id,name` | HTTP 200、2 件、要素のキーは `id`、`name` の 2 個 |
| `fields=id` | HTTP 200、2 件、要素のキーは `id` の 1 個 |
| 配列の形式（`fields[0]=id&fields[1]=name`） | HTTP 200、2 件、要素はキーを 1 つも持たなかった |

あわせて、顧客の単体取得（`GET /v1/customers/{customer_id}`）に `fields=id,name` を送ると、HTTP 200 で `customer` の
キーは 2 個だった。

**公式 OpenAPI に記載はないが、実 API の顧客一覧と顧客の単体取得は、カンマ区切りの `fields` で応答のキーを
絞り込んだ。** 配列の形式は、受注一覧の `fields` と同じく、エラーにならず中身のない要素が返った。

## 商品・在庫・バリエーション・商品広告の `fields`

同日に、`limit=2` を付けて、`fields` を指定しない場合、カンマ区切りで指定した場合、配列の形式で指定した場合を
送った。公式 OpenAPI では、商品・在庫・バリエーションの一覧には `fields`（`type: string`、カンマ区切り）があり、
商品広告の一覧にはない。バリエーションは、バリエーションを持つ商品で確かめた。

| API | 指定なしのキー数 | カンマ区切り | 配列の形式 |
| --- | ---: | --- | --- |
| `GET /v1/products` | 49 | `id,name` で `id`、`name` の 2 個 | キーを持たない要素 |
| `GET /v1/stocks` | 36 | `id,name` で `name` の 1 個（在庫の要素は `id` を持たない） | キーを持たない要素 |
| `GET /v1/products/{product_id}/variants` | 22 | `id,title` で `id`、`title` の 2 個 | キーを持たない要素 |
| `GET /v1/product_advertisings` | 11 | `id,product_id` を指定しても 11 個 | 11 個 |

いずれも HTTP 200 だった。**商品・在庫・バリエーションは、カンマ区切りの `fields` で応答のキーを絞り込み、配列の
形式ではキーを持たない要素を返した。商品広告は、`fields` を指定しても応答のキーが変わらなかった。**

## 真偽値の項目

PHP の `http_build_query()` は真偽値を `1` / `0` にする。公式 OpenAPI は次の項目を `type: boolean` としている。
`true`、`false`、`1`、`0` を送り、`meta.total` を比べた。

| API | パラメータ | `true` | `false` | `1` | `0` | 指定なし |
| --- | --- | ---: | ---: | ---: | ---: | ---: |
| `GET /v1/sales` | `paid` | 0 | 1 | 0 | 1 | 1 |
| `GET /v1/sales` | `delivered` | 0 | 1 | 0 | 1 | 1 |
| `GET /v1/sales` | `canceled` | 0 | 1 | 0 | 1 | 1 |
| `GET /v1/sales` | `mobile` | 0 | 1 | 0 | 1 | 1 |
| `GET /v1/customers` | `member` | 2 | 8 | 2 | 8 | 10 |
| `GET /v1/customers` | `receive_mail_magazine` | 2 | 4 | 2 | 4 | 10 |
| `GET /v1/products` | `stock_managed` | 5 | 5 | 5 | 5 | 10 |
| `GET /v1/products` | `recent_zero_stocks` | 0 | 10 | 0 | 10 | 10 |
| `GET /v1/stocks` | `recent_zero_stocks` | 0 | 2 | 0 | 2 | 2 |

**9 つの項目すべてで、`1` は `true` と、`0` は `false` と同じ件数を返した。**

## 確かめていないこと

- カンマの前後に空白を入れた場合や、存在しない ID を含めた場合
- 真偽値に `yes` / `no` などほかの表現を送った場合
- 観測は 1 ショップ、各条件について 1 回である

## 関連

- Issue #105: 顧客・受注一覧の ID 検索条件がカンマ区切りではなく配列形式で送信される
- [ページングの limit の実測記録](api-pagination-limit-observation.md)
