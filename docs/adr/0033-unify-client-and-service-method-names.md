# ADR 0033: Client と Service のメソッド名を整理する

- 状態: 採用
- 決定日: 2026-10-07

## 文脈

`Client` のメソッドは、おおむね `<動詞><対象>()` の形だった（`getProduct()`、`createProduct()`、`cancelSale()` など）。
しかし一覧取得は、ページングするもの（`Page` を返す）も全件を返すもの（`Collection` を返す）も
`get<対象の複数形>()` で、名前から区別できなかった。

- ページング: `getProducts` / `getStocks` / `getProductVariants` / `getProductAdvertisings` / `getSales` / `getCustomers`
- 全件: `getProductImages` / `getProductGroups` / `getProductCategories` / `getPayments` / `getDeliveries`

ほかにも、次の名前が揃っていなかった。

- `statSales()` だけが取得系なのに動詞が `get` でない
- `sendSalesMail()` は受注 1 件が対象なのに `Sales` と複数形
- `exchangeCode2Token()` は `to` の代わりに `2` を使っている
- `getStocks()` は、在庫を `Product` の下に置いた（[ADR 0030](0030-place-entities-by-api-path.md)）のに、
  グループ・カテゴリー・商品広告（`getProductGroups()` など）と違って `Product` を付けていない

`Services` のメソッドは、Service ごとに付け方が違った。`Services\Customer` と `Services\Sale` は
`page()` / `one()`、`Services\Delivery` と `Services\Payment` は `all()` を使う一方、`Services\Product` は
`products()` / `product()` / `variants()` / `groups()` のように対象の名前を使っていた。

## 判断

- **`Client` のメソッド名は `get` などの動詞を先頭に置く形を保つ。** 作成・更新・削除などは動詞なしでは
  書けず、取得だけを名詞（`products()` など）にすると付け方が 2 通りになる。通信を伴う処理であることも
  動詞で示す。出典: `27bf9d7`。
- **`Client` の一覧取得は、ページングするものを `get<対象>Page()`、全件を返すものを `get<対象の複数形>()`
  とする。** 対象は単数形にする（`getProductPage()`、`getSalePage()`）。出典: `27bf9d7`。
- これにより、`Client` の次の 9 つを改名する。出典: `27bf9d7`。
  - `getProducts` → `getProductPage`、`getStocks` → `getProductStockPage`、
    `getProductVariants` → `getProductVariantPage`、`getProductAdvertisings` → `getProductAdvertisingPage`、
    `getSales` → `getSalePage`、`getCustomers` → `getCustomerPage`
  - `statSales` → `getSaleStat`、`sendSalesMail` → `sendSaleMail`、`exchangeCode2Token` → `exchangeCodeForToken`
- **`Services` のメソッド名は、Service のクラス名にあたる対象を省き、`page` / `one` / `all` を使う。**
  サブリソースは対象の名前のあとに `Page` / `One` / `All` を付ける。出典: `27bf9d7`。
  - `Services\Product`: `products` → `page`、`product` → `one`、`variants` → `variantPage`、`variant` → `variantOne`、
    `images` → `imageAll`、`advertisings` → `advertisingPage`、`group` → `groupOne`、`groups` → `groupAll`、
    `categories` → `categoryAll`、未リリースの `stocks` → `stockPage`
  - `Services\OAuth`: `exchangeCode2Token` → `exchangeCodeForToken`
  - `Services\Sale` / `Customer` / `Delivery` / `Payment` / `Gift` / `Shop` は、すでにこの形のため変えない。
    作成・更新・削除などのメソッドも変えない
- **0.24.0 までの旧メソッド名（`Client` の 9 つと `Services` の 10 個）は、`@deprecated` を付けて新しい
  メソッドに委譲する形で残し、次のメジャーな変更で削除する。** 未リリースの `Services\Product::stocks()` は
  残さない。出典: `27bf9d7`。
- ページングの API の旧名（`getProducts()` など）は、ページを順にたどる全件取得を実装するときに、全件を返す
  メソッドとして使う予定とする。全件取得の実装（リクエスト回数、途中のエラー、メモリの扱い）は別に決める。
  利用者と協議して決めた予定で、README の「0.25.0 の変更」に記載した。出典: `8e2d8f1`。

## 代替案と却下理由

- **全件取得とページングを `getAll<対象>()` / `paginate<対象>()` などで区別する案**は、動詞で種類が分かる。
  しかし利用者の指定により、全件を複数形、ページングを `Page` で表す。
- **ページングの名前を複数形の `getProductsPage()` にする案**は、「商品詳細ページ」と読み違えにくい。
  しかし利用者の指定により、単数形の `getProductPage()` とする。
- **`get` を外して `products()` / `product()` とする案**、**`fetch` にする案**、**リソースのオブジェクトを返す
  案**（`$client->products()->page()`）は、利用者と協議のうえ採らない。動詞の揃い方、利用者の慣れ、
  変更の大きさによる。
- **`Services` のメソッド名を `Client` と同じ名前にする案**は、`Client` と `Services` の対応が自明になる。
  しかし利用者の指定により、Service の中ではクラス名にあたる対象を省き、既存の `page` / `one` / `all` を使う。
- **旧メソッド名をこのリリースで削除する案**は、コードベースが簡潔になる。しかし利用者のコードが壊れるため、
  非推奨として残す。

## 帰結

`Client` の一覧取得が、名前でページングか全件かを区別できるようになる。`Services` のメソッド名も
`page` / `one` / `all` に揃う。

次の点が変わる。

- 旧メソッド名は動作するが非推奨になる。新しいメソッド名に書き換える必要がある
- `Client` と `Services` でメソッド名が対応しない（`Client::getProductPage()` と `Services\Product::page()` など）
- ページングの旧名 `getProducts()` などは、全件取得を実装するときに、戻り値が `Page` から全件に変わる。
  そのときは破壊的変更になる

## 関連

- [ADR 0000: アーキテクチャ上の意思決定を記録する](0000-record-architecture-decisions.md)
- [ADR 0030: Entity の名前空間を API の URL に沿わせる](0030-place-entities-by-api-path.md)
- [ADR 0031: 受注の Service を Services\Sale に改名する](0031-rename-sales-service-to-sale.md)
- [ADR 0032: 在庫の Service を Services\Product に統合する](0032-merge-stock-service-into-product-service.md)
- 実装とテストの出典コミット: `27bf9d7`
- 商品の Service のサブ Service への分割: [ADR 0034](0034-split-product-service.md)
