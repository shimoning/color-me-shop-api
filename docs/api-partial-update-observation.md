# 更新 API で送らなかった項目の扱いの実測記録

## この文書の位置づけと収集条件

この文書は、更新系の API（本ライブラリの `*UpdateInput` と、作成と更新で共用する入力が対応する API）に、項目の一部
だけを送ったとき、送らなかった項目がどう扱われるかを記録する。現在のライブラリ仕様ではない。実測の出典はこの文書を
追加・更新した各コミットであり、[ADR 0000](adr/0000-record-architecture-decisions.md) の収集条件・検証可能性の規則に
従う。

収集日は **2026-10-09（Asia/Tokyo）**。対象はテスト用ショップ。本ライブラリの `Communicator\Request` で JSON の本文を
送った。グループ、大カテゴリー、小カテゴリー、商品（オプションとバリエーションを含む）、顧客は、名前を
「【検証】部分更新 2026-10-09」で始めて新しく作った。商品は非公開（`display_state: hidden`）で作り、顧客のメールアドレスは
`example.com` のものを使った。受注は作成 API を使えないため既存の受注を使い、変えた値は元に戻した。

手順は、対象の全項目（または確かめたい項目）に値を入れたうえで、一部の項目だけを PUT し、PUT の応答と、その後の GET を
送る前の値と比べた。記録したのは項目の名前と、値が変わったかどうかだけで、顧客・受注の値は記録しない。

## PUT の直後の GET

商品の `name` だけを PUT した直後の GET は、更新前の `name` を返した。PUT の応答は更新後の `name` を返し、5 秒後の GET も
更新後の `name` を返した。**PUT の直後の GET は、更新を反映していないことがあった。** そのため以下では、送った項目が
直後の GET に反映されていなかった場合は、10 秒待ってから GET して比べた。顧客と受注は、すべて 10 秒待ってから GET した。

## 結果

| API | 送った項目 | 送らなかった項目 |
| --- | --- | --- |
| `PUT /v1/groups/{id}` | `name` | `expl`、`display_state`、`meta_tag` は変わらなかった |
| `PUT /v1/categories/{id}` | `name` | `expl`、`sort`、`display_state`、`meta_tag` は変わらなかった |
| `PUT /v1/categories/{category_id}/children/{id}` | `name` | `expl`、`sort`、`display_state`、`meta_tag` は変わらなかった |
| `PUT /v1/products/{product_id}` | `name` | 値を持つほかの項目（価格、原価、型番、説明、`display_state`、`stock_managed`、`tax_reduced`、`category`（大・小）、`group_ids`、`variants` など）は変わらなかった |
| `PUT /v1/products/{product_id}` | `variants` に 2 件のうち 1 件だけ（`option1_value` と `stocks`） | 送らなかったバリエーションは消えず、変わらなかった。商品のほかの項目も変わらなかった |
| `PUT /v1/products/{product_id}/variants/{id}` | `stocks` | `few_num`、`model_number`、`weight`、`option_price`、`option_members_price`、`option_market_price`、`option_cost` は変わらなかった |
| `PUT /v1/sales/{sale_id}` | `paid`（今と同じ値） | 受注のほかの項目と、お届け先は変わらなかった |
| `PUT /v1/sales/{sale_id}` | `sale_deliveries` の 1 件の `slip_number` | お届け先のほかの項目は変わらなかった。`tracking_url` は `slip_number` と一緒に変わった |
| `PUT /v1/sales/{sale_id}` | `sale_deliveries` の 1 件の `address1`（今と同じ値） | `address2` を含め、お届け先のほかの項目は変わらなかった |
| `PUT /v1/customers/{customer_id}` | `name`、`address1` | **`address2` は空になった。** ほかの項目は変わらなかった |

`PUT /v1/products/{product_id}/pickups` は、`pickup_type` と `order_num` が必須で、更新できるのは `order_num` だけのため、
送らない項目を作れず確かめなかった。

### 顧客の `address2`

顧客の更新で、`address2` に値がある状態から次を送った。

| 送ったもの | HTTP | `address2` |
| --- | ---: | --- |
| `name`、`address1`（今と同じ値） | 200 | 空になった |
| `name`、`address1`（違う値） | 200 | 空になった |
| `name`、`address1`、`address2` | 200 | 送った値になった |

**顧客の更新では、`address2` を送らないと空になった。`address1` の値を変えたかどうかに関係なかった。** 受注のお届け先では、
`address1` だけを送っても `address2` は変わらなかった。

作成（`POST /v1/customers`）で `address2` を送った顧客は、作成の直後に `address2` を含めずに更新したため、`address2` が空に
なっていた。作成で `address2` が保存されるかは、この更新の前に GET しておらず確かめていない。

### 顧客の必須項目

[ColorMe Shop API 顧客書き込み応答の実測記録](api-customer-structure.md)（2026-09-25）で、顧客の更新は `name` と `address1` が必須だった。
同日に、改めて次を送った。

| 送ったもの | HTTP | `errors[]` |
| --- | ---: | --- |
| `name` だけ | 422 | `code: 422007`、`field: customer.address1`、`message: 市区町村・番地を入力してください。` |
| `address1` だけ | 422 | `code: 422007`、`field: customer.name`、`message: 名前を入力してください。` |
| `mail` だけ | 422 | 上の 2 件の両方 |
| 空の `customer` | 422 | `code: 422210`、`field: customer`、`message: パラメータが指定されていません。` |

**`name` と `address1` は今も必須で、どちらかを欠くと 422 になり、`address2` を含めて何も更新されなかった。**

### 商品の `stocks`

バリエーションのある商品に、商品の `stocks` だけを送ると、HTTP 200 で、PUT の応答と 10 秒後の GET の `stocks` は変わらな
かった。商品の `stocks` は、バリエーションの `stocks` の合計と同じ値だった（7 と 6 で 13）。バリエーションの `stocks` も
変わらなかった。

## 確かめていないこと

- 作成で送った `address2` が保存されるか
- 顧客の `address2` が、ほかの項目を送った場合（`address1` を必ず送るため、`address1` と一緒に送る項目による違い）にも
  同じく空になるか。確かめたのは `name` と `address1` だけを送った場合である
- 顧客のほかの nullable の項目（`address2` 以外）が、送らないと空になるかは、`name` と `address1` だけを送った場合に
  変わらなかったことだけを確かめた
- バリエーションのない商品で、商品の `stocks` を送った場合
- 観測は 1 ショップ、各条件について 1 回である

## 関連

- [ColorMe Shop API 顧客書き込み応答の実測記録](api-customer-structure.md)
- [ColorMe Shop API 商品応答構造の実測記録](api-product-structure.md)
- [ColorMe Shop API カテゴリー応答構造の実測記録](api-category-structure.md)
