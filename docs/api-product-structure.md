# ColorMe Shop API 商品応答構造の実測記録

## この文書の位置づけと収集条件

この文書は、商品 API の Entity 設計と [ADR 0012](adr/0012-allow-nullability-from-api-observations.md) による null 許容判断のため、実 API 応答を記録する。現在のライブラリ仕様ではない。実測の出典はこの文書を追加したコミットであり、[ADR 0000](adr/0000-record-architecture-decisions.md) の収集条件・検証可能性の規則に従う。

収集日は第1回、第2回とも **2026-09-18（Asia/Tokyo）**。対象はテスト用ショップ。本ライブラリの `Communicator\Request::get()` で、認証済みの **GET のみ**を実行した。第1回は一覧3商品・詳細3商品、第2回は一覧6商品・詳細6商品を取得した。以下の件数・型・`null`・欠損の表は、特記しない限り重点ケースを加えた**第2回**の観測である。同一商品の複数 GET は独立した商品件数へ加算していない。公式との比較には、同日取得した [公式 OpenAPI](https://api.shop-pro.jp/v1/spec/open_api.json) の `components.schemas.product` と各 GET 応答スキーマを用いた。

第2回のリクエストと HTTP ステータスは次のとおり。`<product_id>`、`<variant_id>`、`<group_id>` は実在 ID を伏せた表記、`<nonexistent_id>` は存在しない ID を表す。指定していないクエリパラメータはなし。

| GET エンドポイント | パラメータ | HTTP | 応答件数 |
| --- | --- | ---: | ---: |
| `/v1/products` | なし | 200 | 6 |
| `/v1/products` | `limit=100` | 200 | 6 |
| `/v1/products` | `fields=id,name&limit=100` | 200 | 6 |
| `/v1/products` | `display_state=showing&limit=100` | 200 | 3 |
| `/v1/products` | `display_state=hidden&limit=100` | 200 | 1 |
| `/v1/products` | `display_state=showing_for_members&limit=100` | 200 | 1 |
| `/v1/products` | `display_state=sale_for_members&limit=100` | 200 | 1 |
| `/v1/products/<product_id>` | なし。6商品を各1回 | 200 | 各1 |
| `/v1/products/<product_id>/images` | なし。6商品を各1回 | 200 | 商品順に 0、0、0、1、1、2 |
| `/v1/products/<product_id>/variants` | なし。バリエーションを持つ2商品 | 200 | 2、10 |
| `/v1/products/<product_id>/variants` | `limit=100`。同じ2商品 | 200 | 2、16 |
| `/v1/products/<product_id>/variants/<variant_id>` | なし。上記2商品で各1件 | 200 | 各1 |
| `/v1/product_advertisings` | なし | 200 | 6 |
| `/v1/groups/<group_id>` | なし | 200 | 1 |
| `/v1/products/<nonexistent_id>` | なし | 404 | — |

第1回は `showing` が3件で、`hidden`、`showing_for_members`、`sale_for_members` は各0件だった。第2回には各1件を追加して確認した。第1回の画像専用 GET は3商品で 1、1、2件、第2回は画像0枚の3商品を追加して上表の件数となった。

公開時のマスク方針: `account_id`、商品・カテゴリー・オプション・バリエーション・グループなどの実 ID、画像 URL の実パスは掲載しない。長い説明文も転載せず、値の型と `null`・欠損のみ示す。ショップ識別子、ショップ URL、アクセストークン、認証ヘッダー、非公開の生応答はリポジトリへ置かない。ID のゼロ・非ゼロなど、構造判断に必要な関係だけを記録する。

## 商品の状態とカテゴリー

第2回の6商品では、`display_state` は `showing` 3件、`hidden`、`showing_for_members`、`sale_for_members` が各1件だった。フィルター付き一覧の返却件数も一致した。状態の異なる6件すべてで、単体 `product` と通常一覧 `products[]` のキー集合、型、`null` 件数は一致した。

カテゴリー未設定として追加された商品でも `category` キーは存在し、値は `{"id_big": <非0の整数>, "id_small": 0}` という object だった。第2回の6件ではこの非0/0の組み合わせが5件、非0/非0が1件であり、`category: null` とカテゴリーキー欠損はいずれも0件だった。したがって、この標本から「完全なカテゴリー未設定なら `category=null`」とは判断できない。`id_small=0` は、この標本では小カテゴリー ID がない状態を示す。カテゴリーを全く持たない設定での応答は未検証である。

## `product` の全キー

単体 GET のトップレベルは `product`、通常一覧のトップレベルは `products` と `meta`。以下の表は単体の6件と、`fields` を指定していない通常一覧の6件をそれぞれ集計した。`null/欠損` は各6件中の件数で、`0/0` は両方とも観測していない意味である。OpenAPI の「未指定」は `nullable` 指定がない意味であり、API が非 null を保証するという実測ではない。`digital_conent` は**OpenAPI にだけある誤記**を照合用に載せており、実応答のキーではない。

| キー | 実測型 | 単体 null/欠損 | 一覧 null/欠損 | OpenAPI 型 / nullable | 差分 |
| --- | --- | ---: | ---: | --- | --- |
| `account_id` | string | 0/0 | 0/0 | string / nullable: 未指定 |  |
| `category` | object | 0/0 | 0/0 | object / nullable: true |  |
| `cool_charge` | null | 6/0 | 6/0 | integer / nullable: true |  |
| `cost` | integer, null | 5/0 | 5/0 | integer / nullable: true |  |
| `delivery_charge` | integer, null | 5/0 | 5/0 | integer / nullable: true |  |
| `digital_conent` | 未観測 | 0/6 | 0/6 | boolean / nullable: 未指定 | 実測なし |
| `digital_content` | boolean | 0/0 | 0/0 | 未記載 | OpenAPI 未記載 |
| `display_state` | string | 0/0 | 0/0 | string / nullable: 未指定 |  |
| `expl` | null, string | 4/0 | 4/0 | string / nullable: true |  |
| `few_num` | null | 6/0 | 6/0 | integer / nullable: true |  |
| `group_ids` | array | 0/0 | 0/0 | array<integer> / nullable: 未指定 |  |
| `id` | integer | 0/0 | 0/0 | integer / nullable: 未指定 |  |
| `image_url` | null, string | 3/0 | 3/0 | string / nullable: true |  |
| `images` | array | 0/0 | 0/0 | array<object> / nullable: 未指定 |  |
| `make_date` | integer | 0/0 | 0/0 | integer / nullable: 未指定 |  |
| `max_num` | null | 6/0 | 6/0 | integer / nullable: true |  |
| `members_price` | integer | 0/0 | 0/0 | integer / nullable: true |  |
| `members_price_including_tax` | integer | 0/0 | 0/0 | integer / nullable: 未指定 |  |
| `members_price_tax` | integer | 0/0 | 0/0 | integer / nullable: 未指定 |  |
| `memo` | string | 0/0 | 0/0 | string / nullable: true |  |
| `min_num` | null | 6/0 | 6/0 | integer / nullable: true |  |
| `mobile_expl` | null | 6/0 | 6/0 | string / nullable: true |  |
| `mobile_image_url` | null | 6/0 | 6/0 | string / nullable: true |  |
| `model_number` | null, string | 5/0 | 5/0 | string / nullable: true |  |
| `name` | string | 0/0 | 0/0 | string / nullable: 未指定 |  |
| `options` | array | 0/0 | 0/0 | array<object> / nullable: 未指定 |  |
| `pickups` | array | 0/0 | 0/0 | array<object> / nullable: 未指定 |  |
| `price` | integer, null | 5/0 | 5/0 | integer / nullable: true |  |
| `regular_purchase` | boolean | 0/0 | 0/0 | boolean / nullable: 未指定 |  |
| `sale_end_date` | null | 6/0 | 6/0 | integer / nullable: true |  |
| `sale_start_date` | null | 6/0 | 6/0 | integer / nullable: true |  |
| `sales_price` | integer | 0/0 | 0/0 | integer / nullable: true |  |
| `sales_price_including_tax` | integer | 0/0 | 0/0 | integer / nullable: 未指定 |  |
| `sales_price_tax` | integer | 0/0 | 0/0 | integer / nullable: 未指定 |  |
| `simple_expl` | null | 6/0 | 6/0 | string / nullable: true |  |
| `smartphone_expl` | null | 6/0 | 6/0 | string / nullable: true |  |
| `soldout_display` | boolean | 0/0 | 0/0 | boolean / nullable: 未指定 |  |
| `sort` | null | 6/0 | 6/0 | integer / nullable: true |  |
| `stock_managed` | boolean | 0/0 | 0/0 | boolean / nullable: 未指定 |  |
| `stocks` | integer, null | 5/0 | 5/0 | integer / nullable: true |  |
| `tax_reduced` | boolean | 0/0 | 0/0 | boolean / nullable: 未指定 |  |
| `thumbnail_image_url` | null, string | 3/0 | 3/0 | string / nullable: true |  |
| `unavailable_delivery_ids` | array | 0/0 | 0/0 | array<integer> / nullable: 未指定 |  |
| `unavailable_payment_ids` | array | 0/0 | 0/0 | array<integer> / nullable: 未指定 |  |
| `unit` | null | 6/0 | 6/0 | string / nullable: true |  |
| `unlisted` | boolean | 0/0 | 0/0 | 未記載 | OpenAPI 未記載 |
| `update_date` | integer | 0/0 | 0/0 | integer / nullable: 未指定 |  |
| `variants` | array | 0/0 | 0/0 | array<object> / nullable: 未指定 |  |
| `weight` | null | 6/0 | 6/0 | integer / nullable: true |  |
| `without_shipping` | boolean | 0/0 | 0/0 | boolean / nullable: 未指定 |  |

実応答では `digital_content: boolean` と `unlisted: boolean` が単体・通常一覧の全6件にあり、両方とも公式 OpenAPI には記載がない。逆に公式の `digital_conent` は全件で欠損した。実際に返った同名キーについて、公式で nullable 指定のない項目に `null` は観測しなかった。この6件のみを根拠に、他ショップでも欠損や `null` がないとは断定しない。

## ネストした値

以下は単体 `product` 6件に含まれる配列・object の集計である。表の欠損件数は表題の対象 object に対する件数。空配列では要素の型を実測できないため、OpenAPI 上の要素型と区別する。

### `category`

6 object。`null`・キー欠損は0件。

| キー | 実測型 | null 件数 | 欠損件数 | OpenAPI 型 / nullable | 差分 |
|---|---|---:|---:|---|---|
| `id_big` | integer | 0 | 0 | integer / nullable: 未指定 |  |
| `id_small` | integer | 0 | 0 | integer / nullable: 未指定 |  |

### `group_ids`

配列要素は合計2件。

| キー | 実測型 | null 件数 | 欠損件数 | OpenAPI 型 / nullable | 差分 |
|---|---|---:|---:|---|---|
| `[]` | integer | 0 | 0 | integer / nullable: 未指定 |  |

### `images`

追加画像の object は合計1件。

| キー | 実測型 | null 件数 | 欠損件数 | OpenAPI 型 / nullable | 差分 |
|---|---|---:|---:|---|---|
| `mobile` | boolean | 0 | 0 | boolean / nullable: 未指定 |  |
| `position` | integer | 0 | 0 | integer / nullable: 未指定 |  |
| `src` | string | 0 | 0 | string / nullable: 未指定 |  |

### `options[]`

object は合計3件。オプションを持つ商品は2件。

| キー | 実測型 | null 件数 | 欠損件数 | OpenAPI 型 / nullable | 差分 |
|---|---|---:|---:|---|---|
| `account_id` | string | 0 | 0 | string / nullable: 未指定 |  |
| `id` | integer | 0 | 0 | integer / nullable: 未指定 |  |
| `make_date` | integer | 0 | 0 | integer / nullable: 未指定 |  |
| `name` | string | 0 | 0 | string / nullable: 未指定 |  |
| `product_id` | integer | 0 | 0 | integer / nullable: 未指定 |  |
| `update_date` | integer | 0 | 0 | integer / nullable: 未指定 |  |
| `values` | array | 0 | 0 | array<string> / nullable: 未指定 |  |

### `options[].values`

配列要素は合計10件。

| キー | 実測型 | null 件数 | 欠損件数 | OpenAPI 型 / nullable | 差分 |
|---|---|---:|---:|---|---|
| `[]` | string | 0 | 0 | string / nullable: 未指定 |  |

### `variants[]`

object は合計18件。バリエーションを持つ商品は2件。

| キー | 実測型 | null 件数 | 欠損件数 | OpenAPI 型 / nullable | 差分 |
|---|---|---:|---:|---|---|
| `account_id` | string | 0 | 0 | string / nullable: 未指定 |  |
| `few_num` | integer, null | 17 | 0 | integer / nullable: true |  |
| `id` | integer | 0 | 0 | integer / nullable: 未指定 |  |
| `make_date` | integer | 0 | 0 | integer / nullable: 未指定 |  |
| `model_number` | string | 0 | 0 | string / nullable: true |  |
| `option1` | object | 0 | 0 | object / nullable: true |  |
| `option1_value` | string | 0 | 0 | string / nullable: true |  |
| `option2` | null, object | 2 | 0 | object / nullable: true |  |
| `option2_value` | null, string | 2 | 0 | string / nullable: true |  |
| `option_cost` | null | 18 | 0 | integer / nullable: true |  |
| `option_market_price` | null | 18 | 0 | integer / nullable: true |  |
| `option_members_price` | integer | 0 | 0 | integer / nullable: true |  |
| `option_members_price_including_tax` | integer | 0 | 0 | integer / nullable: 未指定 |  |
| `option_members_price_tax` | integer | 0 | 0 | integer / nullable: 未指定 |  |
| `option_price` | integer | 0 | 0 | integer / nullable: true |  |
| `option_price_including_tax` | integer | 0 | 0 | integer / nullable: 未指定 |  |
| `option_price_tax` | integer | 0 | 0 | integer / nullable: 未指定 |  |
| `product_id` | integer | 0 | 0 | integer / nullable: 未指定 |  |
| `stocks` | null | 18 | 0 | integer / nullable: true |  |
| `title` | string | 0 | 0 | string / nullable: 未指定 |  |
| `update_date` | integer | 0 | 0 | integer / nullable: 未指定 |  |
| `weight` | null | 18 | 0 | integer / nullable: true |  |

### `variants[].option1`

18 object。親の `option1` はすべて object。

| キー | 実測型 | null 件数 | 欠損件数 | OpenAPI 型 / nullable | 差分 |
|---|---|---:|---:|---|---|
| `id` | integer | 0 | 0 | integer / nullable: 未指定 |  |
| `name` | string | 0 | 0 | string / nullable: 未指定 |  |
| `value` | string | 0 | 0 | string / nullable: true |  |
| `value_id` | integer | 0 | 0 | integer / nullable: true |  |

### `variants[].option2`

親の `option2` は2件が `null`、残る16件は object。

| キー | 実測型 | null 件数 | 欠損件数 | OpenAPI 型 / nullable | 差分 |
|---|---|---:|---:|---|---|
| `id` | integer | 0 | 0 | integer / nullable: 未指定 |  |
| `name` | string | 0 | 0 | string / nullable: 未指定 |  |
| `value` | string | 0 | 0 | string / nullable: true |  |
| `value_id` | integer | 0 | 0 | integer / nullable: true |  |

### `pickups[]`

object は合計5件。

| キー | 実測型 | null 件数 | 欠損件数 | OpenAPI 型 / nullable | 差分 |
|---|---|---:|---:|---|---|
| `make_date` | integer | 0 | 0 | integer / nullable: 未指定 |  |
| `order_num` | null | 5 | 0 | integer / nullable: true |  |
| `pickup_type` | integer | 0 | 0 | integer / nullable: false |  |
| `update_date` | integer | 0 | 0 | integer / nullable: 未指定 |  |

### `unavailable_payment_ids`

配列要素は合計1件。

| キー | 実測型 | null 件数 | 欠損件数 | OpenAPI 型 / nullable | 差分 |
|---|---|---:|---:|---|---|
| `[]` | integer | 0 | 0 | integer / nullable: 未指定 |  |

### `unavailable_delivery_ids`

全商品で空配列。要素型は未観測。

| キー | 実測型 | null 件数 | 欠損件数 | OpenAPI 型 / nullable | 差分 |
|---|---|---:|---:|---|---|
| `[]` | 未観測 | — | — | integer / nullable: 未指定 | 要素なし |

`images: []` は6商品のうち5件で、そのうち3件は画像専用 GET も0件だった。この3件では `image_url` と `thumbnail_image_url` も `null` だった。`product.images` に要素があったのは追加画像を持つ1商品だけである。`variants` は2商品にそれぞれ2件・16件あり、1軸の2件では `option2` と `option2_value` が明示的に `null`（キー欠損0件）だった。`unavailable_payment_ids` は1商品で1要素、`unavailable_delivery_ids` は6商品とも空配列だった。

## 専用エンドポイント

### `/v1/products/<product_id>/images`

トップレベルは `product` で、そのキーは `id: integer` と `images: array`。画像0枚の商品でも `product.images: []` が返った。画像のある3商品では合計4要素を観測した。

| `product.images[]` のキー | 実測型 | null | 欠損 | OpenAPI 型 / nullable |
| --- | --- | ---: | ---: | --- |
| `position` | integer | 0/4 | 0/4 | integer / 未指定 |
| `url` | string | 0/4 | 0/4 | string / 未指定 |

商品本体の `product.images[]` は追加画像を示し、要素は `position: integer`、`src: string`、`mobile: boolean` の3キー。画像専用 GET の要素は `position` と `url` の2キーであり、要素数も異なる。商品本体で画像0枚の5商品中2商品には画像専用 GET で各1件があり、別構造・別件数として扱う。

### `/v1/products/<product_id>/variants` と `.../variants/<variant_id>`

一覧のトップレベルは `variants` と `meta`、単体は `variant`。各バリエーションは上の `product.variants[]` と同じ22キー、`option1` / `option2` object は各 `id`、`name`、`value`、`value_id` を持った。1軸商品のバリエーション2件では、一覧・単体とも `option2: null` と `option2_value: null` を観測した。2軸商品の `variants` は全16件で、一覧は既定 `limit=10` で10件・`meta={"total":16,"limit":10,"offset":0}`、`limit=100` で16件・`meta={"total":16,"limit":100,"offset":0}`。1軸商品の一覧は既定・`limit=100` とも2件だった。バリエーション一覧の `limit=100` はそのまま `meta.limit=100` となり、商品一覧の50への丸め込みとは異なった。

### `/v1/product_advertisings`

トップレベルは `product_advertisings` と `meta`。6件を返し、`meta={"total":6,"limit":50,"offset":0}`。各要素の全キーは次のとおり。`colors` と `sizes` は全件空配列だったので、要素の型は未観測である。

| キー | 実測型 | null 件数 | 欠損件数 | OpenAPI 型 / nullable | 差分 |
|---|---|---:|---:|---|---|
| `account_id` | string | 0 | 0 | string / nullable: 未指定 |  |
| `brand` | null | 6 | 0 | string / nullable: true |  |
| `colors` | array | 0 | 0 | array<string> / nullable: 未指定 |  |
| `condition` | string | 0 | 0 | string / nullable: true |  |
| `description` | null | 6 | 0 | string / nullable: true |  |
| `gender` | null | 6 | 0 | string / nullable: true |  |
| `google_product_category` | null | 6 | 0 | string / nullable: true |  |
| `gtin` | null | 6 | 0 | string / nullable: true |  |
| `mpn` | null | 6 | 0 | string / nullable: true |  |
| `product_id` | integer | 0 | 0 | integer / nullable: 未指定 |  |
| `sizes` | array | 0 | 0 | array<string> / nullable: 未指定 |  |

### `/v1/groups/<group_id>`

トップレベルは `group` で1件を取得した。全キーは次のとおり。

| キー | 実測型 | null 件数 | 欠損件数 | OpenAPI 型 / nullable | 差分 |
|---|---|---:|---:|---|---|
| `account_id` | string | 0 | 0 | string / nullable: 未指定 |  |
| `display_state` | string | 0 | 0 | string / nullable: 未指定 |  |
| `expl` | string | 0 | 0 | string / nullable: true |  |
| `id` | integer | 0 | 0 | integer / nullable: 未指定 |  |
| `image_url` | null | 1 | 0 | string / nullable: true |  |
| `meta_tag` | object | 0 | 0 | object / nullable: true |  |
| `name` | string | 0 | 0 | string / nullable: 未指定 |  |
| `parent_group_id` | integer | 0 | 0 | integer / nullable: true |  |
| `sort` | integer | 0 | 0 | integer / nullable: true |  |

`group.meta_tag` は object で、子の全キーは次のとおり。実測した `group` 応答は既存の `Product\Group` Entity で構築でき、全 getter が成功した。

| `group.meta_tag` のキー | 実測型 | null 件数 | 欠損件数 | OpenAPI 型 / nullable |
| --- | --- | ---: | ---: | --- |
| `title` | string | 0 | 0 | string / nullable: true |
| `keywords` | string | 0 | 0 | string / nullable: true |
| `description` | string | 0 | 0 | string / nullable: true |

## 一覧の `meta` と `fields`

商品一覧の `meta` は次の3キーで、調べた3応答では `null`・欠損がなかった。

| キー | 実測型 | null 件数 | 欠損件数 | OpenAPI 型 / nullable |
| --- | --- | ---: | ---: | --- |
| `total` | integer | 0 | 0 | integer / nullable: 未指定 |
| `limit` | integer | 0 | 0 | integer / nullable: 未指定 |
| `offset` | integer | 0 | 0 | integer / nullable: 未指定 |

パラメータなしでは `{"total":6,"limit":10,"offset":0}`、`limit=100` では `{"total":6,"limit":50,"offset":0}` だった。したがって**今回のリクエストでは**商品一覧の `limit=100` が50へ丸められた。50より大きい全リクエストで必ず50になるかは未検証。

`fields=id,name&limit=100` の場合、トップレベルは同じ `products` と `meta`、6件の各商品には `id` と `name` の2キーだけがあり、他の通常商品キーは欠損した。`meta` は `{"total":6,"limit":50,"offset":0}`。上の全キー表はこの射影応答を含めていない。射影による欠損を Entity の通常応答での nullable 根拠と混同しない。

## 4xx 応答

存在しない商品 ID への `GET /v1/products/<nonexistent_id>` は HTTP 404 で、次の応答だった。`errors` は配列、`code` と `status` は integer、`message` は string。`errors[]` のキー欠損・`null` はなかった。

```json
{"errors":[{"code":404100,"message":"データが見つかりません。","status":404}]}
```

[既存のエラー応答記録](api-error-responses.md) の商品404と、ステータス・コード・キー構造・メッセージが一致した。他の4xxパターンは今回の収集対象外だった。

## 観測できなかったこと

- `category: null`、カテゴリーキー欠損、`id_big=0`。全くカテゴリーを持たない商品での表現は未検証。
- `unavailable_delivery_ids` の非空配列と、その要素型の実測。
- `options[].values`、`group_ids`、`unavailable_payment_ids` の `null` 要素や型違い。
- `images` や `variants` の `null` 値、`variants[].option1: null`。OpenAPI の nullable 記載だけから出現を断定しない。
- `fields` に他のキーを指定した場合、`offset` を変えた場合、商品一覧に50件超が存在する場合のページング。
- 書き込み系エンドポイントのリクエスト・応答。今回の GET 観測を作成・更新・削除へ一般化しない。

## 現在のライブラリ実装との関係

商品本体、バリエーション、画像、商品広告の Entity は現時点で未実装である。`Product\Group` 単体応答は既存 Entity で構築・getter 確認済み。`Category` を大・小カテゴリーへ分割する変更は 0.11.0 で実装済み（[ADR 0010](adr/0010-split-category-into-big-and-small.md)）。本記録の `product.category` は ID ペアの小さな object であり、カテゴリー専用 GET の Entity 構造と同一とは限らない。ここから先の Entity の型や nullable の採否は設計判断であり、上記の実測事実と区別する。

## 再収集手順

1. テスト用ショップで `read_products` スコープの認証を使い、上の表の GET エンドポイントとクエリを実行する。書き込みは行わない。
2. 通常一覧と6件の詳細で、全キー、JSON の型、明示的 `null`、キー欠損、各配列の要素数を別々に記録する。`display_state` ごとの一覧と `fields=id,name` の射影を別標本として扱う。
3. バリエーションを持つ1軸・2軸の商品で一覧（既定と `limit=100`）と単体、各商品の画像専用 GET、広告一覧、実在グループ1件、存在しない商品1件の404を確認する。
4. 公開前に `account_id`、全実 ID、画像 URL の実パス、長文、認証情報、ショップ名・URL がないことを確認する。マスク後もキーの有無、型、`null`、空配列、ゼロと非ゼロの関係、HTTP ステータスを保つ。

収集時点以降の API 仕様変更は未検証である。再収集した場合は収集日、条件、件数、全キー統計を新しい観測値で更新する。
