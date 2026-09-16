# ADR 0012: 実 API の観測に基づき Entity の null 許容を判断する

- 状態: 採用
- 決定日: 2026-09-16

## 文脈

[ADR 0002](0002-entity-nullability-from-openapi.md) は、非 null 許容型を一律には変更せず、
公式 OpenAPI の `nullable: true` と PHP 側の型を突き合わせると定めた。出典: `bd6f9c2`。

しかし、2026-09-12 にテスト用ショップの `GET /v1/categories` で収集した親カテゴリー2件のうち
1件には `meta_tag` キーがなかった。公式 OpenAPI は `meta_tag` 自体を nullable としていない。
非 nullable のままでは、この応答で getter が `MissingFieldException` になる。実測の出典:
[カテゴリー応答の観測記録](../api-category-structure.md)（`9167b12102c78f60a931d76051b373bd0e7cfa9d`）。
コードの出典: `6ad15c6`、`f56c22e`。

同様に、2026-09-16 にテスト用ショップの `GET /v1/payments` で収集した代引き設定では、
公式 OpenAPI が非 nullable とする `cod.fee_max` のキー欠損と明示的な `null` を確認した
（Issue #28）。実測の出典: [決済設定の観測記録](../api-payment-structure.md)
（`10a42835f188bfe04fb816822583cdffeea66a41`）。

## 判断

- 実 API でキー欠損または明示的な `null` が確認されたフィールドは、公式 OpenAPI に
  `nullable: true` がなくても nullable にする。正当な応答で構築や getter が失敗する利用者の
  不利益は、型契約を弱める不利益より大きい。出典: `9167b12102c78f60a931d76051b373bd0e7cfa9d`、
  `10a42835f188bfe04fb816822583cdffeea66a41`、`f56c22e`。
- nullable 化の根拠となる実測は、[ADR 0000](0000-record-architecture-decisions.md) に従い
  `docs/` の観測記録とそのコミット SHA で示す。公式 OpenAPI と異なる契約は、該当 getter の
  PHPDoc にも記す。出典: `076a318`、`e8fa4ec`、`9167b12102c78f60a931d76051b373bd0e7cfa9d`、
  `f56c22e`。
- nullable 化で受け入れるのはキー欠損と明示的な `null` に限る。その他の型不一致は従来どおり
  構築時に `InvalidFieldException` とし、観測していない不正値を暗黙に受け入れない。
  出典: `f56c22e`、`adb019d`。
- `OBJECT_FIELDS` で子 Entity を持つフィールドを nullable にする場合、falsy 値を一律に
  `null` にする既存の `nullable` ではなく、`null` のみを許す `allowNull` を使う。
  空オブジェクトは子 Entity として保持する。出典: `f56c22e`、`adb019d`。
- ADR 0002 の OpenAPI と型を突き合わせる原則は維持し、本 ADR は実測で欠損または
  `null` を確認した場合の例外条件を追加する。[ADR 0008](0008-model-structured-response-fields-as-entities.md)
  の構造化フィールドを Entity とする判断も維持する。一方、同 ADR が nullable な object に
  指定した `nullable` は空オブジェクトも `null` にするため、子 Entity で `null` のみを
  許す場合は本 ADR の `allowNull` の判断でその指定を更新する。出典: `bd6f9c2`、
  `a2b5814`、`e6e9812`、`f56c22e`。

## 代替案と却下理由

- OpenAPI の nullable 指定だけを根拠とする案は、観測済みの正当な応答で構築や getter が
  失敗するため採用しない。出典: `9167b12102c78f60a931d76051b373bd0e7cfa9d`、
  `10a42835f188bfe04fb816822583cdffeea66a41`。
- 子 Entity の nullable 化に既存の `nullable` を使う案は、空オブジェクトまで `null` に
  変換し、存在する構造を失うため採用しない。出典: `f56c22e`、`adb019d`。

## 帰結

この判断を適用したフィールドでは、欠損または `null` を含む応答を受け取れる。
`Category::$metaTag` では
欠損と明示的な `null` の getter 結果が `null` となり、空オブジェクトは `MetaTag` として
保持される。その他の型不一致は引き続き構築時のエラーとなる。出典: `f56c22e`、`adb019d`。

getter の戻り値が nullable になるため、利用者は `null` を扱う必要がある。
公式 OpenAPI との違いと実測の根拠を併せて示し、型契約を弱める範囲を追跡可能にする。

## 関連

- [ADR 0000: アーキテクチャ上の意思決定を記録する](0000-record-architecture-decisions.md)
- [ADR 0002: Entity の null 許容性を OpenAPI に合わせる](0002-entity-nullability-from-openapi.md)
- [ADR 0008: 構造化されたレスポンスフィールドを Entity として表現する](0008-model-structured-response-fields-as-entities.md)
- [カテゴリー応答の観測記録](../api-category-structure.md)（出典コミット: `9167b12102c78f60a931d76051b373bd0e7cfa9d`）
- [決済設定の観測記録](../api-payment-structure.md)（出典コミット: `10a42835f188bfe04fb816822583cdffeea66a41`）
- Issue #28
