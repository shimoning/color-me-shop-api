# 在庫 API 応答構造の実測記録

## この文書の位置づけと収集条件

この文書は、在庫 API の Entity 設計のため、実 API の挙動を記録する。現在のライブラリ仕様ではない。
実測の出典はこの文書を追加・更新した各コミットであり、[ADR 0000](adr/0000-record-architecture-decisions.md)
の収集条件・検証可能性の規則に従う。

収集日は **2026-09-28（Asia/Tokyo）**。対象はテスト用ショップ。本ライブラリの `Communicator\Request` で
認証済みの GET を実行した。公式との比較には、同日取得した
[公式 OpenAPI](https://api.shop-pro.jp/v1/spec/open_api.json) の `GET /v1/stocks` の定義を用いた。

掲載しない情報は他の記録と同じ規則に従う。ショップ識別子、アクセストークン、商品の実 ID、商品名、
説明文、画像 URL は載せない。キー、型、`null`・欠損、件数、HTTP status だけを残す。

## 対象 API

`GET /v1/stocks`（`getStocks`）。在庫情報を商品名や型番で検索する。書き込み API はない。
`security` は `[{OAuth2: []}]` で、OAuth2 認証を要求するがスコープの宣言が空である
（[スコープの突合記録](auth-scope-audit.md)）。

## 実行したリクエスト

| HTTP | パラメータ | 件数 | `meta` | 観測目的 |
| ---: | --- | ---: | --- | --- |
| 200 | なし | 2 | `total: 2, limit: 10, offset: 0` | 既定の `limit` |
| 200 | `limit=50` | 2 | `total: 2, limit: 50, offset: 0` | 一覧構造 |
| 200 | `display_state=hidden&limit=50` | 1 | `total: 1` | 状態 filter |
| 200 | `stocks=5&limit=50` | 1 | `total: 1` | 在庫数 filter |
| 200 | `recent_zero_stocks=true&limit=50` | 1 | `total: 1` | 最近更新 filter |
| 200 | `fields=product_id,name,stocks&limit=5` | 2 | `total: 2, limit: 5` | field projection |
| 200 | `limit=100` | 2 | **`limit: 50`** | 上限超過の扱い |

`limit` に上限（50）を超える値を指定してもエラーにならず、`meta.limit` が 50 になる。
`fields` を指定すると各行のキーが指定したものだけになる。

## 応答の構造

トップレベルは `stocks`（配列）と `meta`（`total` / `limit` / `offset`）で、`meta` の形は商品一覧と同じ。

`stocks[]` のキー集合は、`fields` 指定時を除く全 9 行で**公式 OpenAPI の 36 プロパティと完全に一致**した。
欠損も余分なキーもない。

| キー | 観測型 | OpenAPI の nullable | 備考 |
| --- | --- | --- | --- |
| `account_id` | string | 指定なし | |
| `product_id` | integer | 指定なし | |
| `name` | string | 指定なし | |
| `option1_value` / `option2_value` | null | true | 非 null は未観測 |
| `stocks` | integer | true | `null` は未観測 |
| `few_num` | null | true | |
| `model_number` | string, null | true | 両方を観測 |
| `variant_model_number` | string, null | true | 両方を観測 |
| `category` | object | true | 全行で `id_big` / `id_small` を持つ object。`null` は未観測 |
| `display_state` | string | 指定なし | `showing` と `hidden` を観測 |
| `sales_price` / `members_price` | integer, null | true | 両方を観測 |
| `price` / `cost` / `delivery_charge` / `cool_charge` / `min_num` / `max_num` / `weight` / `sort` | null | true | 非 null は未観測 |
| `sale_start_date` / `sale_end_date` | null | true | 非 null は未観測 |
| `unit` | null | true | |
| `soldout_display` | boolean | 指定なし | |
| `simple_expl` / `mobile_expl` / `smartphone_expl` | null | true | |
| `expl` / `memo` | string, null | true | 両方を観測 |
| `make_date` / `update_date` | integer | 指定なし | |
| `image_url` / `thumbnail_image_url` | string, null | true | 両方を観測 |
| `mobile_image_url` | null | true | |
| `images` | 空配列 | 指定なし | **要素の形は未観測** |

`category` と `images[]` の OpenAPI 定義は、`GET /v1/products/{product_id}` の `product.category` /
`product.images[]` と同一である。`display_state` の 4 値も商品と同一である。

## 未観測

- **バリエーション展開。** テスト用ショップの商品にはオプションがなく、`option1_value` / `option2_value` は
  常に `null` で、1 商品 = 1 行だった。オプションごとに在庫管理する商品で 1 商品が複数行になるかは未観測。
- `images[]` の要素。全行で空配列だった。
- `category: null`。全行で object だった。
- 非 null 8 プロパティの `null`。観測しなかった。

## 対象外パラメータの扱い

商品検索専用のパラメータや未知のキーを付けても**拒否されず、黙って無視される**。いずれも HTTP 200 で、
件数はパラメータなしの場合と同じだった。

| パラメータ | 結果 |
| --- | --- |
| `group_ids=999999` | 無視 |
| `stock_managed=false` | 無視 |
| `sort=-price` | 無視 |
| `sales_price_min=99999` | 無視 |
| `unknown_param=1` | 無視 |
| **`display_state=members_only`**（enum にない値） | **無視**（`422` にならない） |

不正な enum 値も無視されるため、API 側の検証には頼れない。この観測が、`Product\SearchParameters` を
流用せず、在庫 API が受け付ける 11 パラメータだけを持つ `Stock\SearchParameters` を設ける根拠である。

## 関連

- [ADR 0002: Entity の null 許容を OpenAPI に合わせる](adr/0002-entity-nullability-from-openapi.md)
- [ADR 0014: 商品書き込み API の入力と空レスポンスを表現する](adr/0014-model-product-write-api.md)（要求側 Entity が有効なフィールド集合を型で表す方針）
- [商品 API 応答構造の実測記録](api-product-structure.md)
- [OAuth スコープと公式 OpenAPI の突合記録](auth-scope-audit.md)
- [公式 OpenAPI](https://api.shop-pro.jp/v1/spec/open_api.json)（2026-09-28 取得）
