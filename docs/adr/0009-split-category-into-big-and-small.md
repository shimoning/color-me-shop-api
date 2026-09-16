# ADR 0009: Category を BigCategory と SmallCategory に分割する

- 状態: 採用
- 決定日: 2026-09-16

## 文脈

2026-09-12 にテスト用ショップの実 API を検証した結果、親カテゴリーと子カテゴリーではレスポンスの
スキーマが異なることが判明した。確認した親カテゴリー2件はいずれも `id_small = 0` で、非空の
`children` キーを持っていた。一方、子カテゴリー4件の `id_small` はすべて1以上で、`children` キー
自体がなかった。`meta_tag` は親カテゴリー2件中1件と子カテゴリー4件すべてに存在したため、親子の
判別子にはできない。

現行の `Category::$children` は非 nullable の `array` である。子カテゴリーの実レスポンスには
`children` キーがないため、子カテゴリーで `getChildren()` を呼ぶと必ず `MissingFieldException` が
送出される。この不整合が Issue #27 で報告された。

公式 OpenAPI も親を `productCategory`、子を `productCategoryChild` という別スキーマで定義している。
ただし、両スキーマには `discriminator` と `required` がなく、`id_small` に `const` もない。さらに
子側の example でも `id_small` が0になっており、OpenAPI だけでは親子を機械的に判別できる保証がない。

## 判断

以下は実装前の設計判断であり、判断そのものを実装したコミットはまだ存在しない。実装は Issue #27 で
行う予定である。各判断の出典には、その前提となる現行実装または検証結果を含むコミットを示す。

- 親子の判別基準には `id_small === 0` を使う。0なら大カテゴリー、それ以外の `int` なら
  小カテゴリーとする。これは公式説明の「大カテゴリーなら0」と実データの双方に合致する。
  出典: `0ca343e`、`076a318`。
- 基底の `Entities\Product\Category` を abstract にする。既存利用者への破壊的変更を許容し、
  親子どちらでもない `Category` の生成を型で防ぐ。出典: `0ca343e`。
- `getChildren()` は `Entities\Product\BigCategory` のみに置く。小カテゴリーには、実 API に存在しない
  `children` の getter を公開しない。出典: `0ca343e`、`faafff9`。
- 生成には静的ファクトリ `Category::fromArray()` を使う。判別ロジックをドメイン型に一元化し、
  `Services\Product` によるトップレベル要素の変換と `BigCategory` による子要素の変換で再利用する。
  汎用の `Collection` と `Page` は変更しない。出典: `0ca343e`、`765a114`、`ae36b5a`。
- `id_small` が欠損している場合は `MissingFieldException`、値が `int` でない場合は
  `InvalidFieldException` とする。文字列 `"0"`、`null`、配列などを受理せず、既定で
  `BigCategory` にフォールバックしない。フォールバックは不正な応答の誤分類を隠すためである。
  出典: `f3f5845`。
- `BigCategory::getChildren()` の戻り値は `SmallCategory[]` の配列を維持する。
  `Collection<SmallCategory>` へ変更すると、現行の `Collection` は `json_encode` で `{}` になり、
  配列の添字、`foreach`、`count`、`array_map` を使う既存コードの互換性も壊れるため採用しない。
  出典: `0ca343e`、`a4c368f`、`dc116bb`。
- `meta_tag` は基底の `Category` に置く。[公式 OpenAPI](https://api.shop-pro.jp/v1/spec/open_api.json) の
  `components.schemas.productCategory.properties.meta_tag` と
  `components.schemas.productCategoryChild.properties.meta_tag`（2026-09-16 確認）、および実データの
  双方で親子に存在し得る共通フィールドだからである。出典: `076a318`、`8e5cdbd`。
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
  共通基盤、例外のラップ、PHPDoc、テストに及ぶ変更範囲が最大になる。現時点の一用途には過剰なため
  却下した。
- 基底 `Category` を具象のまま残す方法は、`new Category([...])` の互換性を保てる一方、親子の
  どちらでもない `Category` が存在し得るため却下した。

## 帰結

この判断は破壊的変更として次のマイナーバージョンで実装する。次のコードは影響を受ける。

- `new Category([...])` は、`Category` が abstract になるため `Error` になる。
- `Collection::cast(Category::class, ...)` と `Page::build(Category::class, ...)` は、abstract クラスを
  直接生成できないため使えなくなる。
- `ReflectionClass` や DI で可変の class-string から `Category` を生成するコードは使えなくなる。
- `Category` 型のまま `getChildren()` を呼ぶコードは使えなくなり、`instanceof BigCategory` による
  narrow が必要になる。
- 子カテゴリーの `toArray()` から `children` キーが消える。現行では実レスポンスにキーがなくても
  `children=null` を含めているため、出力形状が変わる。

一方、`BigCategory` と `SmallCategory` はいずれも `Category` を継承するため、`instanceof Category` と
`Category` 型宣言は引き続き機能する。この点は実メモリ上の検証で確認済みである。

テストは、`ApiFieldNameTest` のカテゴリー項目を親子2スキーマへ分割する必要がある。
`EntityContractTest` は abstract になった `Category` ではなく、新しい具象2クラスを検査する構成へ
更新する。実装の統合順序は Issue #29 の `meta_tag` 対応を先に master へ入れ、その後に本判断を
実装する。逆順では Issue #29 を具象 `Category` 前提から作り直すことになる。

未確認事項は次のとおりであり、実装時にも OpenAPI だけから挙動を補完しない。

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
- [実 API のエラー応答調査](../api-error-responses.md)。主題はエラー応答だが、同じ実 API 検証で
  カテゴリーの親子スキーマに関する知見を得た。
