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

それぞれに、PHP の `http_build_query()` が配列から作る形式（`ids[0]=1&ids[1]=2`）、カンマ区切り（`ids=1,2`）、
1 件だけの指定を送った。

| API | パラメータ | 配列の形式 | カンマ区切り | 1 件 |
| --- | --- | --- | --- | --- |
| `GET /v1/customers` | `ids`（2 件） | HTTP 500 | HTTP 200、`meta.total=2` | HTTP 200、`meta.total=1` |
| `GET /v1/sales` | `ids`（1 件） | HTTP 500 | HTTP 200、`meta.total=1` | HTTP 200、`meta.total=1` |
| `GET /v1/sales` | `customer_ids`（1 件） | HTTP 500 | HTTP 200、`meta.total=1` | HTTP 200、`meta.total=1` |
| `GET /v1/sales` | `payment_ids`（1 件） | HTTP 500 | HTTP 200、`meta.total=1` | HTTP 200、`meta.total=1` |

HTTP 500 の応答は、いずれも `errors[]` の `code` が `500000`、`message` が `Internal Server Error` だった。

**配列の形式は、4 つの項目すべてで HTTP 500 になった。カンマ区切りは HTTP 200 になった。**

ショップの受注が 1 件だったため、受注の 3 項目は 1 要素の配列とカンマ区切りで確かめた。顧客の `ids` は 2 件で
確かめた。

## 受注一覧の `fields`

公式 OpenAPI の `GET /v1/sales` の `fields` は `type: string` で、「レスポンスJSONのキーをカンマ区切りで指定」と
説明されている。

| 送った形式 | 応答 |
| --- | --- |
| 配列の形式（`fields[0]=id&fields[1]=paid`） | HTTP 200、`meta.total=1`、受注の要素はキーを 1 つも持たなかった |
| カンマ区切り（`fields=id,paid`） | HTTP 200、`meta.total=1`、受注の要素のキーは `id`、`paid` だった |

**配列の形式はエラーにならず、中身のない受注の要素が返った。**

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

- 受注の `ids` / `customer_ids` / `payment_ids` に、2 件以上をカンマ区切りで指定した場合
- カンマの前後に空白を入れた場合や、存在しない ID を含めた場合
- 真偽値に `yes` / `no` などほかの表現を送った場合
- 観測は 1 ショップ、各条件について 1 回である

## 関連

- Issue #105: 顧客・受注一覧の ID 検索条件がカンマ区切りではなく配列形式で送信される
- [ページングの limit の実測記録](api-pagination-limit-observation.md)
