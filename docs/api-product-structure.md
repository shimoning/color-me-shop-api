# ColorMe Shop API 商品応答構造の実測記録

## この文書の位置づけと収集条件

この文書は、商品 API の Entity 設計と [ADR 0012](adr/0012-allow-nullability-from-api-observations.md) による null 許容判断のため、実 API 応答を記録する。現在のライブラリ仕様ではない。実測の出典はこの文書を追加・更新した各コミットであり、[ADR 0000](adr/0000-record-architecture-decisions.md) の収集条件・検証可能性の規則に従う。

読み取り系の収集日は **2026-09-18（Asia/Tokyo）**。対象はテスト用ショップ。本ライブラリの `Communicator\Request::get()` で認証済みの GET のみを実行し、通常商品に加えて、状態、画像、オプション軸、カテゴリー設定が異なるケースを観測した。公式との比較には、同日取得した [公式 OpenAPI](https://api.shop-pro.jp/v1/spec/open_api.json) の `components.schemas.product` と各 GET 応答スキーマを用いた。書き込み系は後述の別条件で収集しており、読み取り観測を未観測の書き込み動作へ一般化しない。

| GET endpoint | parameter | HTTP | 観測目的 |
| --- | --- | ---: | --- |
| `/v1/products` | なし | 200 | 通常一覧と `meta` |
| `/v1/products` | `limit=100` | 200 | `limit` の扱い |
| `/v1/products` | `fields=id,name&limit=100` | 200 | field projection |
| `/v1/products` | `display_state=showing&limit=100` | 200 | 状態 filter |
| `/v1/products` | `display_state=hidden&limit=100` | 200 | 状態 filter |
| `/v1/products` | `display_state=showing_for_members&limit=100` | 200 | 状態 filter |
| `/v1/products` | `display_state=sale_for_members&limit=100` | 200 | 状態 filter |
| `/v1/products/{product_id}` | なし | 200 | 単体構造 |
| `/v1/products/{product_id}/images` | なし | 200 | 画像の専用構造と空配列 |
| `/v1/products/{product_id}/variants` | なし / `limit=100` | 200 | 1軸・2軸と pagination |
| `/v1/products/{product_id}/variants/{variant_id}` | なし | 200 | variant 単体構造 |
| `/v1/product_advertisings` | なし | 200 | 広告用商品構造 |
| `/v1/groups/{group_id}` | なし | 200 | 商品グループ構造 |
| `/v1/products/{product_id}` | 存在しない ID | 404 | エラー構造 |

公開時には `account_id`、商品・カテゴリー・オプション・バリエーション・グループなどの実 ID、画像 URL の実パス、長い説明文を掲載しない。ショップ識別子、ショップ URL、アクセストークン、認証ヘッダー、非公開の生応答もリポジトリへ置かない。構造判断に必要なキー、型、`null`・欠損、空配列、ID のゼロ・非ゼロの関係、HTTP status だけを残す。

## 商品の状態とカテゴリー

`display_state` は `showing`、`hidden`、`showing_for_members`、`sale_for_members` を観測し、filter 付き一覧は指定した状態だけを返した。状態が異なる商品でも、単体 `product` と通常一覧 `products[]` のキー集合、型、`null` の傾向は一致した。

カテゴリー未設定として追加した商品でも `category` キーは存在し、`id_big` が非ゼロ、`id_small` が `0` の object だった。`id_small=0` は観測範囲で小カテゴリー ID がない状態を示した。`id_big` と `id_small` がともに非ゼロのケースも観測したが、`category: null` とカテゴリーキー欠損は未観測である。カテゴリーを全く持たない設定での応答は未検証であり、「完全なカテゴリー未設定なら `category=null`」とは判断できない。

## `product` の全キー

単体 GET のトップレベルは `product`、通常一覧は `products` と `meta` だった。以下は field projection を使わない応答の観測である。「常に存在」はこの観測範囲について述べる。OpenAPI の nullable「未指定」は非 null の実測保証を意味しない。`digital_conent` は OpenAPI にだけある誤記で、実応答のキーではない。

| キー | 観測型 | 観測結果 | OpenAPI 型 / nullable | 差分 |
| --- | --- | --- | --- | --- |
| `account_id` | string | 単体・一覧で常に存在 | string / 未指定 |  |
| `category` | object | 単体・一覧で常に存在 | object / true |  |
| `cool_charge` | null | 常に存在し、`null` | integer / true |  |
| `cost` | integer, null | 常に存在し、両方を観測 | integer / true |  |
| `delivery_charge` | integer, null | 常に存在し、両方を観測 | integer / true |  |
| `digital_conent` | 未観測 | 単体・一覧とも欠損 | boolean / 未指定 | OpenAPI にだけある誤記 |
| `digital_content` | boolean | 単体・一覧で常に存在 | 未記載 | OpenAPI 未記載 |
| `display_state` | string | 単体・一覧で常に存在 | string / 未指定 |  |
| `expl` | null, string | 常に存在し、両方を観測 | string / true |  |
| `few_num` | null | 常に存在し、`null` | integer / true |  |
| `group_ids` | array | 単体・一覧で常に存在 | array<integer> / 未指定 |  |
| `id` | integer | 単体・一覧で常に存在 | integer / 未指定 |  |
| `image_url` | null, string | 常に存在し、両方を観測 | string / true |  |
| `images` | array | 単体・一覧で常に存在 | array<object> / 未指定 |  |
| `make_date` | integer | 単体・一覧で常に存在 | integer / 未指定 |  |
| `max_num` | null | 常に存在し、`null` | integer / true |  |
| `members_price` | integer | 常に存在し、`null` は未観測 | integer / true |  |
| `members_price_including_tax` | integer | 単体・一覧で常に存在 | integer / 未指定 |  |
| `members_price_tax` | integer | 単体・一覧で常に存在 | integer / 未指定 |  |
| `memo` | string | 常に存在し、`null` は未観測 | string / true |  |
| `min_num` | null | 常に存在し、`null` | integer / true |  |
| `mobile_expl` | null | 常に存在し、`null` | string / true |  |
| `mobile_image_url` | null | 常に存在し、`null` | string / true |  |
| `model_number` | null, string | 常に存在し、両方を観測 | string / true |  |
| `name` | string | 単体・一覧で常に存在 | string / 未指定 |  |
| `options` | array | 単体・一覧で常に存在 | array<object> / 未指定 |  |
| `pickups` | array | 単体・一覧で常に存在 | array<object> / 未指定 |  |
| `price` | integer, null | 常に存在し、両方を観測 | integer / true |  |
| `regular_purchase` | boolean | 単体・一覧で常に存在 | boolean / 未指定 |  |
| `sale_end_date` | null | 常に存在し、`null` | integer / true |  |
| `sale_start_date` | null | 常に存在し、`null` | integer / true |  |
| `sales_price` | integer | 常に存在し、読み取り観測では `null` 未観測 | integer / true | 書き込み観測では `null` へのクリアを確認 |
| `sales_price_including_tax` | integer | 単体・一覧で常に存在 | integer / 未指定 |  |
| `sales_price_tax` | integer | 単体・一覧で常に存在 | integer / 未指定 |  |
| `simple_expl` | null | 常に存在し、`null` | string / true |  |
| `smartphone_expl` | null | 常に存在し、`null` | string / true |  |
| `soldout_display` | boolean | 単体・一覧で常に存在 | boolean / 未指定 |  |
| `sort` | null | 常に存在し、`null` | integer / true |  |
| `stock_managed` | boolean | 単体・一覧で常に存在 | boolean / 未指定 |  |
| `stocks` | integer, null | 常に存在し、両方を観測 | integer / true |  |
| `tax_reduced` | boolean | 単体・一覧で常に存在 | boolean / 未指定 |  |
| `thumbnail_image_url` | null, string | 常に存在し、両方を観測 | string / true |  |
| `unavailable_delivery_ids` | array | 単体・一覧で常に存在 | array<integer> / 未指定 |  |
| `unavailable_payment_ids` | array | 単体・一覧で常に存在 | array<integer> / 未指定 |  |
| `unit` | null | 常に存在し、`null` | string / true |  |
| `unlisted` | boolean | 単体・一覧で常に存在 | 未記載 | OpenAPI 未記載 |
| `update_date` | integer | 単体・一覧で常に存在 | integer / 未指定 |  |
| `variants` | array | 単体・一覧で常に存在 | array<object> / 未指定 |  |
| `weight` | null | 常に存在し、`null` | integer / true |  |
| `without_shipping` | boolean | 単体・一覧で常に存在 | boolean / 未指定 |  |

実応答の `digital_content: boolean` と `unlisted: boolean` は OpenAPI に記載がなく、OpenAPI の `digital_conent` は実応答で欠損した。実際に返った同名キーについて、OpenAPI で nullable 指定のない項目に `null` は観測しなかったが、他ショップでも欠損や `null` がないとは断定しない。

## ネストした値

空配列では要素型を実測できないため、OpenAPI 上の要素型と区別する。各表の「常に存在」は、その object が返った観測範囲に限る。

### `category`、ID 配列、画像

| 構造 / キー | 観測型 | 観測結果 | OpenAPI 型 / nullable | 差分 |
| --- | --- | --- | --- | --- |
| `category.id_big` | integer | object 内に常に存在 | integer / 未指定 |  |
| `category.id_small` | integer | object 内に常に存在 | integer / 未指定 |  |
| `group_ids[]` | integer | 非空時に integer を観測 | integer / 未指定 |  |
| `images[].mobile` | boolean | 要素内に常に存在 | boolean / 未指定 |  |
| `images[].position` | integer | 要素内に常に存在 | integer / 未指定 |  |
| `images[].src` | string | 要素内に常に存在 | string / 未指定 |  |
| `unavailable_payment_ids[]` | integer | 非空時に integer を観測 | integer / 未指定 |  |
| `unavailable_delivery_ids[]` | 未観測 | 観測した配列は空。非空は未観測 | integer / 未指定 | 要素型は未実測 |

商品本体と画像専用 GET の画像配列がともに空のケースでは、`image_url` と `thumbnail_image_url` が `null` だった。商品本体の `images` が空でも画像専用 GET が非空になるケースがあり、両者は同じ画像集合ではない。

### `options[]` と `options[].values`

| キー | 観測型 | 観測結果 | OpenAPI 型 / nullable |
| --- | --- | --- | --- |
| `account_id` | string | 常に存在 | string / 未指定 |
| `id` | integer | 常に存在 | integer / 未指定 |
| `make_date` | integer, null | 読み取り観測では integer、書き込み応答では `null` も観測 | integer / 未指定 |
| `name` | string | 常に存在 | string / 未指定 |
| `product_id` | integer | 常に存在 | integer / 未指定 |
| `update_date` | integer | 常に存在 | integer / 未指定 |
| `values` | array | 常に存在 | array<string> / 未指定 |
| `values[]` | string | 非空時に string を観測 | string / 未指定 |

`option.make_date: null` は OpenAPI の integer かつ nullable 指定なしと異なる。

### `variants[]`

| キー | 観測型 | 観測結果 | OpenAPI 型 / nullable |
| --- | --- | --- | --- |
| `account_id` | string | 常に存在 | string / 未指定 |
| `few_num` | integer, null | 両方を観測 | integer / true |
| `id` | integer | 常に存在 | integer / 未指定 |
| `make_date` | integer | 常に存在 | integer / 未指定 |
| `model_number` | string, null | 両方を観測 | string / true |
| `option1` | object | 常に存在し、`null` は未観測 | object / true |
| `option1_value` | string | 常に存在し、`null` は未観測 | string / true |
| `option2` | object, null | 2軸では object、1軸では明示的な `null` | object / true |
| `option2_value` | string, null | 2軸では string、1軸では明示的な `null` | string / true |
| `option_cost` | null | 常に存在し、`null` | integer / true |
| `option_market_price` | null | 常に存在し、`null` | integer / true |
| `option_members_price` | integer | 常に存在し、`null` は未観測 | integer / true |
| `option_members_price_including_tax` | integer | 常に存在 | integer / 未指定 |
| `option_members_price_tax` | integer | 常に存在 | integer / 未指定 |
| `option_price` | integer | 常に存在し、`null` は未観測 | integer / true |
| `option_price_including_tax` | integer | 常に存在 | integer / 未指定 |
| `option_price_tax` | integer | 常に存在 | integer / 未指定 |
| `product_id` | integer | 常に存在 | integer / 未指定 |
| `stocks` | null | 常に存在し、`null` | integer / true |
| `title` | string | 常に存在 | string / 未指定 |
| `update_date` | integer | 常に存在 | integer / 未指定 |
| `weight` | null | 常に存在し、`null` | integer / true |

`option1` と、object である場合の `option2` は、いずれも `id: integer`、`name: string`、`value: string`、`value_id: integer` を常に持った。OpenAPI は `value` と `value_id` を nullable とするが、`null` は未観測だった。

### `pickups[]`

| キー | 観測型 | 観測結果 | OpenAPI 型 / nullable |
| --- | --- | --- | --- |
| `make_date` | integer | 常に存在 | integer / 未指定 |
| `order_num` | null | 常に存在し、`null` | integer / true |
| `pickup_type` | integer | 常に存在 | integer / false |
| `update_date` | integer | 常に存在 | integer / 未指定 |

## 専用 endpoint

### `/v1/products/{product_id}/images`

トップレベルは `product` で、`id: integer` と `images: array` を持った。画像がない場合も `product.images: []` を返した。

| `product.images[]` のキー | 観測型 | 観測結果 | OpenAPI 型 / nullable |
| --- | --- | --- | --- |
| `position` | integer | 要素内に常に存在 | integer / 未指定 |
| `url` | string | 要素内に常に存在 | string / 未指定 |

商品本体の `product.images[]` は `position`、`src`、`mobile` を持つ追加画像の構造で、画像専用 GET は `position` と `url` を持つ。構造だけでなく内容も一対一には対応しないため、別の画像集合として扱う。

### `/v1/products/{product_id}/variants` と `.../variants/{variant_id}`

一覧のトップレベルは `variants` と `meta`、単体は `variant` だった。各 variant は上記 `product.variants[]` と同じキー集合で、1軸商品では一覧・単体とも `option2: null` と `option2_value: null` を観測した。

一覧は既定で `meta.limit=10` を返し、`limit=100` を指定すると `meta.limit=100` になった。商品一覧で同じ指定が `50` へ丸められる挙動とは異なる。

### `/v1/product_advertisings`

トップレベルは `product_advertisings` と `meta` だった。`colors` と `sizes` は空配列だけを観測したため、要素型は未実測である。

| キー | 観測型 | 観測結果 | OpenAPI 型 / nullable |
| --- | --- | --- | --- |
| `account_id` | string | 常に存在 | string / 未指定 |
| `brand` | null | 常に存在し、`null` | string / true |
| `colors` | array | 常に存在し、空配列を観測 | array<string> / 未指定 |
| `condition` | string | 常に存在し、`null` は未観測 | string / true |
| `description` | null | 常に存在し、`null` | string / true |
| `gender` | null | 常に存在し、`null` | string / true |
| `google_product_category` | null | 常に存在し、`null` | string / true |
| `gtin` | null | 常に存在し、`null` | string / true |
| `mpn` | null | 常に存在し、`null` | string / true |
| `product_id` | integer | 常に存在 | integer / 未指定 |
| `sizes` | array | 常に存在し、空配列を観測 | array<string> / 未指定 |

### `/v1/groups/{group_id}`

トップレベルは `group` だった。

| キー | 観測型 | 観測結果 | OpenAPI 型 / nullable |
| --- | --- | --- | --- |
| `account_id` | string | 常に存在 | string / 未指定 |
| `display_state` | string | 常に存在 | string / 未指定 |
| `expl` | string | 常に存在し、`null` は未観測 | string / true |
| `id` | integer | 常に存在 | integer / 未指定 |
| `image_url` | null | 常に存在し、`null` を観測 | string / true |
| `meta_tag` | object, null | object と `null` を観測 | object / true |
| `name` | string | 常に存在 | string / 未指定 |
| `parent_group_id` | integer, null | 両方を観測 | integer / true |
| `sort` | integer | 常に存在し、`null` は未観測 | integer / true |

object の `meta_tag` は `title`、`keywords`、`description` を持ち、各キーの string 値を観測した。`group` 応答は既存の `Product\Group` Entity で構築でき、全 getter が成功した。別日の一覧観測では `meta_tag: null` も確認した。

## 一覧の `meta` と `fields`

商品一覧の `meta` は `total`、`limit`、`offset` を持ち、いずれも integer で、欠損・`null` は未観測だった。パラメータなしでは既定値が返り、`limit=100` では `meta.limit=50` へ丸められた。50より大きいすべてのリクエストで必ず同じ上限になるかは未検証である。

`fields=id,name&limit=100` では、トップレベルは通常と同じ `products` と `meta` で、各商品は `id` と `name` だけを持ち、他の通常商品キーは欠損した。`meta.limit` は `50` だった。projection による欠損を通常応答の nullable 根拠と混同しない。

## 4xx 応答

存在しない商品 ID への GET は HTTP 404 で、`errors` は array、各要素は `code: 404100`、`message: string`、`status: 404` を持ち、欠損・`null` はなかった。[エラー応答の実測記録](api-error-responses.md) の商品 404 と status、code、キー構造、message が一致した。他の 4xx pattern はこの読み取り収集の対象外だった。

## 書き込み系の観測

### 収集条件

収集日は **2026-09-20（Asia/Tokyo）**、対象はテスト用ショップの検証用商品。商品・オプション・オプション値・バリエーションなどの実 ID、検証用の名前、ショップ識別子、アクセストークン、認証ヘッダー、実 URL、画像の実パスは記録しない。POST、PUT、確認用 GET は本ライブラリの `Communicator\Request`、DELETE と multipart は同じ認証条件の HTTP client で実行した。

終了時に商品を `hidden` へ戻し、作成した従属物を削除した。画像は作成に失敗したため残存しない。商品自体には削除 API がないため非表示で残した。商品作成の HTTP status、応答キーと null の集合、画像作成の 401 は最初の実測記録に基づく。

### 商品の作成と更新

`POST /v1/products` は、`product` に `name` だけを指定して HTTP 200 になった。応答はトップレベル `product` object で、直後の単体 GET とキー集合が同一、欠損もなく、構造差は観測しなかった。作成時の `display_state` は `showing` だったため、直後に `hidden` へ更新した。

| `PUT /v1/products/{product_id}` の入力 | HTTP | PUT 応答と直後の GET |
| --- | ---: | --- |
| `display_state: showing` | 200 | ともに `showing` |
| `display_state: hidden` | 200 | ともに `hidden` |
| `display_state: showing_for_members` | 200 | ともに `showing_for_members` |
| `display_state: sale_for_members` | 200 | ともに `sale_for_members` |
| `display_state: members_only` | 422 | `errors[]` を返し、直前の状態を保持 |
| `unlisted: true` | 200 | ともに `unlisted: false` のまま |
| 整数設定後の `sales_price: null` | 200 | ともに明示的な `null` へ変化 |
| `name` だけ | 200 | `name` と `update_date` だけが変化 |
| 空の `product` object | 422 | `errors[]` を返し、既存値を保持 |

実 API が商品で受理する `display_state` は `showing`、`hidden`、`showing_for_members`、`sale_for_members` で、`members_only` は拒否された。これは OpenAPI の商品グループ request の `showing`、`hidden`、`members_only` と異なり、その enum を商品入力へ一般化できない。拒否時の 422 は `code: 422001`、`field: "product.disp_flg"`、`message: string`、`status: 422` を持った。

空の `product` object は 2026-09-21 に `Services\Product::update()` 経由でも 422 となり、`code` は `VALIDATE_ERROR_FIELD`、`field` は `product` だった。直後の GET では既存値が保持され、ライブラリは例外ではなく `Errors` を返した。空入力を送信前に拒否せず、API の検証に委ねる。

`unlisted: true` は成功 status でも無視され、書き込みフィールドとして利用できなかった。`sales_price` は明示的な `null` でクリアでき、`name` だけの更新は全置換ではなく部分更新として動作した。これらを未観測の入力フィールドへ一般化しない。

### 従属物の作成・更新・削除

| 操作 | HTTP | 応答 body |
| --- | ---: | --- |
| `POST /v1/products/{product_id}/options` | 201 | `option` object。`account_id`, `id`, `make_date`, `name`, `product_id`, `update_date`, `values` |
| `POST /v1/products/{product_id}/options/{option_id}/values` | 201 | `option_value` object。`account_id`, `make_date`, `name`, `option_id`, `product_id`, `update_date`, `value_id` |
| `DELETE /v1/products/{product_id}/options/{option_id}/values/{option_value_id}` | 204 | body なし |
| `DELETE /v1/products/{product_id}/options/{option_id}` | 204 | body なし |
| `PUT /v1/products/{product_id}/variants/{variant_id}` | 200 | `variant` object。商品 GET の `variants[]` と同じキー集合 |
| `POST /v1/products/{product_id}/pickups` | 200 | `pickup` object。`account_id`, `make_date`, `order_num`, `pickup_type`, `product_id`, `update_date` |
| `PUT /v1/products/{product_id}/pickups` | 200 | 更新後の同じキー集合の `pickup` object |
| `DELETE /v1/products/{product_id}/pickups/{pickup_type}` | 200 | 空ではなく、削除した `pickup` object |

オプション値の追加に応じてバリエーションが生成された。残る最後のオプション値を直接 DELETE すると 422 になったが、親オプションの DELETE は 204 となり、その後の商品 GET で `options: []` と `variants: []` を確認した。バリエーションの `stocks` 更新は 200 で、PUT 応答と直後の単体 GET の双方へ反映された。ピックアップの DELETE は 200 で、削除した object を返した。

### 画像 API

`POST /v1/products/{product_id}/images` へ仕様で許可された PNG と `position` を multipart 送信したところ、契約プランの制限により HTTP 401 だった。応答は `errors[]` に `code: 401200`、`message: string`、`status: 401` を持った。画像作成の 201、成功時の `product_image`、画像 DELETE の 204、`position` の成功時動作は未観測であり、公式 OpenAPI 定義だけが根拠である。

### エラー応答

存在しない商品への PUT は 404 で、`errors[]` 要素は `code`、`message`、`status` を持った。不正な `display_state` は 422 で `field` が加わった。一方、最後のオプション値の DELETE も 422 だが `field` はなかったため、422 でも `field` は常在しない。

商品 PUT の 404 は [エラー応答の実測記録](api-error-responses.md) の商品 GET 404 と同じ `code: 404100` とキー構造だった。バリデーションの 422 で `field` が付く観測とも整合し、最後のオプション値の DELETE は `field` がない 422 の追加例である。

### 2026-09-21 の追加観測（ライブラリ経由の書き込み smoke test）

対象は同じテスト用ショップの `hidden` にした検証用商品。本ライブラリの `Client` の `updateProduct`、`createProductOption`、`createProductOptionValue`、`updateProductVariant`、`deleteProductOptionValue`、`deleteProductOption`、`createProductPickup`、`updateProductPickup`、`deleteProductPickup`、`createProductImage`、`deleteProductImage` と確認用 GET で実行し、終了時に従属物を削除して商品を初期状態へ戻した。実 ID、ショップ識別子、認証情報、応答本文は記録しない。

| 観測 | 内容 |
| --- | --- |
| オプション作成応答の `make_date` | 201 応答の `option.make_date` は明示的な **`null`** で、直後の商品 GET でも `null`。`update_date` は integer。OpenAPI の `option.make_date` は integer で nullable 指定なし |
| 他の従属物の `make_date` | 同日の `option_value`、`variant`、`pickup` の応答では integer |
| 商品 PUT の `price` | `price: -1` は 422 ではなく 200 で受理・保存され、`price: null` で戻せた。OpenAPI の `price` に `minimum` はない |
| 商品 PUT の `name` | 空文字列は 422 で、`field: "product.name"` を持った |
| 商品 PUT の `sales_price` と `display_state` | `sales_price: null` によるクリアと `showing_for_members` から `hidden` への往復を再確認 |
| バリエーション PUT の `null` | `model_number: null` は 200 で、応答と直後の単体 GET の双方へ反映 |
| オプション削除後の商品 `stocks` | 親オプション削除時、商品在庫が削除前のバリエーション在庫へ置き換わった。商品 PUT で元の値へ戻せた |
| 画像 DELETE | 画像作成が 401 となる同じショップで、存在しない位置の DELETE は 401 ではなく **422**。`errors[]` は `field`、`code`、`message`、`status` を持ち、プラン制限よりバリデーションが先行した。204 は未観測 |

`option.make_date: null` は [ADR 0012](adr/0012-allow-nullability-from-api-observations.md) の実測条件を満たす。`price` の負値は API 側で検証されないため、ライブラリ側でも値域検証を追加しない現行方針の根拠になる。

### 2026-09-21 の追加観測（グループ・カテゴリーの書き込み smoke test）

収集日は **2026-09-21（Asia/Tokyo）**、対象は同じテスト用ショップ。本ライブラリのグループ・大カテゴリー・小カテゴリーの作成・更新メソッドと確認用 GET を使った。入力 Entity が構築時に拒否する `display_state` だけは、`Communicator\Request` で生 JSON を PUT して API 側の受理を確認した。検証用データは `hidden` で残し、実 ID、固有名、ショップ識別子、認証情報、応答本文は記録しない。

#### グループ・カテゴリーの `display_state` の受理値

| 対象 | 受理した値 | 拒否した値 | 観測結果 |
| --- | --- | --- | --- |
| グループ | `showing`, `hidden`, `members_only` | `showing_for_members`, `sale_for_members` | 受理値は 200 で応答と GET に反映。拒否値は 422、`field: "group.display_state"`、message は `showing, hidden, members_only のいずれかを選択してください。`、直前の値を保持 |
| 大カテゴリー | `showing`, `hidden`, `members_only` | `showing_for_members`, `sale_for_members` | 受理値は 200 で応答と GET に反映。拒否値は 422、`field: "product_category.disp_flg"`、message は `Disp flgを正しく選択してください。`、直前の値を保持 |
| 小カテゴリー | `hidden` | 未観測 | OpenAPI は大カテゴリーと同じ定義だが、他の値は送信していない |

グループの受理値は OpenAPI のグループ作成・更新 request enum と一致する。`productGroup` response enum にある `showing_for_members` と `sale_for_members` は書き込みで拒否され、読み取りでも未観測だった。逆に response enum にない `members_only` は GET が返した。当時の `Product\Group` は `ProductDisplayState` の4値しか受理せず、このグループを `InvalidFieldException` で読めなかったが、生 PUT で `hidden` に戻して復旧した。後続実装では `GroupDisplayState` が両定義の和集合を受理し、`GroupInput` は request 側の3値に限定する。カテゴリーの受理値は既存の `CategoryDisplayState` と一致する。

#### `expl` と `meta_tag` の更新

| 対象 | 送信 | 応答と確認 GET |
| --- | --- | --- |
| グループ | `expl` の string 設定後に `expl: null` | 200 で `null` へ戻り、明示 `null` でクリアできた |
| グループ | `meta_tag` の初回設定 | 200 で応答と GET に反映 |
| グループ | 初回設定後の `meta_tag` 更新、子の `null`、`meta_tag: null`、他 field との同時送信 | 200 応答には反映されるが、直後および時間を置いた GET は初回設定値のまま。原因は未解決 |
| 大・小カテゴリー | `expl: null` | 200 だが応答と GET は旧値のままで、**明示 `null` ではクリアされない** |
| 大カテゴリー | `expl: ""` | 200 で空文字列を保存 |
| 大・小カテゴリー | 全子キー設定後、`meta_tag.title` だけを送信 | 200 で `title` は更新され、省略した `keywords` と `description` は **`null`**。部分更新はマージでなく置換 |
| 大・小カテゴリー | `meta_tag` の全子キーを `null` | 200 で全子キーが `null` |
| 大カテゴリー | 作成直後の GET | `meta_tag` キー自体が欠損。一度設定すると、全子キーを `null` に戻しても親キーは残る |

#### エラー応答

| 条件 | HTTP | 応答 |
| --- | ---: | --- |
| グループ・大カテゴリー・小カテゴリー更新とカテゴリー作成の空入力 | 422 | `code: 422210`、`field` は `group` または `category`、`message: "パラメータが指定されていません。"` |
| グループ・大カテゴリー・小カテゴリーの path に存在しない ID | 404 | `code: 404100`、`message: "データが見つかりません。"`、`field` なし |
| 大カテゴリーの `sort: -1` | 422 | `code: 422014`、`field: "product_category.order_num"`、`message: "Order numは0以上の値を入力してください。"` |
| `display_state` の拒否値 | 422 | `code: 422001`、対象 field を持つ |

`422001` は選択肢にない値、`422014` は数値の範囲外で返った。OpenAPI にコード固有の説明はなく、意味は応答 message から読み取った。`ErrorCode` にはこの観測を出典として case を追加した。

### 2026-09-22 の追加観測（管理画面で設定された既存グループの読み取り）

対象は同じテスト用ショップ。オーナーが管理画面で会員限定に設定した既存グループを含む `GET /v1/groups` と `GET /v1/groups/{group_id}` を、本ライブラリの `Communicator\Request::get()` による GET のみで観測した。書き込みは行わず、実 ID、固有名、ショップ識別子、認証情報は記録しない。

一覧と単体の双方で `display_state: members_only` を観測した。一覧には `hidden` と `showing` も存在した。これによりグループの実測語彙 `showing`、`hidden`、`members_only` は管理画面由来の読み取りでも裏付けられた。OpenAPI の response enum にある `showing_for_members` と `sale_for_members` は未観測だが、他ショップや設定で返らないことの証明ではない。同日の商品 GET はこれらの商品用語彙を返し、グループと商品で `display_state` の語彙が異なることも確認した。

一覧要素のキー集合は以前の単体観測と同じだった。`meta_tag` は3子キーが空文字列の object と `null` の両方を観測したため、グループでは object と `null` が起こり得る。これは OpenAPI の `nullable: true` と `Product\Group::getMetaTag()` の nullable 契約に整合する。`parent_group_id` も `null` と integer の両方を観測した。

## 観測できなかったこと

- `category: null`、カテゴリーキー欠損、`id_big=0`。カテゴリーを全く持たない商品の表現。
- `unavailable_delivery_ids` の非空配列と、その要素型。
- `options[].values`、`group_ids`、`unavailable_payment_ids` の `null` 要素や型違い。
- `images` や `variants` の `null`、`variants[].option1: null`。OpenAPI の nullable 記載だけから出現を断定しない。
- `fields` に他のキーを指定した場合、`offset` を変えた場合、商品一覧が上限を超える場合の paging。
- 商品 POST の `name` 以外の field と、商品 PUT で個別に試していない field の受理・無視・検証動作。
- `sales_price`、`price`、variant の `model_number` 以外の nullable field を明示 `null` でクリアできるか。
- 画像作成の 201 と `product_image` の実キー、画像 DELETE の 204、`position` の成功時動作。契約プラン制限により未観測で、画像 DELETE は 422 だけを観測。
- 商品書き込みの別ショップ・別契約プランでの挙動と、同時更新時の競合。
- グループの `meta_tag` が初回設定以後の PUT で GET に反映されない原因。
- グループ・カテゴリーの `image_url` 書き込みと、新規作成の 201 応答。既存の検証用データを再利用したため成功応答は未観測。小カテゴリーの `expl: ""` と `sort` 範囲外も未観測。
- 小カテゴリーの `display_state` に `hidden` 以外を送った場合の挙動。
- グループ更新への `parent_group_id` 送信。OpenAPI request はこの field を持たず `additionalProperties: false` だが、作成・更新共用の `GroupInput` は送信を許す。実 API の挙動は未観測で、利用者に委ねている。
- グループ GET が `showing_for_members` または `sale_for_members` を返す条件。PUT では拒否され、管理画面由来の会員限定グループは `members_only` を返した。

## 現在のライブラリ実装との関係

商品本体、variant、画像、商品広告の Entity は観測時点で未実装だった。`Product\Group` 単体応答は既存 Entity で構築・getter 確認済み。`Category` を大・小カテゴリーへ分割する変更は 0.11.0 で実装済み（[ADR 0010](adr/0010-split-category-into-big-and-small.md)）。`product.category` は ID pair の小さな object で、カテゴリー専用 GET の Entity 構造と同一とは限らない。Entity の型や nullable の採否は設計判断であり、上記の実測事実と区別する。

## 読み取り観測の再収集手順

1. テスト用ショップで `read_products` scope の認証を使い、上表の GET endpoint と query を実行する。書き込みは行わない。
2. 通常一覧と詳細で、全キー、JSON 型、明示 `null`、キー欠損、配列の空・非空を別々に記録する。`display_state` filter と `fields=id,name` の projection は別条件として扱う。
3. 1軸・2軸商品の variant 一覧（既定と `limit=100`）と単体、画像専用 GET、広告一覧、実在グループ、存在しない商品の 404 を確認する。
4. 公開前に実 ID、画像 URL の実パス、長文、認証情報、ショップ識別情報、検証用固有名がないことを確認する。マスク後もキーの有無、型、`null`、空配列、ゼロと非ゼロの関係、HTTP status を保つ。

## 書き込み観測の再収集手順

1. テスト用ショップで検証専用の商品を `name` だけで作成し、直後に `display_state=hidden` へ更新する。他の既存商品は操作しない。
2. 商品の `display_state` の受理値と拒否値、`unlisted`、整数設定後の `sales_price=null`、`name` だけの部分更新を、PUT 応答と直後の GET の組で確認する。
3. オプション、オプション値、variant、pickup を順に作成・更新・削除し、HTTP status、トップレベルキー、object のキー集合、204 の body 不在を記録する。
4. 画像 API を利用できる契約プランでは、仕様で許可された小さな検証画像を作成し、応答を記録して削除する。利用できない場合は 401 と成功系未観測を区別する。
5. グループ・カテゴリーは `hidden` の検証用データを再利用し、`display_state` の受理値、`expl` と `meta_tag` の明示 `null` と部分更新、空入力、存在しない ID、`sort` 範囲外を、PUT 応答と直後の GET の組で確認する。削除 API がないため、終了時に `hidden` へ戻す。
6. 終了時に従属物が残っていないことを GET で確認し、商品を `hidden` にする。公開前に実 ID、ショップ識別情報、認証情報、URL、画像実パス、検証用固有名を除く。

収集時点以降の API 仕様変更は未検証である。再収集した場合は収集日、条件、キーの有無と型、`null`、OpenAPI との差分を新しい観測へ更新する。
