# 非推奨のクラス名の対応表

改名したクラスの旧名は、非推奨の別名として残している。旧名は次のメジャーな変更で削除する。新しいコードでは
新名を使うこと。

旧名での生成、旧名での `instanceof` と型宣言、旧名で `serialize()` されたデータの `unserialize()` は
いずれも従来どおり動作する（`unserialize()` の `allowed_classes` には旧名を渡すこと）。

クラス名はいずれも `Shimoning\ColorMeShopApi\Entities\` 以下である。

## 0.24.0: 親の名前の接頭辞を外した改名

名前空間の主 Entity の名前を、クラス名の接頭辞に付けないように揃えた
（[ADR 0029](adr/0029-drop-parent-prefix-from-entity-names.md)）。

| 旧クラス名 | 新クラス名 |
| --- | --- |
| `Sales\SaleApplication` | `Sales\Application` |
| `Sales\SaleCustomization` | `Sales\Customization` |
| `Sales\SaleDelivery` | `Sales\Delivery` |
| `Sales\SaleDetail` | `Sales\Detail` |
| `Sales\SaleSegment` | `Sales\Segment` |
| `Sales\SaleShopCoupon` | `Sales\ShopCoupon` |
| `Sales\SaleTotals` | `Sales\Totals` |
| `Sales\SaleCustomerCreateInput` | `Sales\CustomerCreateInput` |
| `Sales\SaleDeliveryCreateInput` | `Sales\DeliveryCreateInput` |
| `Sales\SaleDeliveryUpdateInput` | `Sales\DeliveryUpdateInput` |
| `Sales\SaleDetailCreateInput` | `Sales\DetailCreateInput` |
| `Gift\GiftCard` | `Gift\Card` |
| `Gift\GiftNoshi` | `Gift\Noshi` |
| `Gift\GiftType` | `Gift\Type` |
| `Gift\GiftWrapping` | `Gift\Wrapping` |
| `Delivery\DeliveryDate` | `Delivery\Date` |
| `Delivery\DeliveryDateDays` | `Delivery\DateDays` |
| `Delivery\DeliveryDateTimes` | `Delivery\DateTimes` |
| `Product\ProductStocksIncrementInput` | `Product\StocksIncrementInput` |

`Sales\Delivery` と `Delivery\Delivery`、`Sales\CustomerCreateInput` と `Customer\CustomerCreateInput`、
`Gift\Card` と `Payment\Card` のように、別の名前空間に同じ名前のクラスがある。両方を使うファイルでは
`use ... as` で別名を付けること。

`Product\ProductImage` と `Product\ProductVariantInput` は改名していない。

## 0.14.0: 要求側入力クラスの改名

書き込み入力クラスの命名を `<対象><操作>Input` に統一した
（[ADR 0016](adr/0016-unify-request-input-entity-names.md)）。

| 旧クラス名 | 新クラス名 |
| --- | --- |
| `Product\OptionInput` | `Product\OptionCreateInput` |
| `Product\OptionValueInput` | `Product\OptionValueCreateInput` |
| `Product\VariantInput` | `Product\VariantUpdateInput` |
| `Sales\SaleUpdater` | `Sales\SaleUpdateInput` |
| `Sales\SaleDeliveryUpdater` | `Sales\DeliveryUpdateInput`（0.14.0 から 0.23.0 までは `Sales\SaleDeliveryUpdateInput`） |

作成と更新で共用する `ProductInput` / `GroupInput` / `CategoryInput` / `CategoryChildInput` / `PickupInput` と、
子要素の `MetaTagInput` は据え置いた（`ProductInput` は 0.22.0 で作成用と更新用に分けた）。検索条件の
`SearchParameters` 系も対象外である。

0.20.0 で `SaleUpdateInput` から `id` を削除したため、`id` を含む旧データの `SaleUpdater`（および 0.19.0 以前の
`SaleUpdateInput`）を `unserialize()` すると、PHP 8.2 以降では動的プロパティの非推奨警告が出る。復元自体は
でき、`id` は配列化にも送信にも使われない（[ADR 0022](adr/0022-take-sale-id-as-update-argument.md)）。
