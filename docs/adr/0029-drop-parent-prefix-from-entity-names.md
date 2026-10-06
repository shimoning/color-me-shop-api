# ADR 0029: Entity のクラス名に親の名前の接頭辞を付けない

- 状態: 採用
- 決定日: 2026-10-06

## 文脈

Entity は API の領域ごとの名前空間（`Entities\Sales`、`Entities\Gift`、`Entities\Delivery` など）に置いている。
各名前空間には主となる Entity（`Sales\Sale`、`Gift\Gift`、`Delivery\Delivery` など）があり、それに属する
Entity のクラス名に主 Entity の名前を接頭辞として付けるかどうかが、名前空間ごとに揃っていなかった。

- `Sales` と `Gift` は、ほぼすべてに付けていた（`SaleDelivery`、`SaleTotals`、`GiftCard`、`GiftNoshi` など）
- `Payment` は付けていなかった（`Brand`、`Card`、`Cod`、`CodFee`）
- `Delivery`、`Customer`、`Product` は混在していた（`DeliveryDate` と `Area`・`Weight`、
  `ProductStocksIncrementInput` と `VariantUpdateInput` など）

利用者は、ある Entity のクラス名を名前空間から推測できなかった。

`Product` には、名前の似た画像の Entity が 2 つある。`Product\Image` は商品本体の `images[]` の要素、
`Product\ProductImage` は商品画像の取得 API（`GET /v1/products/{product_id}/images`）の要素である。
2026-10-05 に両者を突き合わせ、項目（`src` / `position` / `mobile` と `position` / `url`）も、表す画像の
範囲（画像の取得 API はメイン画像を `position: 0` として含み、商品本体の `images[]` は含まない）も
違うことを確かめた。詳細は[ColorMe Shop API 商品応答構造の実測記録](../api-product-structure.md)にある。出典: `a86fd98`。

## 判断

- **Entity のクラス名に、所属する名前空間の主 Entity の名前を接頭辞として付けない。** 何に属するかは
  名前空間で表す。出典: `152b88a`。
- 主 Entity 自身（`Sales\Sale` など）と、`<対象><操作>Input`（ADR 0016）の `<対象>` は接頭辞ではないため、
  そのままにする（`Sales\SaleCreateInput`、`Customer\CustomerUpdateInput` など）。`SearchParameters` 系の
  `Advertising` / `Variant` も検索の対象を表すため、そのままにする。出典: `152b88a`。
- この規則により、次の 19 クラスを改名する。出典: `152b88a`。
  - `Sales`: `SaleApplication` → `Application`、`SaleCustomization` → `Customization`、
    `SaleDelivery` → `Delivery`、`SaleDetail` → `Detail`、`SaleSegment` → `Segment`、
    `SaleShopCoupon` → `ShopCoupon`、`SaleTotals` → `Totals`、
    `SaleCustomerCreateInput` → `CustomerCreateInput`、`SaleDeliveryCreateInput` → `DeliveryCreateInput`、
    `SaleDeliveryUpdateInput` → `DeliveryUpdateInput`、`SaleDetailCreateInput` → `DetailCreateInput`
  - `Gift`: `GiftCard` → `Card`、`GiftNoshi` → `Noshi`、`GiftType` → `Type`、`GiftWrapping` → `Wrapping`
  - `Delivery`: `DeliveryDate` → `Date`、`DeliveryDateDays` → `DateDays`、`DeliveryDateTimes` → `DateTimes`
  - `Product`: `ProductStocksIncrementInput` → `StocksIncrementInput`
- **次の 2 つは例外として改名しない。**
  - `Product\ProductImage`: 接頭辞を外すと `Product\Image` と衝突する。両者は項目も表す画像の範囲も違い、
    1 つにまとめられない。出典: `a86fd98`。
  - `Product\ProductVariantInput`（商品更新の `variants[]` の要素）: 接頭辞を外した `VariantInput` は
    ADR 0016 で改名した `VariantUpdateInput` の旧名として使っている。ADR 0016 の規則に従った
    `VariantUpdateInput` も、項目の異なるバリエーション更新 API の入力が使っている。出典: `152b88a`。
- 旧名は ADR 0016 と同じ仕組みで非推奨の別名として残し、次のメジャーな変更で削除する。ADR 0016 の旧名
  `Sales\SaleDeliveryUpdater` の対応先は、新しい `Sales\DeliveryUpdateInput` に付け替える。出典: `152b88a`。
- 旧名と新名の対応表は README から[非推奨のクラス名の対応表](../class-aliases.md)に移し、README からは
  リンクする。出典: `eb1a52a`。

## 代替案と却下理由

- **すべてのクラス名に主 Entity の名前を付ける案**は、名前空間をまたいでもクラス名が一意になり、
  `use ... as` が要らない。しかし `Payment\PaymentCard`、`Delivery\DeliveryArea` のように、名前空間と
  重なる冗長な名前が増える。利用者と協議のうえ、付けない側に揃える。
- **公式 OpenAPI のスキーマ名（`productVariant`、`deliveryChargeByPrefecture` など）に合わせる案**は、
  公式仕様と名前を対応づけられる。しかしスキーマが定義されていない構造が多く、別の規則で補う必要がある。
- **`DeliveryDate` 系の `Delivery` を「配送日」という概念の一部とみなして残す案**は、`Date` という汎用的な
  名前を避けられる。しかし接頭辞かどうかを個別に判断する余地が残るため、利用者の指定により外す。
- **名前空間 `Sales` を `Sale` に揃える変更を同時に行う案**は、名前空間の単数・複数も揃う。しかし Service の
  改名も含んで影響範囲が大きいため、別の変更として行う。

## 帰結

クラス名から主 Entity の名前の重複がなくなり、名前空間とクラス名から Entity を推測しやすくなる。

次の点が変わる。

- 新名で型宣言・`instanceof` を書くことを推奨する。旧名での生成、旧名での `instanceof` と型宣言、旧名で
  直列化されたデータの復元は、別名により引き続き成立する
- **クラス名の文字列に依存するコードは互換でない。** `get_class()` の結果と、例外のメッセージに含まれる
  クラス名は新名になる。一方、旧名の `::class` は旧名の文字列のままなので、`get_class($x) === SaleDelivery::class`
  のような比較は成立しなくなる
- 別の名前空間に同じ名前のクラスができる（`Sales\Delivery` と `Delivery\Delivery`、
  `Sales\CustomerCreateInput` と `Customer\CustomerCreateInput`、`Gift\Card` と `Payment\Card`）。両方を使う
  ファイルでは `use ... as` が必要になる
- 例外の 2 クラスは、規則から外れた名前のまま残る

## 関連

- [ADR 0000: アーキテクチャ上の意思決定を記録する](0000-record-architecture-decisions.md)
- [ADR 0016: 要求側入力 Entity の命名を統一し、旧名を非推奨の別名で残す](0016-unify-request-input-entity-names.md)
- [ColorMe Shop API 商品応答構造の実測記録](../api-product-structure.md)（出典コミット: `a86fd98`）
- [非推奨のクラス名の対応表](../class-aliases.md)
- 実装とテストの出典コミット: `152b88a`
- 対応表の移動の出典コミット: `eb1a52a`
