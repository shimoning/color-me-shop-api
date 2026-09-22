# ColorMe Shop API 商品応答構造の実測記録

## この文書の位置づけと収集条件

この文書は、商品 API の Entity 設計と [ADR 0012](adr/0012-allow-nullability-from-api-observations.md) による null 許容判断のため、実 API 応答を記録する。現在のライブラリ仕様ではない。実測の出典はこの文書を追加したコミットであり、[ADR 0000](adr/0000-record-architecture-decisions.md) の収集条件・検証可能性の規則に従う。

読み取り系の収集日は第1回、第2回とも **2026-09-18（Asia/Tokyo）**。対象はテスト用ショップ。本ライブラリの `Communicator\Request::get()` で、認証済みの **GET のみ**を実行した。第1回は一覧3商品・詳細3商品、第2回は一覧6商品・詳細6商品を取得した。以下の件数・型・`null`・欠損の表は、特記しない限り重点ケースを加えた**第2回**の観測である。同一商品の複数 GET は独立した商品件数へ加算していない。公式との比較には、同日取得した [公式 OpenAPI](https://api.shop-pro.jp/v1/spec/open_api.json) の `components.schemas.product` と各 GET 応答スキーマを用いた。書き込み系は後述の「書き込み系の観測」に、別の収集条件と対象範囲を記す。読み取り観測だけを未観測の書き込み動作へ一般化しない。

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

この単体観測では `group.meta_tag` は object 1 件だけだったが、2026-09-22 の `GET /v1/groups` では 5 件中 1 件が `null` だった（後述の「2026-09-22 の追加観測（管理画面で設定された既存グループの読み取り）」）。

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

## 書き込み系の観測

### 収集条件

収集日は **2026-09-20（Asia/Tokyo）**、対象はテスト用ショップ。新規作成した検証用商品1件だけを操作した。商品・オプション・オプション値・バリエーションなどの実 ID は `<product_id>`、`<option_id>`、`<option_value_id>`、`<variant_id>` として扱い、ショップ識別子、アクセストークン、認証ヘッダー、実 URL、画像の実パスは記録しない。POST、PUT と確認用 GET は本ライブラリの `Communicator\Request`、DELETE と multipart は同じ認証条件の HTTP クライアントで実行した。

終了時に検証用商品を `hidden` に戻し、作成したオプション、オプション値、バリエーション、ピックアップを削除した。画像は作成に失敗したため残存しない。商品自体には削除 API がないため、非表示の商品1件だけが残る。最初の一時記録が消去された後は同じ商品を再観測しており、商品を追加作成していない。商品作成の HTTP ステータス、応答キー集合と null 集合、画像作成の 401 は最初の実測記録に基づく。

### 商品の作成と更新

`POST /v1/products` は `{"product":{"name":"<probe_name>"}}` だけで HTTP 200 となった。応答はトップレベル `product` object で、49キーを持つ。直後の単体 GET とキー集合は同一で、欠損はなく、作成応答との差は観測しなかった。作成応答の `display_state` は `showing` だったため、直後に `hidden` へ更新した。

`PUT /v1/products/<product_id>` の観測は次のとおり。

| 送信した `product` | HTTP | PUT 応答と直後の GET |
| --- | ---: | --- |
| `{"display_state":"showing"}` | 200 | 両方とも `showing` |
| `{"display_state":"hidden"}` | 200 | 両方とも `hidden` |
| `{"display_state":"showing_for_members"}` | 200 | 両方とも `showing_for_members` |
| `{"display_state":"sale_for_members"}` | 200 | 両方とも `sale_for_members` |
| `{"display_state":"members_only"}` | 422 | `errors[]` を返し、GET は直前の `hidden` を保持 |
| `{"unlisted":true}` | 200 | 両方とも `unlisted: false` のまま |
| `{"sales_price":null}` | 200 | 事前に整数へ設定した値が、両方で明示的な `null` へ変化 |
| `{"name":"<renamed_probe_name>"}` | 200 | 前後の GET で変化したのは `name` と `update_date` だけ |
| `{}` (空 object) | 422 | `errors[]` を返し、GET は `name` と `hidden` を保持 |

実 API は `display_state` の `showing`、`hidden`、`showing_for_members`、`sale_for_members` を受理し、`members_only` を拒否した。これは OpenAPI の商品グループ作成・更新 request が列挙する `showing`、`hidden`、`members_only` と一致せず、その request 側 enum を商品更新入力へ一般化できない。422 応答は `{"errors":[{"code":422001,"field":"product.disp_flg","message":string,"status":422}]}` の形だった。

空の `product` object (`{"product":{}}`) は HTTP 422 で拒否され、`errors[]` の `code` は `VALIDATE_ERROR_FIELD`、`field` は `product` だった (収集日 **2026-09-21（Asia/Tokyo）**、本ライブラリの `Services\Product::update()` に空の `ProductInput` を渡して観測)。直後の GET では `name` と `display_state: hidden` が保持され、商品の状態は変化しなかった。ライブラリは `Errors` を返し例外は送出しない。空入力の事前拒否はライブラリ側では行わず、API の検証に委ねる。

`unlisted: true` は成功ステータスを返しても値が変化せず、API は入力を黙って無視した。したがって `unlisted` は書き込みフィールドとして利用できない。`sales_price` では整数値を設定した後に明示的な `null` を送ると値をクリアできた。`name` だけの更新では他フィールドが保持されたため、この操作は全置換ではなく部分更新として動作した。これらは実測したフィールドに限る事実であり、他の入力フィールドへ一般化しない。

### 従属物の作成・更新・削除

| 操作 | HTTP | 応答ボディ |
| --- | ---: | --- |
| `POST /v1/products/<product_id>/options` | 201 | `option` object。`account_id`, `id`, `make_date`, `name`, `product_id`, `update_date`, `values` の7キー |
| `POST /v1/products/<product_id>/options/<option_id>/values` | 201 | `option_value` object。`account_id`, `make_date`, `name`, `option_id`, `product_id`, `update_date`, `value_id` の7キー |
| `DELETE /v1/products/<product_id>/options/<option_id>/values/<option_value_id>` | 204 | ボディなし |
| `DELETE /v1/products/<product_id>/options/<option_id>` | 204 | ボディなし |
| `PUT /v1/products/<product_id>/variants/<variant_id>` | 200 | `variant` object。商品 GET の `variants[]` と同じ22キー |
| `POST /v1/products/<product_id>/pickups` | 200 | `pickup` object。`account_id`, `make_date`, `order_num`, `pickup_type`, `product_id`, `update_date` の6キー |
| `PUT /v1/products/<product_id>/pickups` | 200 | 更新後の同じ6キーの `pickup` object |
| `DELETE /v1/products/<product_id>/pickups/<pickup_type>` | 200 | 空ではなく、削除した同じ6キーの `pickup` object |

オプションを値1件で作成するとバリエーション1件が生成され、オプション値を追加すると2件になった。追加した値の DELETE は 204 でボディなしだった。最後の値を直接削除すると 422 になったが、親オプションの DELETE は 204 となり、その後の商品 GET で `options: []` と `variants: []` を確認した。バリエーションの `stocks` 更新は 200 で、PUT 応答と直後の単体 GET の双方で更新値を確認した。ピックアップの DELETE だけは 200 で、削除した object を返した。

### 画像 API

`POST /v1/products/<product_id>/images` へ生成した PNG 1件と `position=0` を multipart 送信したところ、契約プランの制限により HTTP 401 だった。応答は `{"errors":[{"code":401200,"message":string,"status":401}]}` の形である。画像の作成成功、成功時の `product_image`、画像 DELETE、`position` の動作は**未観測**であり、これらについては公式 OpenAPI 定義だけが根拠となる。

### エラー応答

存在しない商品への PUT は HTTP 404 で、`errors[]` の要素は `code`, `message`, `status` を持った。不正な `display_state` と `members_only` は HTTP 422 で、要素に `field` が加わった。一方、最後のオプション値の DELETE も 422 だが、要素は `code`, `message`, `status` だけで `field` はなかった。したがって 422 でも `field` は常に存在するとは限らない。

これらは [既存のエラー応答記録](api-error-responses.md) と整合する。商品 PUT の 404 は同文書の商品 GET 404 と同じ `code: 404100` およびキー構造であり、バリデーションの 422 で `field` が付く観測とも一致した。最後のオプション値の DELETE は、`field` のない 422 の追加例である。

### 2026-09-21 の追加観測（ライブラリ経由の書き込み smoke test）

収集日は **2026-09-21（Asia/Tokyo）**、対象は上記と同じテスト用ショップの既存の検証用商品1件（`hidden`）。本ライブラリの `Client` の書き込み系メソッド（`updateProduct`、`createProductOption`、`createProductOptionValue`、`updateProductVariant`、`deleteProductOptionValue`、`deleteProductOption`、`createProductPickup`、`updateProductPickup`、`deleteProductPickup`、`createProductImage`、`deleteProductImage`）と確認用 GET で実行し、終了時に従属物を削除して商品を初期状態へ戻した。実 ID、ショップ識別子、認証情報、応答本文は記録しない。

| 観測 | 内容 |
| --- | --- |
| オプション作成応答の `make_date` | `POST /v1/products/<product_id>/options` の 201 応答 `option.make_date` は明示的な **`null`** だった。直後の `GET /v1/products/<product_id>` の `options[].make_date` も `null` で、`update_date` は integer。応答 `option` の各キーの型は `id`: integer、`product_id`: integer、`account_id`: string、`name`: string、`values`: string の配列、`make_date`: null、`update_date`: integer。2026-09-20 のオプション作成でも同じ応答で `make_date: null` を記録していたが、上の表にはキー名だけを記していた。公式 OpenAPI の `option.make_date` は integer で nullable 指定がない |
| 他の従属物の `make_date` | 同日の `option_value`（201）、`variant`（200）、`pickup`（200）の各応答では `make_date` は integer だった |
| 商品 PUT の `price` | `{"price":-1}` は HTTP 422 ではなく **200** で受理され、応答と直後の GET で `price: -1` が保存された。`{"price":null}` の PUT で `null` へ戻せた。公式 OpenAPI の `price` には `minimum` がない |
| 商品 PUT の `name` | `{"name":""}` は HTTP 422 で、`errors[]` 要素は `field: "product.name"` を持った |
| 商品 PUT の `sales_price` と `display_state` | 整数設定後の `{"sales_price":null}` によるクリアと、`showing_for_members` → `hidden` の往復を再確認した |
| バリエーション PUT の `null` | `{"model_number":null}` は 200 で、応答と直後の単体 GET の双方で `model_number: null` へ戻った |
| オプション削除後の商品 `stocks` | バリエーションの `stocks` を 3 へ更新した状態で親オプションを削除すると、商品 GET の `stocks` が 0 から 3 へ変化した（バリエーション削除時に商品在庫がバリエーションの値へ置き換わる）。`{"stocks":0}` の PUT で戻した |
| 画像 DELETE | `DELETE /v1/products/<product_id>/images/0` は、画像作成が契約プラン制限で 401 となる同じショップで、401 ではなく **422** だった。`errors[]` 要素は `field` を持ち、`code`、`message`、`status` を含む。プラン制限は DELETE には効かず、バリデーションが先に走る。画像 DELETE の 204 は引き続き未観測 |

`option.make_date` の `null` は、[ADR 0012](adr/0012-allow-nullability-from-api-observations.md) の条件（実 API での明示的な `null` の確認）を満たす。`price` の負値は API 側で検証されないため、ライブラリ側でも値の範囲を検証しない現行方針を維持する根拠になる。

### 2026-09-21 の追加観測（グループ・カテゴリーの書き込み smoke test）

収集日は **2026-09-21（UTC 11:29 頃、Asia/Tokyo では同日 20 時台）**、対象は上記と同じテスト用ショップ。本ライブラリの `Client` の書き込み系メソッド（`createProductGroup`、`updateProductGroup`、`createProductCategory`、`updateProductCategory`、`createProductCategoryChild`、`updateProductCategoryChild`）と確認用 GET で実行し、入力 Entity が構築時に拒否する `display_state` 値だけは `Communicator\Request` で同じエンドポイントへ生 JSON を PUT して API 側の受理を確認した（下表で「生 PUT」と表記）。検証用のグループ 1 件、大カテゴリー 1 件、小カテゴリー 2 件を `hidden` で残した（削除 API がない）。実 ID、ショップ識別子、認証情報、応答本文は記録しない。

#### グループ・カテゴリーの `display_state` の受理値

| 対象 | 送信値 | HTTP | 応答と直後の GET |
| --- | --- | ---: | --- |
| `PUT /v1/groups/<group_id>` | `showing` / `hidden` | 200 | 応答・GET とも送信値 |
| `PUT /v1/groups/<group_id>` | `showing_for_members` / `sale_for_members` | 422 | `{"errors":[{"code":422001,"field":"group.display_state","message":"showing, hidden, members_only のいずれかを選択してください。","status":422}]}`。GET は直前の値を保持 |
| `PUT /v1/groups/<group_id>`（生 PUT） | `members_only` | 200 | 生 GET の `group.display_state` は `members_only` |
| `PUT /v1/categories/<category_id>` | `showing` / `hidden` / `members_only` | 200 | 応答・GET とも送信値 |
| `PUT /v1/categories/<category_id>`（生 PUT） | `showing_for_members` / `sale_for_members` | 422 | `{"errors":[{"code":422001,"field":"product_category.disp_flg","message":"Disp flgを正しく選択してください。","status":422}]}`。GET は直前の値を保持 |

したがって、**グループの `display_state` は `showing`、`hidden`、`members_only` の 3 値**であり、公式 OpenAPI のグループ作成・更新 request の enum と一致する。`productGroup` response の enum が列挙する `showing_for_members` / `sale_for_members` は、書き込みで拒否され、読み取りでも観測しなかった。逆に response の enum にない `members_only` を GET が返すため、当時の `Product\Group`（`ProductDisplayState` の 4 値）は `members_only` のグループを含む `GET /v1/groups` と `GET /v1/groups/<group_id>` を `InvalidFieldException` で読めなかった（本 smoke test で再現し、生 PUT で `hidden` に戻して復旧した）。カテゴリーの 3 値は大カテゴリーで観測し、既存の `CategoryDisplayState` と一致する。小カテゴリー（`PUT /v1/categories/<category_id>/children/<child_id>`）は `hidden` の送信だけを観測した（`showing` / `members_only` と 422 になる値は未観測。OpenAPI の定義は大カテゴリーと同一）。

#### `expl` と `meta_tag` の更新

| 対象 | 送信した内容 | HTTP | 応答と直後の GET（2 秒後にも再確認） |
| --- | --- | ---: | --- |
| グループ | `{"expl":"<text>"}` → `{"expl":null}` | 200 | 応答・GET とも `null` へ戻った（明示 `null` でクリアできる） |
| グループ | `meta_tag` の初回設定（`null` → `title` / `keywords` / `description`） | 200 | 応答・GET とも送信値 |
| グループ | 初回設定後の `meta_tag` 更新（`title` のみ、3 キーとも新値、3 キーとも `null`、`meta_tag: null`、`expl` や `name` との同時送信） | 200 | **応答には反映されるが、直後および数分後の GET では初回設定の値のまま**。API 側の挙動と考えられるが原因は未解決 |
| 大カテゴリー・小カテゴリー | `{"expl":null}` | 200 | 応答・GET とも**旧値のまま**（明示 `null` ではクリアされない） |
| 大カテゴリー | `{"expl":""}` | 200 | 応答・GET とも空文字（空文字は保存される） |
| 大カテゴリー・小カテゴリー | `meta_tag` を 3 キーで設定した後に `{"meta_tag":{"title":"<new>"}}` | 200 | 応答・GET とも `title` は新値で `keywords` / `description` は **`null`**（部分更新はマージではなく置換） |
| 大カテゴリー・小カテゴリー | `{"meta_tag":{"title":null,"keywords":null,"description":null}}` | 200 | 応答・GET とも 3 キーとも `null` |
| 大カテゴリー | 作成直後の GET | — | `meta_tag` キー自体が無い（`getMetaTag()` は `null`）。一度 `meta_tag` を設定すると、3 キーとも `null` に戻してもキーが残る |

#### エラー応答

| 操作 | HTTP | 応答 |
| --- | ---: | --- |
| `PUT /v1/groups/<group_id>`、`PUT /v1/categories/<category_id>`、`PUT /v1/categories/<category_id>/children/<child_id>`、`POST /v1/categories` の空入力 `{"group":{}}` / `{"category":{}}` | 422 | `code: 422210`、`field: "group"` または `"category"`、`message: "パラメータが指定されていません。"` |
| 存在しない ID への `PUT /v1/groups/<id>`、`PUT /v1/categories/<id>`、`PUT /v1/categories/<category_id>/children/<id>`、`PUT /v1/categories/<id>/children/<child_id>` | 404 | `code: 404100`、`message: "データが見つかりません。"`、`field` なし |
| `PUT /v1/categories/<category_id>` の `{"sort":-1}` | 422 | `code: 422014`、`field: "product_category.order_num"`、`message: "Order numは0以上の値を入力してください。"` |
| 上表の `display_state` 不正値 | 422 | `code: 422001`、`field` は `group.display_state` / `product_category.disp_flg` |

`422001` は選択肢にない値、`422014` は数値の範囲外に対して返った。いずれも公式 OpenAPI にコード固有の説明はなく、意味は応答メッセージから読み取ったものである。`ErrorCode` へはこの観測を出典として case を追加した。

### 2026-09-22 の追加観測（管理画面で設定された既存グループの読み取り）

収集日は **2026-09-22（Asia/Tokyo）**、対象は上記と同じテスト用ショップ。オーナーが管理画面で「会員限定」に設定したグループ 1 件を含む既存の全 5 グループを、本ライブラリの `Communicator\Request::get()` による **GET のみ**（`GET /v1/groups` と `GET /v1/groups/<group_id>`）で観測した。書き込みは行っていない。実 ID、ショップ識別子、認証情報は記録しない。

#### `display_state` の応答値

| 対象 | HTTP | 応答 |
| --- | ---: | --- |
| `GET /v1/groups`（パラメータなし。OpenAPI 上 `limit` 等は未定義） | 200 | 5 件。`display_state` は `hidden` 2 件、`showing` 2 件、`members_only` 1 件 |
| `GET /v1/groups/<group_id>`（管理画面で会員限定に設定したグループ） | 200 | `display_state` は `members_only`（一覧の値と一致） |

管理画面で会員限定に設定したグループは、一覧・単体とも `display_state: "members_only"` を返した。2026-09-21 の生 PUT で書き込んだ値だけでなく、管理画面で設定された既存グループでも同じ値が返るため、`GroupDisplayState` の実測 3 値（`showing` / `hidden` / `members_only`）は読み取り側でも裏付けられた。公式 OpenAPI の `productGroup` response 定義が列挙する `showing_for_members` / `sale_for_members` は、今回のショップの 5 グループには現れなかった（他のショップや設定で現れないことの証明ではない）。参考として、同日の `GET /v1/products` は OpenAPI どおり `showing_for_members` / `sale_for_members` を返しており、グループと商品では `display_state` の語彙が異なる。

#### `meta_tag` と `parent_group_id` の応答値

一覧の各要素のキーは `id`, `account_id`, `name`, `image_url`, `expl`, `sort`, `display_state`, `parent_group_id`, `meta_tag` で、2026-09-18 の単体観測と同じだった。`meta_tag` は 5 件中 4 件が `{"title":"","description":"","keywords":""}`（管理画面で未設定でも 3 キーが空文字で揃った object）、1 件が `null` だった。したがって、グループの `meta_tag` は object と `null` のどちらも起こり得る（OpenAPI の `nullable: true` と一致し、既存の `Product\Group::getMetaTag()` の nullable と整合する）。`parent_group_id` は 4 件が `null`、1 件が integer（親グループの id）だった。2026-09-18 の単体観測の表は、この 5 件を加算していない。

## 観測できなかったこと

- `category: null`、カテゴリーキー欠損、`id_big=0`。全くカテゴリーを持たない商品での表現は未検証。
- `unavailable_delivery_ids` の非空配列と、その要素型の実測。
- `options[].values`、`group_ids`、`unavailable_payment_ids` の `null` 要素や型違い。
- `images` や `variants` の `null` 値、`variants[].option1: null`。OpenAPI の nullable 記載だけから出現を断定しない。
- `fields` に他のキーを指定した場合、`offset` を変えた場合、商品一覧に50件超が存在する場合のページング。
- 商品 POST の `name` 以外のフィールド、商品 PUT で個別に試したフィールド以外の受理・無視・検証動作。
- `sales_price`、`price`、バリエーションの `model_number` 以外の nullable フィールドを明示的な `null` でクリアできるか。
- 画像作成の 201 と `product_image` の実キー、画像 DELETE の 204、`position` の実動作。契約プラン制限のため未観測（画像 DELETE は 422 だけを観測）。
- 商品書き込みの別ショップ・別契約プランでの挙動と、同時更新時の競合動作。
- グループの `meta_tag` が初回設定以後の PUT で GET に反映されない原因。API 側の挙動と考えられるが未解決。
- グループ・カテゴリーの `image_url` の書き込み、`POST /v1/groups` / `POST /v1/categories` / `POST /v1/categories/<category_id>/children` の新規作成応答（既存の同名の検証用データを再利用したため、作成の 201 は未観測）、小カテゴリーの `expl: ""` と `sort` の範囲外。
- 小カテゴリー（`PUT /v1/categories/<category_id>/children/<child_id>`）の `display_state` に `hidden` 以外（`showing` / `members_only`、および 422 になる `showing_for_members` / `sale_for_members`）を送った場合の挙動。
- `PUT /v1/groups/<group_id>` への `parent_group_id` の送信。公式 OpenAPI の更新 request は `parent_group_id` を持たず `additionalProperties: false` なので 422 になり得るが未観測（`GroupInput` は作成・更新共用のため送信を許し、利用者に委ねている）。
- `GET /v1/groups` / `GET /v1/groups/<group_id>` が `showing_for_members` / `sale_for_members` を返すか。公式 OpenAPI の `productGroup` response 定義にはあるが、PUT では 422 で、管理画面で会員限定に設定した既存グループも `members_only` を返した（2026-09-22、5 グループ）。別のショップや契約プラン、管理画面の他の設定で返るかは未観測。

## 現在のライブラリ実装との関係

商品本体、バリエーション、画像、商品広告の Entity は現時点で未実装である。`Product\Group` 単体応答は既存 Entity で構築・getter 確認済み。`Category` を大・小カテゴリーへ分割する変更は 0.11.0 で実装済み（[ADR 0010](adr/0010-split-category-into-big-and-small.md)）。本記録の `product.category` は ID ペアの小さな object であり、カテゴリー専用 GET の Entity 構造と同一とは限らない。ここから先の Entity の型や nullable の採否は設計判断であり、上記の実測事実と区別する。

## 読み取り観測の再収集手順

1. テスト用ショップで `read_products` スコープの認証を使い、上の表の GET エンドポイントとクエリを実行する。書き込みは行わない。
2. 通常一覧と6件の詳細で、全キー、JSON の型、明示的 `null`、キー欠損、各配列の要素数を別々に記録する。`display_state` ごとの一覧と `fields=id,name` の射影を別標本として扱う。
3. バリエーションを持つ1軸・2軸の商品で一覧（既定と `limit=100`）と単体、各商品の画像専用 GET、広告一覧、実在グループ1件、存在しない商品1件の404を確認する。
4. 公開前に `account_id`、全実 ID、画像 URL の実パス、長文、認証情報、ショップ名・URL がないことを確認する。マスク後もキーの有無、型、`null`、空配列、ゼロと非ゼロの関係、HTTP ステータスを保つ。

## 書き込み観測の再収集手順

1. テスト用ショップで検証専用の商品を `name` だけで1件作成し、直後に `display_state=hidden` へ更新する。他の既存商品は操作しない。
2. `display_state` の4値と拒否値、`unlisted`、整数設定後の `sales_price=null`、`name` だけの部分更新を、各 PUT 応答と直後の GET の組で確認する。
3. オプション、オプション値、バリエーション、ピックアップを順に作成・更新・削除し、HTTP ステータス、トップレベルキー、object のキー集合、204 のボディ不在を記録する。
4. 画像 API を利用できる契約プランでは、仕様で許可された小さな検証画像を1件だけ作成し、応答を記録して削除する。利用できない場合は、401 と成功系が未観測であることを区別する。
5. グループ・カテゴリーは `hidden` の検証用データを名前で再利用し、`display_state` の受理値、`expl` と `meta_tag` の明示 `null` と部分更新、空入力、存在しない ID、`sort` の範囲外を、各 PUT 応答と直後の GET の組で確認する。削除 API がないため、終了時に `hidden` へ戻す。
6. 終了時に従属物が残っていないことを GET で確認し、商品を `hidden` にする。公開前に全実 ID、ショップ識別情報、トークン、認証ヘッダー、URL、画像実パスをマスクする。

収集時点以降の API 仕様変更は未検証である。再収集した場合は収集日、条件、件数、全キー統計を新しい観測値で更新する。
