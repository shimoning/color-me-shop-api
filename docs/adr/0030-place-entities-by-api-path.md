# ADR 0030: Entity の名前空間を API の URL に沿わせる

- 状態: 採用
- 決定日: 2026-10-06

## 文脈

Entity は API の領域ごとの名前空間に置いていたが、どの名前空間に置くかの規則はなかった。
バリエーション・オプション・ピックアップ・商品画像の Entity は、それぞれ専用の URL
（`/v1/products/{product_id}/variants` など）を持つのに、商品と同じ `Product` 名前空間に並んでいた。
そのため、クラス名に `Variant` / `Option` などの接頭辞を付けて区別していた（`VariantOption`、`OptionValue`
など）。受注は、URL が `/v1/sales` で、ほかの名前空間が単数形なのに、名前空間だけ複数形の `Sales` だった。

[ADR 0029](0029-drop-parent-prefix-from-entity-names.md) で、クラス名に親の名前の接頭辞を付けない規則を
決めた。しかし同じ名前空間に別の URL の Entity が混在したままでは、接頭辞を外すと名前が衝突する。
このため ADR 0029 では `Product\ProductImage` を例外として残していた。

## 判断

- **Entity の名前空間を、公式 API の URL のパスに沿わせる。** パスの区切りを単数形の PascalCase の名前空間に
  し（`/v1/sales` → `Sale`）、`{id}` の部分は無視する。サブリソースは入れ子の名前空間にし、クラスが 1 つ
  だけでも入れ子にする（`/v1/sales/stat` → `Sale\Stat\Stat`）。出典: `d187813`。
- 名前空間の主 Entity は、名前空間の最後の名前と同じクラス名にする（`Product\Variant\Variant`）。Input は
  ADR 0016 の `<対象><操作>Input` のままにする（`Product\Variant\VariantUpdateInput`）。`SearchParameters` は
  名前空間で区別できるため、接頭辞を付けない（`Product\Variant\SearchParameters`）。出典: `d187813`。
- 自前の URL を持たず、応答に埋め込まれるだけの Entity は、それを返す URL の名前空間に置く
  （`Sale\Detail`、`Delivery\Charge`、`Product\Image` など）。出典: `d187813`。
- **例外として、商品広告・グループ・カテゴリー・在庫は `Product` の下に置く。** URL は
  `/v1/product_advertisings`、`/v1/groups`、`/v1/categories`、`/v1/stocks` と独立しているが、利用者の指定に
  より `Product` の下に置く。出典: `d187813`。
  - 商品広告は `Product\Advertising\Advertising` とし、`ProductAdvertising` ではなく短い `Advertising` とする
  - 小カテゴリー（`/v1/categories/{category_id}/children`）は `Child` の階層を作らず、大カテゴリーと同じ
    `Product\Category` に置く。入力は `CategoryChildInput` から `ChildInput` にする
  - 在庫は `Product\Stock\Stock` とする
- グループとカテゴリーの両方が使う SEO メタタグ（`MetaTag` / `MetaTagInput`）は、特定の URL に属さないため
  `Entities\Common` に置く。出典: `d187813`。
- 商品画像の取得 API の要素は `Product\Image\Image` とし、商品本体に埋め込まれた `Product\Image` と並べる。
  クラス名と名前空間の名前が同じになるが、PHP では共存できる。ADR 0029 で例外として残した
  `Product\ProductImage` の名前は、この配置で解消する。出典: `d187813`。
- 0.23.0 までのクラス名は、ADR 0016 と同じ仕組みで非推奨の別名として残す。別名は 0.23.0 までに存在した名前
  から最終的な名前を直接指し、ADR 0029 で付けた未リリースの途中の名前（`Sales\Application` など）は別名に
  しない。出典: `d187813`。
- `Services` のクラス名と名前空間は変えない。出典: `d187813`。
- **ADR 0016 で 0.14.0 に残した 5 件の旧名の別名（`Product\OptionInput`、`Product\OptionValueInput`、
  `Product\VariantInput`、`Sales\SaleUpdater`、`Sales\SaleDeliveryUpdater`）を、このリリースで削除する。**
  ADR 0016 は次のメジャーな変更で削除するとしていたが、利用者の指定により前倒しする。出典: `99da427`。
- 空いた `Product\VariantInput` に、商品更新の `variants[]` の要素（0.23.0 までの `Product\ProductVariantInput`）を
  改名する。ADR 0029 では、この名前が 0.14.0 の旧名として使われていたため例外として残していた。
  `Product\ProductVariantInput` は非推奨の別名として残す。出典: `99da427`。

## 代替案と却下理由

- **ADR 0029 の改名だけで止め、名前空間は今のままにする案**は、変更が小さい。しかし `Product` に別の URL の
  Entity が混在し、接頭辞を外せない例外が残る。
- **商品広告・グループ・カテゴリー・在庫も URL どおり最上位の名前空間にする案**（`Group\Group`、
  `Category\Child\SmallCategory`、`Stock\Stock` など）は、規則に例外がない。しかし利用者の指定により、
  `Product` の下に置く。
- **クラスが 1 つしかない URL は入れ子にしない案**は、名前空間が浅くなる。しかしクラスが増えたときに移動が
  必要になり、どの URL を入れ子にするかの判断も要る。利用者の指定により、常に入れ子にする。
- **共用の `MetaTag` を `Product` に残す案**は、移動が要らない。しかし特定の URL に属さない Entity が `Product`
  に残る。利用者の指定により、共通の名前空間に置く。

## 帰結

Entity の置き場所を、API の URL から決められるようになる。同じ名前空間の中では、クラス名に親の名前や
URL の名前を重ねなくてよくなる。

次の点が変わる。

- 0.23.0 の 55 クラスの名前が変わる。旧名は別名として動作するが、クラス名の文字列に依存するコードは互換で
  ない（ADR 0029 と同じ）
- `Product\Variant` → `Product\Variant\Variant` のように、旧クラス名と新しい名前空間が同じ名前になるものが
  ある
- 名前空間が 1 段深くなる Entity が増え、`use` の行が長くなる
- 0.14.0 の 5 件の旧名は使えなくなる。旧名での生成・型判定、旧名で直列化されたデータの `unserialize()` は
  できない。`Product\VariantInput` は 0.23.0 までと別のクラス（バリエーション更新 API の入力ではなく、商品更新の
  `variants[]` の要素）を指すようになり、旧名のまま使っていたコードは型が合わずに失敗する
- 商品広告・グループ・カテゴリー・在庫の配置は、URL の規則の例外として残る

## 関連

- [ADR 0000: アーキテクチャ上の意思決定を記録する](0000-record-architecture-decisions.md)
- [ADR 0016: 要求側入力 Entity の命名を統一し、旧名を非推奨の別名で残す](0016-unify-request-input-entity-names.md)
- [ADR 0029: Entity のクラス名に親の名前の接頭辞を付けない](0029-drop-parent-prefix-from-entity-names.md)
- [非推奨のクラス名の対応表](../class-aliases.md)
- 実装とテストの出典コミット: `d187813`
- 0.14.0 の別名の削除と `VariantInput` への改名の出典コミット: `99da427`
