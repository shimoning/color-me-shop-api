# 変更履歴

利用者のコードに影響する変更と、移行の方法をバージョンごとにまとめる。ここにないバージョンを含むすべての変更は、[GitHub のリリース](https://github.com/shimoning/color-me-shop-api/releases)を参照。

## 0.35.0

### 入力 Entity の必須フィールドを生成時に検証する

必須フィールドの検証を、送信時の Service から、入力 Entity のコンストラクタへ移した ([ADR 0050](docs/adr/0050-validate-required-fields-in-input-entities.md))。必須フィールドが欠けた入力は、送信時の `ParameterException` ではなく、入力 Entity の生成時の `InvalidFieldException` になる。キーがあれば必須を満たしたと扱い、明示した `null` を受け付けるかは、これまでどおり各フィールドの型で決まる。

| 入力 Entity | 必須フィールド |
| --- | --- |
| `Customer\CustomerCreateInput` | `name`、`mail`、`pref_id`、`postal`、`address1`、`tel` |
| `Customer\CustomerUpdateInput` | `name`、`address1`、`address2` |
| `Customer\Points\PointsInput` | `points` |
| `Sale\SaleCreateInput` | `payment_id`、`details` (1 件以上) |
| `Sale\DetailCreateInput` | `product_id`、`product_num` |
| `Sale\DeliveryCreateInput` | `delivery_id`、`name`、`furigana`、`postal`、`pref_id`、`address1`、`tel` |
| `Product\Pickup\PickupInput` | `pickup_type`、`order_num` |

`ParameterException` で必須フィールドの欠落を捕捉していたコードは、入力 Entity の生成を囲んで `InvalidFieldException` を捕捉するように変える。両方を捕捉するには、親クラスの `ColorMeApiException` を使う。必須フィールドが欠けた入力は、送信しない場合も生成できなくなる。`PickupInput` には、ほかの入力 Entity と同じく `REQUIRED_FIELDS` を追加した。検証は基底の `Entity` のコンストラクタが行い、`RequestEntity` に `REQUIRED_FIELDS` の既定値 (空の配列) を追加した。

## 0.34.0

### 受注のお届け先の更新で、furigana の空文字と不正な値を null にしない

`Sale\DeliveryUpdateInput` の `furigana` に空文字 `''` を渡すと、これまでは `null` として送っていた。今後は空文字のまま送る。`'0'` のように falsy でカタカナでない値も、これまではエラーにならずに `null` として送っていたが、今後は生成時に `InvalidFieldException` になる (`'abc'` のような falsy でない不正な値は、これまでも `InvalidFieldException` だった)。顧客の入力 (`CustomerCreateInput`、`CustomerUpdateInput`、`Sale\CustomerCreateInput`) と同じ扱いになる。`null` を渡した場合は、これまでどおり `null` を送る。

### 受注と顧客の応答で、子 Entity の空の配列と falsy な値を null にしない

応答の次の項目が、空の配列 `[]` のときは、これまでは `null` を返していたが、今後は中身の空の Entity を返す (`externalAccounts` は、これまでどおり `[]` を返す)。`''`、`0`、`false`、`'0'` のような `null` 以外の falsy な値は、これまでは `null` (`externalAccounts` は `[]`) を返していたが、今後は生成時に `InvalidFieldException` になる。値が `null` のときと項目がないときは、これまでどおり `null` を返す。実 API でこれまでに観測した値は、`null` か項目なしだけだった。

- `Sale\Sale` の `segment`、`totals`、`application`、`shopCoupon`
- `Customer\Customer` の `membership`、`externalAccounts`
- `Customer\Membership\Membership` の `progress`
- `Customer\Membership\Progress` の `nextMembership`

### 顧客とお届け先の応答で、furigana の空文字を null にしない

`Customer\Customer` と `Sale\Delivery` の `furigana` が空文字 `""` のとき、これまでは `getFurigana()` が `null` を返していたが、今後は `Furigana('')` を返す。実 API は `furigana` の `""` と `null` を別の値として保存して返す (2026-10-15 の観測、[顧客とお届け先の `furigana` の実測記録](docs/api-furigana-observation.md))。値が `null` のときと項目がないときは、これまでどおり `null` を返す。`0`、`false`、`[]` のような文字列でない falsy な値は、これまでは `null` を返していたが、今後は生成時に `InvalidFieldException` になる。`'0'` は、これまでは `null` を返していたが、今後は `'abc'` などの不正な文字列と同じく、`isValid()` が `false` の `Furigana` を返す ([ADR 0049](docs/adr/0049-keep-empty-furigana-in-responses.md))。

## 0.33.0

### API の結果を表す型を Results に移した

API の呼び出しが返す結果の型を、新しい名前空間 `Results` に集めた ([ADR 0048](docs/adr/0048-move-result-types-to-results.md))。`Communicator\Errors` は `Results\Errors` に、`Communicator\NoContent` は `Results\NoContent` に、`Entities\OAuth\ErrorResponse` は `Results\OAuth\ErrorResponse` になる。旧名は非推奨の別名として残しており、`instanceof`、型宣言、生成、`Errors::build()` は旧名のままでも動作する ([非推奨のクラス名の対応表](docs/class-aliases.md))。`get_class()` などで得るクラス名の文字列は新しい名前になる。エラー 1 件を表す `Entities\Error` は移していない。

## 0.32.0

### 継承しない具象クラスの final 化を予告した

継承を前提にしたクラスは `abstract` に、ライブラリの中で継承しない具象クラスは `final` にする規則を設けた ([ADR 0045](docs/adr/0045-final-by-default-for-concrete-classes.md))。既存の 95 クラス (Entity、`Communicator` のクラス、一部の例外と値オブジェクト、`Services\OAuth\Options`) には docblock の `@final` を付けて予告し、次のメジャーな変更で `final` にする。0.32.0 では動作は変わらない。PHPStan は、`@final` のクラスを継承しているコードを `class.extendsFinalByPhpDoc` として指摘する。

`Client` と Service は、利用者がテストでモックできるように `final` にしない。ライブラリの中で継承される `Entities\Collection`、`Values\Limit`、`Values\Sort`、`Exceptions\ColorMeApiException`、`Exceptions\InvalidFieldException`、`Exceptions\MissingFieldException` も `final` にしない。

### すべての PHP ファイルで strict_types を宣言した

src、tests、examples、`client` スクリプトのすべての PHP ファイルで `declare(strict_types=1);` を宣言した ([ADR 0046](docs/adr/0046-declare-strict-types-everywhere.md))。利用者のコードからライブラリを呼ぶときの型の扱いは、利用者のファイルの宣言で決まるため変わらない。`Values\Furigana::validate()` と `Communicator\RequestOptions` の `authorization` は、宣言の前と同じ結果を返す。

### Constants の enum を URL に合わせた名前空間に移した

`Constants` の直下にあった enum のうち 24 個を、Entity と同じく API の URL に合わせた名前空間に移した ([ADR 0047](docs/adr/0047-nest-constants-by-url.md))。クラス名は Entity と同じ規則 ([ADR 0029](docs/adr/0029-drop-parent-prefix-from-entity-names.md)) で、移した先の名前空間と重なる接頭辞を除いた。たとえば `Constants\ProductDisplayState` は `Constants\Product\DisplayState` に、`Constants\ShopState` は `Constants\Shop\State` に、`Constants\PointState` は `Constants\Sale\PointState` になる。旧名は非推奨の別名として残しており、`instanceof`、型宣言、case の参照、`from()` / `tryFrom()` は旧名のままでも動作する ([非推奨のクラス名の対応表](docs/class-aliases.md))。`DisplayState` や `Type` のように同じ名前の enum があるため、1 つのファイルで並べて使うときは `use ... as` で別名を付ける。複数の API で使う `Prefecture`、`Sex`、`ErrorCode`、`SortDirection` と、基底の `FallbackEnum` は直下に残した。

## 0.31.0

### RequestEntity を Entities に移した

利用者が送信する値を組み立てる Entity の印 `Contracts\RequestEntity` を `Entities\RequestEntity` に移した ([ADR 0042](docs/adr/0042-move-request-entity-to-entities.md))。旧名は非推奨の別名として残しており、旧名での `implements`、`instanceof` と型宣言は動作する ([非推奨のクラス名の対応表](docs/class-aliases.md))。

### 基底の型と配置を揃えた

[ADR 0043](docs/adr/0043-align-base-types-and-placement.md) で次を変えた。

- `Entities\Entity` を `abstract` にした。`Entity` を直接生成することはできない
- `Sale\DeliveryUpdateInput` の継承元を、応答の `Sale\Delivery` から `Entity` にした。`Sale\Delivery` の `instanceof` を満たさなくなり、`getName()` などの応答の getter を持たなくなった。setter と、送信する内容は変わらない
- OAuth のアプリ設定 `Entities\OAuth\Options` を `Services\OAuth\Options` に移した。旧名は非推奨の別名として残している ([非推奨のクラス名の対応表](docs/class-aliases.md))

### 入力 Entity の getter と setter、受注の convert() を非推奨にした

入力 Entity は、コンストラクタの配列で組み立てる形に揃える ([ADR 0044](docs/adr/0044-deprecate-input-accessors-and-sale-convert.md))。次を非推奨にした。動作は変わらず、次のメジャーな変更で削除する。

- `Sale\SaleUpdateInput::convert()`。受注の更新 API は送った項目だけを変えるため、現在の値を引き継ぐ必要はない。送りたい項目だけをコンストラクタの配列で渡す
- `Sale\SaleUpdateInput` の `isPaid()`、`getPointState()`、`getSaleDeliveries()`、`setPaid()`、`setPointState()`、`setSaleDeliveries()`
- `Sale\DeliveryUpdateInput` の setter すべて
- 商品・在庫・商品の広告の検索条件 `SearchParameters` の `getDisplayState()`

値はコンストラクタの配列で指定し、送信内容は `toArrayRecursive()` で確かめる。`toArray()` は指定していない項目も `null` で含み、enum などを展開しないため、送信内容とは異なる。

## 0.30.0

### 顧客データの更新で address2 を必須にした

顧客データの更新 (`Client::updateCustomer()` / `Services\Customer::update()`) で、`CustomerUpdateInput` の `address2` を、`name` / `address1` と同じく必須にした ([ADR 0041](docs/adr/0041-require-address2-in-customer-update.md))。実 API は、更新で `address2` を送らないと空にする (2026-10-09 の観測)。これまでは `address2` を指定しない部分更新で、利用者の気づかないうちに `address2` が消えていた。

`address2` を明示しない更新は、送信前に `ParameterException` になる。今の値を保つには今の値を、消すには `null` を明示する。

## 0.29.0

### 値オブジェクトのインスタンスを受け付ける

要求 Entity や検索条件の値オブジェクトのフィールドで、生の値に加えて値オブジェクトのインスタンスも受け付けるようにした ([ADR 0040](docs/adr/0040-accept-value-object-instances.md))。enum と Entity のフィールドは、以前からインスタンスを受け付けている。

```php
new CustomerCreateInput([..., 'furigana' => new Furigana('ヤマダ タロウ')]);
new ProductSearchParameters(['limit' => new Limit(30), 'sort' => [new Sort('price', SortDirection::DESC), 'make_date']]);
```

| 渡したもの | 扱い |
| --- | --- |
| 宣言したクラスか、その子クラスのインスタンス | そのまま使う |
| 宣言したクラスの親クラスのインスタンス (例: 商品の検索条件に基底の `Values\Limit`) | 宣言したクラスで作り直して検証し直す。`new Limit(80)` は商品の上限 50 を超えるため拒否する |
| 無関係の値オブジェクト | 拒否する |
| 検証に通らない値を保持した `Values\FallbackValue` のインスタンス | 要求側では拒否する。応答側ではそのまま受け付ける |

配列のフィールド (商品の検索条件の `sort` など) は、要素ごとに生の値とインスタンスを混在して渡せる。

## 0.28.0

### 商品一覧の並び順

商品一覧の検索条件の `sort` を、文字列の配列で指定するようにした ([ADR 0038](docs/adr/0038-represent-sort-with-value-object.md))。各要素はカラム名で、`-` で始めると降順になる。内部では各要素を値オブジェクト `Values\Product\Sort` (汎用の `Values\Sort` を継承し、カラム名と向きを分けて持つ) にし、クエリでは空白なしのカンマ区切り (`-sales_price,make_date`) で送る。`toArray()` の `sort` は `Values\Product\Sort` の配列になる。

```php
new ProductSearchParameters(['sort' => ['-update_date']]);
new ProductSearchParameters(['sort' => ['-sales_price', 'make_date']]);
```

文字列 1 つでの指定 (`'sort' => '-make_date'`) は、生成時に `InvalidFieldException` になる。0.28.0 では値オブジェクトのインスタンスも拒否していたが、0.29.0 から受け付ける ([0.29.0 の変更](#0290))。

カラム名は、公式 OpenAPI が挙げる `make_date` / `update_date` / `sales_price` / `price` / `members_price` の 5 つだけを受け付け (大文字・小文字を区別する)、それ以外は生成時に `InvalidFieldException` になる ([ADR 0039](docs/adr/0039-validate-product-sort-fields.md))。2026-10-08 の観測では、実 API はそれ以外の値をエラーにせず、並び順に使わなかった ([商品一覧の sort の実測記録](docs/api-product-sort-observation.md))。

### 検索条件の fields の指定方法

一覧取得の検索条件の `fields` を、すべて文字列の配列で指定するように揃えた ([ADR 0037](docs/adr/0037-unify-search-fields-as-string-list.md))。クエリではカンマ区切りで送る。

| 検索条件 | 0.27.x まで | 0.28.0 から |
| --- | --- | --- |
| `Entities\Product\SearchParameters` | `'fields' => 'id,name'` (文字列) | `'fields' => ['id', 'name']` |
| `Entities\Product\Stock\SearchParameters` | 文字列 | 文字列の配列 |
| `Entities\Product\Variant\SearchParameters` | 文字列 | 文字列の配列 |
| `Entities\Sale\SearchParameters` | 文字列の配列 | 変更なし |
| `Entities\Customer\SearchParameters` | なし | 文字列の配列 (新規。公式 OpenAPI に記載はないが、2026-10-08 の観測で実 API が受け付けた) |

商品・在庫・バリエーションの検索条件に `fields` を文字列で渡すと、生成時に `InvalidFieldException` になる。文字列の `fields` を持つまま `serialize()` した、これらの検索条件は `unserialize()` で復元できない (`TypeError`)。

商品広告の一覧取得 (`GET /v1/product_advertisings`) は、`fields` を指定しても実 API が応答を絞り込まなかった (2026-10-08 の観測) ため、検索条件に `fields` を持たない。

## 0.27.1

### 顧客と受注の一覧で、複数指定の検索条件をカンマ区切りで送る

`ids` / `customer_ids` / `payment_ids` (整数の配列) と `fields` (文字列の配列) を、クエリでカンマ区切りにして送るようにした。0.27.0 までは配列の形 (`ids[0]=1&ids[1]=2`) で送っており、実 API は HTTP 500 を返していた (`fields` は中身のない要素を返していた) ([検索条件のクエリ形式の実測記録](docs/api-search-query-format-observation.md))。

## 0.27.0

### 一覧取得の limit の検証

一覧取得の検索条件の `limit` を、API ごとの上限を持つ値オブジェクトで検証するようにした ([ADR 0036](docs/adr/0036-validate-limit-per-api.md))。上限は実 API で確かめた値で、下限は 1 である ([ページングの limit の実測記録](docs/api-pagination-limit-observation.md))。範囲外の値は、検索条件の生成時に `InvalidFieldException` になる。

| 検索条件 | 値オブジェクト | 範囲 |
| --- | --- | --- |
| `Entities\Product\SearchParameters` | `Values\Product\Limit` | 1 〜 50 |
| `Entities\Product\Stock\SearchParameters` | `Values\Product\Stock\Limit` | 1 〜 50 |
| `Entities\Product\Variant\SearchParameters` | `Values\Product\Variant\Limit` | 1 〜 100 |
| `Entities\Product\Advertising\SearchParameters` | `Values\Product\Advertising\Limit` | 1 〜 250 |
| `Entities\Sale\SearchParameters` | `Values\Sale\Limit` | 1 〜 100 |
| `Entities\Customer\SearchParameters` | `Values\Customer\Limit` | 1 〜 100 |

これまで API が上限に丸めていた値 (在庫の 51 〜 100、商品の 51 以上、バリエーションの 101 以上、商品広告の 251 以上) と、商品・バリエーション・商品広告の 0 以下 (観測した `0` と `-1` は API で要素が返らなかった) は、送信前に拒否されるようになる。`limit` は整数で指定する (0.28.0 までは値オブジェクトのインスタンスを受け付けなかった。0.29.0 から受け付ける)。

## 0.26.0

### 商品の Service の分割

`Services\Product` を、Entity の名前空間と同じ単位のサブ Service に分けた ([ADR 0034](docs/adr/0034-split-product-service.md))。`Client` の公開メソッドは変わらない。

| サブ Service | メソッド |
| --- | --- |
| `Services\Product\Variant` | `page()` / `one()` / `update()` |
| `Services\Product\Option` | `create()` / `delete()` |
| `Services\Product\Option\Value` | `create()` / `delete()` |
| `Services\Product\Pickup` | `create()` / `update()` / `delete()` |
| `Services\Product\Image` | `all()` / `create()` / `delete()` |
| `Services\Product\Group` | `one()` / `all()` / `create()` / `update()` |
| `Services\Product\Category` | `all()` / `create()` / `update()` / `createChild()` / `updateChild()` |
| `Services\Product\Advertising` | `page()` |
| `Services\Product\Stock` | `page()` |

カテゴリーの応答が期待した形でないときの `InvalidFieldException` のメッセージは、発生元のクラス名が `Services\Product\Category` になった (例外の型は変わらない)。

`Services\Product` には商品本体の `page()` / `one()` / `create()` / `update()` を残した。サブリソースのメソッド (`variantPage()`、`createGroup()`、`stockPage()` など 24 個) は非推奨とし、対応するサブ Service に委譲する。次のメジャーな変更で削除する。

## 0.25.0

### メソッド名の整理

`Client` の一覧取得は、ページングするもの (`Page` を返す) を `get<対象>Page()`、全件を返すもの (`Collection` を返す) を `get<対象の複数形>()` とした。あわせて、取得系の動詞を `get` に揃え、受注 1 件を対象とするメソッドを単数形にした ([ADR 0033](docs/adr/0033-unify-client-and-service-method-names.md))。

| 旧メソッド名 | 新メソッド名 |
| --- | --- |
| `getProducts()` | `getProductPage()` |
| `getStocks()` | `getProductStockPage()` |
| `getProductVariants()` | `getProductVariantPage()` |
| `getProductAdvertisings()` | `getProductAdvertisingPage()` |
| `getSales()` | `getSalePage()` |
| `getCustomers()` | `getCustomerPage()` |
| `statSales()` | `getSaleStat()` |
| `sendSalesMail()` | `sendSaleMail()` |
| `exchangeCode2Token()` | `exchangeCodeForToken()` |

`Services\Product` の取得メソッドも、`page()` / `one()` と、サブリソースの `<対象>Page()` / `<対象>One()` / `<対象>All()` に揃えた (`products()` → `page()`、`variants()` → `variantPage()`、`groups()` → `groupAll()` など)。`Services\OAuth::exchangeCode2Token()` は `exchangeCodeForToken()` にした。

旧メソッド名は非推奨として残しており、新しいメソッドに委譲する。次のメジャーな変更で削除する。ページングの `getProducts()` などの旧名も、意味を変えずに非推奨のまま削除する。公式 API に全件取得がないため、ページを順にたどる全件取得はライブラリでは提供しない ([ADR 0035](docs/adr/0035-follow-api-without-client-side-features.md))。

### Service の整理

受注の `Services\Sales` を `Services\Sale` に改名した。旧名は非推奨の別名として残している ([ADR 0031](docs/adr/0031-rename-sales-service-to-sale.md)、[非推奨のクラス名の対応表](docs/class-aliases.md))。在庫一覧の取得は `Services\Product` に統合し、`Services\Stock` を非推奨にした ([ADR 0032](docs/adr/0032-merge-stock-service-into-product-service.md))。

## 0.22.0

### 商品の入力を作成と更新で分けた

作成と更新で共用していた `ProductInput` を `ProductCreateInput` と `ProductUpdateInput` に分け、`ProductInput` は削除した ([ADR 0026](docs/adr/0026-split-product-input-into-create-and-update.md))。更新の呼び出しは `new ProductInput(` を `new ProductUpdateInput(` に、作成の呼び出しは `new ProductCreateInput(` に書き換える。作成で更新専用の 4 項目 (`category_id_small` / `stocks` / `group_ids` / `variants`) を指定していた場合、その指定はもともと反映されていなかった。

### 要求側の配列フィールドに連番でない配列を渡すと例外にした

要求側 Entity の配列フィールドに、キーが 0 から始まる連番でない配列を渡すと、生成時や setter での設定時に `InvalidFieldException` になる ([ADR 0025](docs/adr/0025-reject-non-list-arrays-in-requests.md))。`array_values()` を通してから渡す。

## 0.20.0

### 受注の更新で、受注 ID を第 1 引数で渡す

0.19.0 までは `$client->updateSale($updater)` のように入力だけを渡し、`SaleUpdateInput` が持つ `id` を path と body の両方に使っていた。受注 ID は第 1 引数で渡し、`SaleUpdateInput` は受注 ID を持たず body にも含めない。`SaleUpdateInput::getId()` は削除したため、受注 ID は取得した受注の `getId()` などから渡す ([ADR 0022](docs/adr/0022-take-sale-id-as-update-argument.md))。実測では、body の `id` の有無や値 (存在しない ID を含む) で応答と結果に差はなく、path の受注として処理された。ただし観測は値を変えない更新に限られる ([受注更新の body に含めた `id` の実測記録](docs/api-sale-update-id-observation.md))。

## 0.19.0

### OAuth のトークンのエラー応答から state を外した

`ErrorResponse` から `state` プロパティを削除し、`toArray()` と `toArrayRecursive()` の出力から `state` キーを外した ([ADR 0021](docs/adr/0021-drop-state-from-token-error-response.md))。`getState()` は非推奨として残しており、応答に文字列の `state` があればそれを返し、なければ `null` を返す。

## 0.18.0

### 認可 URL に state を渡せるようにした

`Client::getOAuthUrl()` に、CSRF 対策の `state` を渡せるようにした。

## 0.14.0

### 要求側入力クラスの改名

書き込み入力クラスの命名を `<対象><操作>Input` に統一した ([ADR 0016](docs/adr/0016-unify-request-input-entity-names.md))。旧クラス名は非推奨の別名として残していたが、0.24.0 で削除した ([ADR 0030](docs/adr/0030-place-entities-by-api-path.md))。旧名と新名の対応は[非推奨のクラス名の対応表](docs/class-aliases.md)にある。

### フリガナの検証

`Values\Furigana` の許容文字を `^[ァ-ヶー 　]*$` とし、空文字を受け付けるようにした。従来は空文字を拒否していた。公式 OpenAPI が許容する `ヷヸヹヺ` は実 API が 422 で拒否するため含めない (2026-09-25 の観測)。

### 応答側の値オブジェクトのフォールバック

`Values\FallbackValue` を導入し、`Values\Furigana` に実装した ([ADR 0017](docs/adr/0017-opt-in-value-fallback.md))。応答側では検証に通らない値でも生の文字列を保持するため、管理画面から入力された数値文字参照を含むフリガナを持つ顧客でも、単体取得と一覧取得が失敗しなくなる。0.13.0 以前は `InvalidFieldException` になっていた。

要求側の厳格さは変わらない。フォールバックは `FallbackValue` を実装した値オブジェクトにだけ適用され、`Values\DateTime` や `Values\Limit` の挙動は変わらない。

## 0.13.0

### 商品グループの表示状態を専用の enum にした

応答の `Group::getDisplayState()` が返す enum を、商品の enum からグループ専用の enum (現在の `Constants\Product\Group\DisplayState`) に変えた。実 API が返す `members_only` を読めるようにするため。

## 0.9.0

### OAuth のエラー応答を専用の型で返す

`Client::exchangeCode2Token()` と `Services\OAuth::exchangeCode2Token()` (0.25.0 で `exchangeCodeForToken()` に改名) の戻り値が `AccessToken|Errors` から `AccessToken|ErrorResponse|Errors` へ変わった。OAuth エラーを `Errors` だけで判定していたコードには破壊的変更となる。
