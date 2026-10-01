# 配列フィールドの JSON 形状の実測記録

## この文書の位置づけと収集条件

この文書は、Entity の配列フィールドのアノテーションを `list<…>` に統一し、リスト形状の実行時検証を
行わないという [ADR 0018](adr/0018-unify-array-annotations-without-list-validation.md) の判断の
ため、実 API が配列を期待する位置に何を返すかを記録する。現在のライブラリ仕様ではない。実測の出典は
この文書を追加・更新した各コミットであり、[ADR 0000](adr/0000-record-architecture-decisions.md) の
収集条件・検証可能性の規則に従う。

収集日は **2026-09-28（Asia/Tokyo）**。対象はテスト用ショップ。本ライブラリの `Communicator\Request`
で認証済みの GET を実行し、**生のレスポンス文字列**を走査した。JSON をデコードせず、対象キーの直後の
1 文字が `[`（配列）、`{`（オブジェクト）、`n`（null）のどれかを数える方式である。デコード後に
`array_is_list()` で判定すると `{"0":"a","1":"b"}` のようなオブジェクトも list になって区別できない
ため、生文字列で判定した。

対象キーは、`src/Entities` 配下の配列プロパティに対応する API フィールド 24 個。
`Delivery\Weight::$areas` は API のキーではなく `charge_ranges_by_weight` の tuple からライブラリが
組み立てる派生値のため、対象に含めない。

値そのものは一切記録しない。キー、形状、出現回数だけを残す。

## 実行したリクエスト

| HTTP | エンドポイント | パラメータ |
| ---: | --- | --- |
| 200 | `GET /v1/products` | `limit=50` |
| 200 | `GET /v1/products` | `limit=5&fields=id,group_ids,images,options,variants,pickups,unavailable_payment_ids,unavailable_delivery_ids` |
| 200 | `GET /v1/product_advertisings` | `limit=20` |
| 200 | `GET /v1/groups` | |
| 200 | `GET /v1/categories` | |
| 200 | `GET /v1/deliveries` | |
| 200 | `GET /v1/deliveries/date` | |
| 200 | `GET /v1/payments` | |
| 200 | `GET /v1/customers` | `limit=50` |
| 200 | `GET /v1/sales` | `make_date_min=2022-01-01&limit=50` |
| 200 | `GET /v1/sales/{sale_id}` | 上記一覧の 4 件を単体取得 |
| 200 | `GET /v1/customers/{customer_id}` | 上記一覧の先頭 5 件を単体取得 |

受注はテスト用ショップの既定の検索範囲に該当がないため、`make_date_min` に古い日付を指定して取得した。

## 結果

観測できたのは対象 24 キーのうち 22 キー、のべ 149 箇所。**すべて `[`（JSON 配列）で、`{`
（オブジェクト）は 0 箇所**だった。

| キー | 配列 | オブジェクト |
| --- | ---: | ---: |
| `brands` | 1 | 0 |
| `charge_ranges_by_area` | 1 | 0 |
| `charge_ranges_by_price` | 1 | 0 |
| `charge_ranges_by_weight` | 1 | 0 |
| `charge_ranges_max_weight` | 1 | 0 |
| `children` | 4 | 0 |
| `colors` | 7 | 0 |
| `customizations` | 8 | 0 |
| `detail_ids` | 8 | 0 |
| `details` | 8 | 0 |
| `fees` | 2 | 0 |
| `group_ids` | 12 | 0 |
| `images` | 12 | 0 |
| `options` | 12 | 0 |
| `periods` | 1 | 0 |
| `pickups` | 12 | 0 |
| `sale_deliveries` | 8 | 0 |
| `sizes` | 7 | 0 |
| `unavailable_delivery_ids` | 12 | 0 |
| `unavailable_payment_ids` | 13 | 0 |
| `values` | 6 | 0 |
| `variants` | 12 | 0 |

受注の 4 キー（`details` / `sale_deliveries` / `detail_ids` / `customizations`）は、一覧応答と単体応答の
双方で数えたため各 8 箇所になっている。

`detail_ids` については要素の型も確認し、4 要素すべてが整数だった。公式 OpenAPI の
`array` of `integer` と一致する。

## 未観測

- `siblings_sale_ids`: 取得した受注 4 件はいずれも `segment` が `null` で、キー自体が現れなかった。
- `external_accounts`: 顧客 9 件はいずれもキー自体が欠損していた。過去の fixture
  (`tests/Fixtures/customer_response_fields.json`) に配列と `null` の記録がある。

## 公式 OpenAPI との対応

同日に取得した[公式 OpenAPI](https://api.shop-pro.jp/v1/spec/open_api.json)で、対象 24 キーは
すべて `type: array` だった。`type: object` のマップは 1 つもない。JSON 配列は
`json_decode(..., true)` で必ず PHP の list になる。

`charge_ranges_by_price` と `fees` は `array` of `array` of `integer`（2 整数のタプルの配列）、
`charge_ranges_by_weight` は `array` of `array`（要素型の指定なし）、`children` は
`array` of `object`、`detail_ids` は `array` of `integer` である。

## 非 list になる条件

PHP の `json_decode(..., true)` で非 list になるのは、JSON オブジェクトのうち、キーが `0` から
始まる連番の数値文字列でないものである。`{"2":"a"}`（飛び番）、`{"first":"a"}`（文字列キー）、
`{"1":"a","0":"b"}`（順序違い）、混在キーはいずれも非 list になる。一方 `{"0":"a","1":"b"}` は
list になり、`{}` と `[]` も list になる。今回の観測でオブジェクト形状は 0 箇所であり、いずれの
形も実 API からは観測していない。

## 2026-10-01 の追加観測：スカラー配列の要素の型

応答側のスカラー配列のうち、要素の型を実行時に検証していなかった 3 フィールドに検証を加える判断
（Issue #88）のため、要素の型を追加で観測した。

収集日は **2026-10-01（Asia/Tokyo）**。対象と方式は上と同じテスト用ショップ、`Communicator\Request`
による認証済みの GET である。今回は JSON をデコードし、各要素の PHP の型（`get_debug_type()`）を
数えた。値そのものは記録しない。

| HTTP | エンドポイント | パラメータ |
| ---: | --- | --- |
| 200 | `GET /v1/deliveries` | |
| 200 | `GET /v1/products` | `limit=50` |
| 200 | `GET /v1/sales` | `make_date_min=2000-01-01&limit=50` |
| 200 | `GET /v1/sales/{sale_id}` | 上記一覧の 4 件を単体取得 |

| フィールド | 観測した配列 | 要素 |
| --- | ---: | --- |
| `Delivery.unavailable_payment_ids` | 1 | いずれも空配列 |
| `SaleDelivery.detail_ids` | 8 | 8 要素すべて `int` |
| `SaleSegment.siblings_sale_ids` | 0 | 受注 4 件（一覧と単体の計 8 箇所）とも `segment` が `null` で観測できず |
| （比較用）`Product.unavailable_payment_ids` | 7 | 1 要素が `int`、ほか 6 件は空配列 |
| （比較用）`Product.group_ids` | 7 | 2 要素が `int`、ほか 5 件は空配列 |
| （比較用）`Product.unavailable_delivery_ids` | 7 | いずれも空配列 |

観測できた要素はすべて `int` で、公式 OpenAPI の `array` of `integer` と一致した。いずれの配列も
`array_is_list()` が真だった。`int` 以外の要素は 1 つも観測していない。

`Delivery.unavailable_payment_ids` は空配列しか観測できておらず、要素の型は実データで確かめていない。
`siblings_sale_ids` は前回に続き未観測である。受注は既定の検索範囲に該当がないため、前回と同じく
`make_date_min` に古い日付を指定して取得した。

## 関連

- [ADR 0018: 配列フィールドのアノテーションを list に統一し、リスト形状は検証しない](adr/0018-unify-array-annotations-without-list-validation.md)
- Issue #88
- [ADR 0000: アーキテクチャ上の意思決定を記録する](adr/0000-record-architecture-decisions.md)
- [公式 OpenAPI](https://api.shop-pro.jp/v1/spec/open_api.json)（2026-09-28 取得）
