# 非推奨のクラス名の対応表

改名したクラスの旧名は、非推奨の別名として残している。旧名は次のメジャーな変更で削除する。新しいコードでは
新名を使うこと。

旧名での生成、旧名での `instanceof` と型宣言、旧名で `serialize()` されたデータの `unserialize()` は
いずれも従来どおり動作する（`unserialize()` の `allowed_classes` には旧名を渡すこと）。
ただし、クラス名の文字列に依存するコードは互換でない。`get_class()` の結果は新名になる一方、旧名の
`::class` は旧名の文字列のままなので、両者の比較は成立しなくなる。

クラス名はいずれも `Shimoning\ColorMeShopApi\Entities\` 以下である。

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

## 0.14.0: 要求側入力クラスの改名

書き込み入力クラスの命名を `<対象><操作>Input` に統一した
（[ADR 0016](adr/0016-unify-request-input-entity-names.md)）。

| 旧クラス名 | 新クラス名 |
| --- | --- |
| `Product\OptionInput` | `Product\Option\OptionCreateInput`（0.14.0 から 0.23.0 までは `Product\OptionCreateInput`） |
| `Product\OptionValueInput` | `Product\Option\Value\ValueCreateInput`（0.14.0 から 0.23.0 までは `Product\OptionValueCreateInput`） |
| `Product\VariantInput` | `Product\Variant\VariantUpdateInput`（0.14.0 から 0.23.0 までは `Product\VariantUpdateInput`） |
| `Sales\SaleUpdater` | `Sale\SaleUpdateInput`（0.14.0 から 0.23.0 までは `Sales\SaleUpdateInput`） |
| `Sales\SaleDeliveryUpdater` | `Sale\DeliveryUpdateInput`（0.14.0 から 0.23.0 までは `Sales\SaleDeliveryUpdateInput`） |

作成と更新で共用する `ProductInput` / `GroupInput` / `CategoryInput` / `CategoryChildInput` / `PickupInput` と、
子要素の `MetaTagInput` は据え置いた（`ProductInput` は 0.22.0 で作成用と更新用に分けた）。検索条件の
`SearchParameters` 系も対象外である。

0.20.0 で `SaleUpdateInput` から `id` を削除したため、`id` を含む旧データの `SaleUpdater`（および 0.19.0 以前の
`SaleUpdateInput`）を `unserialize()` すると、PHP 8.2 以降では動的プロパティの非推奨警告が出る。復元自体は
でき、`id` は配列化にも送信にも使われない（[ADR 0022](adr/0022-take-sale-id-as-update-argument.md)）。
