# ADR 0008: 構造化されたレスポンスフィールドを Entity として表現する

- 状態: 採用
- 決定日: 2026-09-15

## 文脈

公式 OpenAPI と `src/Entities` の突合で、`sale.segment`、`customer.membership`、
`productCategory.meta_tag` など、未実装の `object` および object 配列が見つかった。
これらを PHP の素の `array` として公開すれば変更量は小さいが、既存コードは `customer`、
`sale_deliveries`、`delivery.charge`、`payment.card` などの構造化フィールドを専用 Entity に変換し、
型検証と getter を提供している。監査基点の実装: `60def840e555a17890af3d7cfa896c4d62e62538`。

また、`customer.external_accounts` は OpenAPI で `array` かつ `nullable: true` である。
現行の `Entity::build()` は nullable な object 配列の `null` を空配列へ変換するため、そのまま
`OBJECT_FIELDS` に登録すると仕様上の `null` と空配列を区別できない。監査基点の実装:
`60def840e555a17890af3d7cfa896c4d62e62538`。

## 判断

- OpenAPI の `object` で、利用者が内部フィールドを参照するレスポンス項目には専用 Entity を定義する。
  object 配列は PHP のプロパティを `array` とし、PHPDoc で `list<Entity>` を表現して
  `OBJECT_FIELDS` により要素を変換する。既存の構造化フィールドと同じ公開契約に揃えるためである。
  出典: `60def840e555a17890af3d7cfa896c4d62e62538`。
- ワイヤ形式と意味が同じ構造は共通 Entity を再利用する。今回の `Category` と `Group` の
  `meta_tag` は `Product\MetaTag` を共有する。一方、名前が似ていても意味やフィールドが異なる構造は
  リソース名前空間内の別 Entity とする。出典: `60def840e555a17890af3d7cfa896c4d62e62538`。
- `nullable: true` の object は nullable な Entity 型とし、`OBJECT_FIELDS` に `nullable` を指定する。
  nullable な object 配列は `null` と空配列を保持し分ける。`external_accounts` を追加する前に、
  `Entity::build()` の nullable 配列変換もこの契約を満たすよう変更し、横断テストで固定する。
  [ADR 0002](0002-entity-nullability-from-openapi.md) の判断を構造化フィールドにも適用するためである。
  出典: `60def840e555a17890af3d7cfa896c4d62e62538`。
- object の内部に列挙値がある場合も `src/Constants` の既存 enum を優先し、該当するものがなければ
  値の意味と将来の追加可能性を確認して専用 enum の追加を判断する。今回の
  `external_accounts[].provider` (`0`: LINE) に再利用可能な既存 enum はない。
  出典: `60def840e555a17890af3d7cfa896c4d62e62538`。

## 代替案と却下理由

- 新規フィールドをすべて素の `array` とする案は、既存の nested Entity と公開方法が不統一になり、
  内部フィールドの型不正を構築時に検出できないため却下する。
- object ごとに汎用の `ObjectValue` を一つ導入する案は、フィールドごとの意味と getter の型を
  表現できず、利用者がキー文字列へ依存するため却下する。
- nullable な object 配列の `null` を空配列へ正規化する案は、「関連なし」と「関連はあるが0件」を
  区別できず、OpenAPI の `nullable: true` と [ADR 0002](0002-entity-nullability-from-openapi.md) に
  反するため却下する。

## 帰結

追加フィールドにも既存 Entity と同じ構築時検証と型付き getter を提供できる。一方で、単純な
プロパティ追加より新規クラスとテストが増え、`external_accounts` の追加には共通変換処理の修正を
先行させる必要がある。新しい public Entity 名は公開 API になるため、命名と再利用範囲を各
リソースの実装コミットで確定する。

## 関連

- [ADR 0002: Entity の null 許容性を OpenAPI に合わせる](0002-entity-nullability-from-openapi.md)
- [ADR 0003: 暗黙変換より意味上正しい型を優先する](0003-prefer-semantic-types-over-legacy-coercion.md)
- [ADR 0004: Entity の `toArray()` は往復可能な直列化ではない](0004-entity-to-array-is-not-round-trippable.md)
- [Entity フィールド監査](../entity-field-audit.md)
- 公式 OpenAPI: <https://api.shop-pro.jp/v1/spec/open_api.json>
- 監査基点コミット: `60def840e555a17890af3d7cfa896c4d62e62538`
