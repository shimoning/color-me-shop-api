# ADR 0010: Category を BigCategory と SmallCategory に分割する

- 状態: 採用
- 決定日: 2026-09-16

## 文脈

2026-09-12 にテスト用ショップの実 API を検証した結果、親カテゴリーと子カテゴリーではレスポンスの
スキーマが異なることが判明した（[実測記録](../api-category-structure.md)）。確認した
親カテゴリー2件はいずれも `id_small = 0` で、非空の `children` キーを持っていた。一方、
子カテゴリー4件の `id_small` はすべて1以上で、`children` キー自体がなかった。`meta_tag` は
親カテゴリー2件中1件と子カテゴリー4件すべてに存在したため、親子の判別子にはできない。

現行の `Category::$children` は非 nullable の `array` である。子カテゴリーの実レスポンスには
`children` キーがないため、子カテゴリーで `getChildren()` を呼ぶと欠損フィールドとして扱われる。
この不整合が Issue #27 で報告された。

公式 OpenAPI も親を `productCategory`、子を `productCategoryChild` という別スキーマで定義している。
ただし、両スキーマには `discriminator` と `required` がなく、`id_small` に `const` もない。さらに
子側の example でも `id_small` が0になっており、OpenAPI だけでは親子を機械的に判別できる保証がない。

## 判断

実 API の観測結果の出典は `docs/api-category-structure.md` を更新したコミット
`9167b12102c78f60a931d76051b373bd0e7cfa9d` とする。

- 親子の判別基準には `id_small === 0` を使う。0なら大カテゴリー、それ以外の `int` なら
  小カテゴリーとする。これは公式説明の「大カテゴリーなら0」と実データの双方に合致する。
  コードの出典: `0ca343e`。実データの出典: `docs/api-category-structure.md`。
- 基底の `Entities\Product\Category` を abstract にする。既存利用者への破壊的変更を許容し、
  親子どちらでもない `Category` の生成を型で防ぐ。出典: `0ca343e`。
- `$children` プロパティと `getChildren()` は `Entities\Product\BigCategory` のみに置く。
  小カテゴリーには、実 API に存在しない `children` を公開せず、`toArray()` にも出さない。
  出典: `0ca343e`、`faafff9`、`326f1ac`、`docs/api-category-structure.md`。
- 生成には静的ファクトリ `Category::fromArray()` を使う。判別ロジックをドメイン型に一元化し、
  `Services\Product` によるトップレベル要素の変換と `BigCategory` による子要素の変換で再利用する。
  汎用の `Collection` と `Page` は変更しない。出典: `0ca343e`、`765a114`、`ae36b5a`。
- `children` は `OBJECT_FIELDS` に委ねず、親側で各要素を
  `Category::fromArray()` に通し、`SmallCategory` として扱えることを確認する。
  現行の `Entity::build()` は指定クラスを直接生成するため、abstract な `Category` を指定できず、
  `SmallCategory` を指定するとファクトリを迂回する。親側で配列を正規化する `Delivery\Charge` の
  前例にも沿う。出典: `8a6560f`、`05a9206`、`b89f3ce`。
- `id_small` が欠損または `int` 以外の場合は不正な応答として扱う。文字列 `"0"`、`null`、配列などを
  受理せず、既定で `BigCategory` にフォールバックしない。フォールバックは誤分類を隠すためである。
  出典: `f3f5845`。
- `BigCategory::getChildren()` の戻り値は `SmallCategory[]` の配列を維持する。
  `Collection<SmallCategory>` へ変更すると、現行の `Collection` は `json_encode` で `{}` になり、
  配列の添字、`foreach`、`count`、`array_map` を使う既存コードの互換性も壊れるため採用しない。
  出典: `0ca343e`、`a4c368f`、`dc116bb`。
- `meta_tag` は基底の `Category` に置く。[公式 OpenAPI](https://api.shop-pro.jp/v1/spec/open_api.json) の
  `components.schemas.productCategory.properties.meta_tag` と
  `components.schemas.productCategoryChild.properties.meta_tag`（2026-09-16 確認）、および実データの
  双方で親子に存在し得る共通フィールドだからである。実データの出典:
  `docs/api-category-structure.md`。
- 具象型の名前は `BigCategory` と `SmallCategory`、名前空間は `Entities\Product\` とする。公式の
  「大カテゴリー」「小カテゴリー」という呼称、および `id_big` と `id_small` に整合する。
  出典: `0ca343e`。

## 代替案と却下理由

- `children` を nullable にする当初の Issue #27 の案は、型で親子を区別できず、子カテゴリーにも
  `getChildren()` が存在する不自然さが残るため却下した。
- 案Aとして `Services\Product` 側で生配列を判別する方法は、最小コストで実装できる一方、判別と
  例外処理が Service へ漏れる。`BigCategory` の子変換や将来のエンドポイントで処理が重複し、
  Service 外の利用者へ共通の生成経路も提供できないため却下した。
- 案Cとして `Collection::castUsing(callable)` などの汎用基盤を追加する方法は、再利用性が高い一方、
  共通基盤に及ぶ変更範囲が大きい。現時点の一用途には過剰なため却下した。
- 基底 `Category` を具象のまま残す方法は、`new Category([...])` の互換性を保てる一方、親子の
  どちらでもない `Category` が存在し得るため却下した。

## 帰結

`Category` の直接生成と、`Category::class` を指定する汎用変換は利用できなくなる。
`Category` 型からの `getChildren()` 呼び出しには `BigCategory` への型の絞り込みが必要となる。
子カテゴリーの `toArray()` からは `children` キーが消える。一方、両具象型は `Category` を継承し、
`Category` 型宣言は引き続き機能する。

未確認事項は次のとおりである。

- 本番 API がトップレベルに小カテゴリーを返す例があるか。
- 子を持たない親カテゴリーが `children` キーを省略するか、空配列を返すか。
- 公式 OpenAPI で `required` が未指定であり、子スキーマの example の `id_small` が0であることを
  どのように解釈すべきか。

## 関連

- Issue #27
- Issue #29
- [公式 OpenAPI](https://api.shop-pro.jp/v1/spec/open_api.json) の `productCategory` /
  `productCategoryChild`
- [ADR 0002: Entity の null 許容性を OpenAPI に合わせる](0002-entity-nullability-from-openapi.md)
- [ADR 0004: Entity の `toArray()` は往復可能な直列化ではない](0004-entity-to-array-is-not-round-trippable.md)
- [ADR 0006: Entity のプロパティ解決を通常のインスタンスプロパティに統一する](0006-limit-entity-property-kinds.md)
- [実 API のカテゴリー応答構造の実測記録](../api-category-structure.md)（出典コミット:
  `9167b12102c78f60a931d76051b373bd0e7cfa9d`）。
