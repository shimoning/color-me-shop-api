# 非推奨のクラス名の対応表

改名したクラスの旧名は、非推奨の別名として残している。旧名は次のメジャーな変更で削除する。新しいコードでは
新名を使うこと。

旧名での生成、旧名での `instanceof` と型宣言は従来どおり動作する。Entity は、旧名で `serialize()` された
データの `unserialize()` も動作する（`allowed_classes` には旧名を渡すこと）。Service は直列化を想定しない
ため、旧名での `unserialize()` は対象外である。
ただし、クラス名の文字列に依存するコードは互換でない。`get_class()` の結果は新名になる一方、旧名の
`::class` は旧名の文字列のままなので、両者の比較は成立しなくなる。

クラス名は、断りがなければ `Shimoning\ColorMeShopApi\Entities\` 以下である。

## 0.31.0: RequestEntity の移動

基底の型を、属する分類のフォルダに置く規則（`Values\Value`、`Constants\FallbackEnum`、`Entities\Entity`）に揃え、
`Contracts` を廃止した（[ADR 0042](adr/0042-move-request-entity-to-entities.md)）。この表の名前は
`Shimoning\ColorMeShopApi\` 以下である。

| 旧インターフェース名 | 新インターフェース名 |
| --- | --- |
| `Contracts\RequestEntity` | `Entities\RequestEntity` |

旧名での `implements`、`instanceof` と型宣言は動作する。

## 0.31.0: OAuth の設定の移動

`Entities` には Entity だけを置くようにし、OAuth のアプリ設定を使う Service の下に移した
（[ADR 0043](adr/0043-align-base-types-and-placement.md)）。この表のクラス名は `Shimoning\ColorMeShopApi\` 以下である。

| 旧クラス名 | 新クラス名 |
| --- | --- |
| `Entities\OAuth\Options` | `Services\OAuth\Options` |

旧名での生成、`instanceof` と型宣言は動作する。

## 0.25.0: 受注の Service の改名

ほかの Service と同じ単数形にし、Entity の名前空間 `Entities\Sale` と揃えた
（[ADR 0031](adr/0031-rename-sales-service-to-sale.md)）。`Client` のメソッド名（`getSales()` など）は変わらない。
この表のクラス名は `Shimoning\ColorMeShopApi\` 以下である。

| 旧クラス名 | 新クラス名 |
| --- | --- |
| `Services\Sales` | `Services\Sale` |

`Services\Sale` と `Entities\Sale\Sale` を両方使うファイルでは、`use ... as` で別名を付けること。

Service は HTTP クライアントなどの実行時の依存を持ち、直列化して使うことを想定していないため、旧名での `unserialize()` の
互換は確かめていない。旧名での生成と `instanceof`・型宣言は動作する。

## 0.24.0: 名前空間を API の URL に沿わせる改名

Entity の名前空間を公式 API の URL のパスに沿わせ、名前空間の主 Entity の名前をクラス名の接頭辞に付けない
ように揃えた（[ADR 0029](adr/0029-drop-parent-prefix-from-entity-names.md)、
[ADR 0030](adr/0030-place-entities-by-api-path.md)）。名前空間 `Sales` は `Sale` になった。

| 旧クラス名 | 新クラス名 |
| --- | --- |
| `Sales\Sale` | `Sale\Sale` |
| `Sales\SaleCreateInput` | `Sale\SaleCreateInput` |
| `Sales\SaleUpdateInput` | `Sale\SaleUpdateInput` |
| `Sales\SearchParameters` | `Sale\SearchParameters` |
| `Sales\SaleApplication` | `Sale\Application` |
| `Sales\SaleCustomization` | `Sale\Customization` |
| `Sales\SaleDelivery` | `Sale\Delivery` |
| `Sales\SaleDetail` | `Sale\Detail` |
| `Sales\SaleSegment` | `Sale\Segment` |
| `Sales\SaleShopCoupon` | `Sale\ShopCoupon` |
| `Sales\SaleTotals` | `Sale\Totals` |
| `Sales\SaleCustomerCreateInput` | `Sale\CustomerCreateInput` |
| `Sales\SaleDeliveryCreateInput` | `Sale\DeliveryCreateInput` |
| `Sales\SaleDeliveryUpdateInput` | `Sale\DeliveryUpdateInput` |
| `Sales\SaleDetailCreateInput` | `Sale\DetailCreateInput` |
| `Sales\Stat` | `Sale\Stat\Stat` |
| `Customer\Points` | `Customer\Points\Points` |
| `Customer\CustomerPointsInput` | `Customer\Points\PointsInput` |
| `Customer\Membership` | `Customer\Membership\Membership` |
| `Customer\MembershipAggregationPeriod` | `Customer\Membership\AggregationPeriod` |
| `Customer\MembershipProgress` | `Customer\Membership\Progress` |
| `Customer\NextMembership` | `Customer\Membership\NextMembership` |
| `Delivery\DeliveryDate` | `Delivery\Date\Date` |
| `Delivery\DeliveryDateDays` | `Delivery\Date\Days` |
| `Delivery\DeliveryDateTimes` | `Delivery\Date\Times` |
| `Gift\GiftCard` | `Gift\Card` |
| `Gift\GiftNoshi` | `Gift\Noshi` |
| `Gift\GiftType` | `Gift\Type` |
| `Gift\GiftWrapping` | `Gift\Wrapping` |
| `Product\ProductStocksIncrementInput` | `Product\StocksIncrementInput` |
| `Product\ProductVariantInput` | `Product\VariantInput` |
| `Product\Variant` | `Product\Variant\Variant` |
| `Product\VariantOption` | `Product\Variant\Option` |
| `Product\VariantUpdateInput` | `Product\Variant\VariantUpdateInput` |
| `Product\VariantSearchParameters` | `Product\Variant\SearchParameters` |
| `Product\Option` | `Product\Option\Option` |
| `Product\OptionCreateInput` | `Product\Option\OptionCreateInput` |
| `Product\OptionValue` | `Product\Option\Value\Value` |
| `Product\OptionValueCreateInput` | `Product\Option\Value\ValueCreateInput` |
| `Product\Pickup` | `Product\Pickup\Pickup` |
| `Product\PickupInput` | `Product\Pickup\PickupInput` |
| `Product\ProductImage` | `Product\Image\Image` |
| `Product\Advertising` | `Product\Advertising\Advertising` |
| `Product\AdvertisingSearchParameters` | `Product\Advertising\SearchParameters` |
| `Product\Group` | `Product\Group\Group` |
| `Product\GroupInput` | `Product\Group\GroupInput` |
| `Product\Category` | `Product\Category\Category` |
| `Product\BigCategory` | `Product\Category\BigCategory` |
| `Product\CategoryInput` | `Product\Category\CategoryInput` |
| `Product\SmallCategory` | `Product\Category\SmallCategory` |
| `Product\CategoryChildInput` | `Product\Category\ChildInput` |
| `Stock\Stock` | `Product\Stock\Stock` |
| `Stock\SearchParameters` | `Product\Stock\SearchParameters` |
| `Product\MetaTag` | `Common\MetaTag` |
| `Product\MetaTagInput` | `Common\MetaTagInput` |

`Product\Variant` → `Product\Variant\Variant` のように、旧クラス名と新しい名前空間が同じ名前になるものがある。
旧名はクラスの別名として引き続き使える。

`Sale\Delivery` と `Delivery\Delivery`、`Sale\CustomerCreateInput` と `Customer\CustomerCreateInput`、
`Gift\Card` と `Payment\Card` のように、別の名前空間に同じ名前のクラスがある。両方を使うファイルでは
`use ... as` で別名を付けること。

## 0.14.0: 要求側入力クラスの改名（0.24.0 で別名を削除）

書き込み入力クラスの命名を `<対象><操作>Input` に統一した
（[ADR 0016](adr/0016-unify-request-input-entity-names.md)）。このとき残した次の旧名の別名は、0.24.0 で削除した
（[ADR 0030](adr/0030-place-entities-by-api-path.md)）。旧名での生成・型判定・`unserialize()` はできない。
新しいクラス名に書き換えること。

| 削除した旧クラス名 | 新クラス名（0.24.0） |
| --- | --- |
| `Product\OptionInput` | `Product\Option\OptionCreateInput` |
| `Product\OptionValueInput` | `Product\Option\Value\ValueCreateInput` |
| `Product\VariantInput` | `Product\Variant\VariantUpdateInput` |
| `Sales\SaleUpdater` | `Sale\SaleUpdateInput` |
| `Sales\SaleDeliveryUpdater` | `Sale\DeliveryUpdateInput` |

`Product\VariantInput` は、0.24.0 から商品更新の `variants[]` の要素（0.23.0 までの `Product\ProductVariantInput`）の
クラス名である。0.23.0 までの `Product\VariantInput` とは別のクラスを指す。

0.20.0 で `SaleUpdateInput` から `id` を削除したため、`id` を含む 0.19.0 以前の `Sales\SaleUpdateInput` の直列化
データを `unserialize()` すると、PHP 8.2 以降では動的プロパティの非推奨警告が出る。復元自体はでき、`id` は配列化
にも送信にも使われない（[ADR 0022](adr/0022-take-sale-id-as-update-argument.md)）。
