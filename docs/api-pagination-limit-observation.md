# ページングの limit の実測記録

## この文書の位置づけと収集条件

この文書は、ページングする一覧取得 API の `limit` の既定値と上限について、公式 OpenAPI の記述と実 API の挙動を
突き合わせた結果を記録する。現在のライブラリ仕様ではない。実測の出典はこの文書を追加・更新した各コミットで
あり、[ADR 0000](adr/0000-record-architecture-decisions.md) の収集条件・検証可能性の規則に従う。

収集日は **2026-10-07（Asia/Tokyo）**。対象はテスト用ショップ。本ライブラリの `Communicator\Request` で GET だけを
送り、応答の HTTP ステータス、返った要素の件数、`meta` を記録した。商品・受注・顧客などの ID と内容は記録しない。

公式 OpenAPI は `https://api.shop-pro.jp/v1/spec/open_api.json` を同日に取得した。

## 公式 OpenAPI の記述

| API | `limit` の説明 |
| --- | --- |
| `GET /v1/products` | 指定がない場合は 10。最大 50 |
| `GET /v1/stocks` | 指定がない場合は 10。最大 50 |
| `GET /v1/products/{product_id}/variants` | 指定がない場合は 10。最大 50 |
| `GET /v1/sales` | 指定がない場合は 10。最大 100 |
| `GET /v1/customers` | 指定がない場合は 10。最大 100（schema にも `minimum: 1`、`maximum: 100`、`default: 10`） |
| `GET /v1/product_advertisings` | 指定がない場合は 50。最大 250 |

## 観測

各 API に、`limit` を指定しない場合、仕様の上限ちょうど、上限 + 1、`1000` の 4 通りで GET を送った。表の値は
応答の `meta.limit` である。

| API | 指定なし | 上限ちょうど | 上限 + 1 | `limit=1000` |
| --- | ---: | ---: | ---: | ---: |
| `GET /v1/products` | 10 | 50 | 50 | 50 |
| `GET /v1/stocks` | 10 | 50 | 50 | 50 |
| `GET /v1/products/{product_id}/variants` | 10 | 50 | 51 | 100 |
| `GET /v1/sales` | 10 | 100 | 100 | 100 |
| `GET /v1/customers` | 10 | 100 | 100 | 100 |
| `GET /v1/product_advertisings` | 50 | 250 | 250 | 250 |

**上限を超える `limit` は、どの API でも 200 を返し、エラーにならなかった。** `meta.limit` は上限に丸められた。

**バリエーション一覧の上限は、公式 OpenAPI の「最大 50」ではなく 100 だった。** `limit=51` は `meta.limit` が
`51` のまま返り、`limit=1000` は `100` に丸められた。

既定値は、6 つの API すべてで公式 OpenAPI の記述と一致した。

## 確かめていないこと

- 観測に使ったショップの件数は、どの API も全部で 10 件以下だった。返った要素の件数は丸めた後の上限に届かず、
  上限の件数まで実際に要素が返るかは確かめていない。上限は `meta.limit` で判断した
- `limit` に 0 以下の値や整数でない値を送った場合
- バリエーション一覧で、上限 100 が商品やバリエーションの数によって変わるか
- 観測は 1 ショップ、各 API について 1 回である

## 関連

- [ColorMe Shop API 商品応答構造の実測記録](api-product-structure.md)（商品一覧の `limit=100` が 50 に丸められ、
  バリエーション一覧の `limit=100` が `meta.limit=100` で返った以前の観測）
- [ADR 0035: 公式 API にない機能をライブラリで作らない](adr/0035-follow-api-without-client-side-features.md)
