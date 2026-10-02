# ADR 0027: 商品更新の stocks と variants を入力 Entity で表す

- 状態: 採用
- 決定日: 2026-10-02

## 文脈

商品更新の入力 `Entities\Product\ProductUpdateInput`（[ADR 0026](0026-split-product-input-into-create-and-update.md)）は、
次の 3 項目をコンストラクタで個別に検証していた。

| 項目 | 公式 OpenAPI の定義 | 個別の検証 |
| --- | --- | --- |
| `group_ids` | integer の配列 | 連番の配列であること、要素が int であること |
| `stocks` | 整数、または `{"increment": int}` | object なら `increment` だけを持ち、値が int であること |
| `variants` | `option1_value`（string）/ `option2_value`（string）/ `stocks`（`stocks` と同じ 2 形状）を持つ object のリスト | 連番の配列であること、各要素が上の 3 キーだけを持ち空でないこと、`stocks` が `null` でないこと |

`stocks` と `variants` は配列の形のまま保持され、型は `int|array|null` / `?array` だった。

その後、基底 `Entity` は次のことを宣言で扱えるようになった。

- スカラー配列の要素の型の検証（[ADR 0023](0023-validate-scalar-array-elements-via-field-types.md)）
- 入れ子に組み立て済みのインスタンスを受け付けること（[ADR 0024](0024-model-sale-create-api.md)）
- 要求側の配列で、連番でないものを拒否すること（[ADR 0025](0025-reject-non-list-arrays-in-requests.md)）

`group_ids` の個別の検証は、宣言で置き換えられる。一方 `stocks` は「整数か object か」の合併型で、これまでの
`FIELD_TYPES` では表せなかった。

## 判断

- **`group_ids` は `FIELD_TYPES` に `['array' => true, 'scalar' => 'int']` と宣言し、個別の検証をやめる。**
  連番と要素の型の検証は基底 `Entity` が行う。公開メッセージは変わらない。出典: `0af1905`。
- **`stocks` の object を、入力 Entity `ProductStocksIncrementInput`（`increment` だけを持つ）で表す。**
  商品直下の `stocks` と `variants[].stocks` は同じ形なので、同じ Entity を使う。`stocks` は「整数」か
  「この Entity」のどちらかを持つ。出典: `0af1905`。
- `FIELD_TYPES` の `entity` の宣言に、指定したスカラー型の値はそのまま通す指定（`orScalar`）を加える。
  整数ならそのまま、配列なら Entity に組み立て、組み立て済みのインスタンスはそのまま受け付ける。既存の
  `scalar`（スカラー配列を表す）とは意味が異なるため、別の指定にする。出典: `0af1905`。
- **`variants` の要素を、入力 Entity `ProductVariantInput` で表す。** 3 つの任意の項目を持ち、`stocks` は
  上と同じく整数か `ProductStocksIncrementInput`。出典: `0af1905`。
- **定義にないキー、空の要素、`variants[].stocks` の `null` は、これまでどおり拒否する。** 他の入力クラスは
  宣言のないキーを黙って無視するが、この 2 つの Entity では個別の検証の厳しさを保つ。利用者と協議のうえ
  決めた。出典: `0af1905`。
- `variants` に連想配列を 1 つ渡した場合も拒否する。入れ子の Entity の配列は、すべてのキーが文字列の
  連想配列を要素 1 件として包むのが既存の扱い（ADR 0025）だが、`variants` はこれまで連番の配列だけを
  受け付けていたため、その厳しさを保つ。そのための宣言の指定（`strictList`）を加える。出典: `0af1905`。
- 配列で渡す書き方はそのまま使え、送信する JSON は変わらない。出典: `0af1905`。

## 代替案と却下理由

- **個別の検証をコンストラクタに残す案**は、変更がない。しかし `stocks` と `variants` の中身が型のない
  配列のままで、同じ検証（`stocks` の object の形）が 2 か所に書かれていた。宣言と Entity に寄せれば、
  他の入力と同じ仕組みで扱える。
- **`stocks` を値オブジェクト（`Values\` 配下）で表し、整数と増減の両方を 1 つの型にする案**は、合併型を
  使わずに済む。しかし `increment` は API の object の 1 項目であり、他の入れ子の object と同じく入力
  Entity で表すほうが揃う。利用者の指示により Entity で表す。
- **定義にないキーを、他の入力クラスと同じく無視する案**は、形が揃う。しかしこれまで例外になっていた
  入力が通るようになり、検証が緩む。利用者と協議のうえ採用しない。
- **`variants` の要素の名前を `ProductVariantStocksInput` などにする案**は、既存の `VariantUpdateInput`
  （バリエーション単体の更新 API 用）との区別が名前から分かりやすい。利用者の指定により
  `ProductVariantInput` とする。

## 帰結

`ProductUpdateInput` のコンストラクタの個別の検証はなくなり、`group_ids` / `stocks` / `variants` は宣言と
入力 Entity で扱われる。`stocks` と `variants` は、配列のほかに組み立て済みのインスタンスでも渡せる。

次の点が変わる。

- `stocks` と `variants` の誤りの例外メッセージで、期待する型の表示が配列の形
  （`int|array{increment: int}` など）から新しいクラス名に変わる。例外の種類と、拒否される入力は変わらない
- `group_ids` の要素の型の誤りでは、`previous` の文言が基底の共通処理のものになる。公開メッセージは
  変わらない
- 基底 `Entity` に `orScalar` と `strictList` の指定が加わる。どちらも宣言したフィールドにだけ効き、既存の
  フィールドの挙動は変わらない

この 2 つの Entity だけが、宣言のないキーを拒否する。入力クラス全体で揃えるかどうかは、必要になった
時点で判断する。

## 関連

- [ADR 0000: アーキテクチャ上の意思決定を記録する](0000-record-architecture-decisions.md)
- [ADR 0012: 実 API の観測に基づき Entity の null 許容を判断する](0012-allow-nullability-from-api-observations.md)
- [ADR 0023: スカラー配列の要素型を FIELD_TYPES で宣言して検証する](0023-validate-scalar-array-elements-via-field-types.md)
- [ADR 0024: 受注作成 API の入力を表現する](0024-model-sale-create-api.md)
- [ADR 0025: 要求側の配列フィールドでキーが連番でない配列を拒否する](0025-reject-non-list-arrays-in-requests.md)
- [ADR 0026: 商品の入力を作成用と更新用に分ける](0026-split-product-input-into-create-and-update.md)
- 実装とテストの出典コミット: `0af1905`
