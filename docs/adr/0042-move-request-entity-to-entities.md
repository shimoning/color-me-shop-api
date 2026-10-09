# ADR 0042: RequestEntity を Entities\RequestEntity に移す

- 状態: 採用
- 決定日: 2026-10-10

## 文脈

[ADR 0014](0014-model-product-write-api.md) で、利用者が送信する値を組み立てる Entity の印として
`Contracts\RequestEntity` を設けた。`Contracts` には、このインターフェースだけが置かれていた。

ほかの基底の型は、属する分類のフォルダに置かれている。値オブジェクトの `Value` / `FallbackValue` は `Values`、
enum の `FallbackEnum` は `Constants`、Entity の基底 `Entity` は `Entities`、Service の基底 `Service` は `Services`
にある。`Contracts` だけが「型の種類」で分けたフォルダで、ほかと分け方の軸が違っていた。

`RequestEntity` は、利用者の Entity が `implements` し、`instanceof` や型宣言で参照しうる公開の型である。
旧名の別名の仕組み（[ADR 0016](0016-unify-request-input-entity-names.md)）はクラスだけを想定していた。

## 判断

- **`Contracts\RequestEntity` を `Entities\RequestEntity` に移し、`Contracts` を廃止する。** 基底の型を、属する
  分類のフォルダに置く規則に揃える。利用者の指定による。出典: `ddc8801`。
- 旧名 `Contracts\RequestEntity` は、ADR 0016 と同じ仕組みで非推奨の別名として残し、次のメジャーな変更で削除する。
  別名の仕組みは、クラスに加えてインターフェースも扱う。出典: `ddc8801`。
- 旧名で `implements` した利用者の Entity が新名の `instanceof` を満たし、要求側の直列化の契約に従うこと、
  ライブラリの入力 Entity が旧名の `instanceof` と型宣言を満たすことを確かめる。出典: `ddc8801`。

## 代替案と却下理由

- **`Contracts` のままにする案**は、互換性の影響がない。インターフェースを `Contracts` に置くのは PHP の
  ライブラリでよくある構成で、意味として誤りでもない。しかしこのライブラリでは中身が 1 つだけで、ほかの基底の型と
  分け方が揃わない。
- **`Entities\Contracts\RequestEntity` に置く案**は、印が増えたときにまとめやすい。しかし今は 1 つだけで、
  `Entity` と同じ階層に置けば足りる。
- **旧名を今回で廃止する案**は、別名の仕組みを直さずに済む。しかし利用者の `implements` と `instanceof` が
  予告なく壊れ、これまでの改名（ADR 0016、ADR 0031）の扱いとも揃わない。

## 帰結

基底の型が、すべて属する分類のフォルダに置かれる。

次の点が変わる。

- `Contracts\RequestEntity` を参照していたコードは、旧名のままでも動作するが、非推奨になる
- `get_class()` や `ReflectionClass::getName()` の結果は新名になる。インターフェース名の文字列に依存するコードは
  互換でない（ADR 0029 と同じ）

## 関連

- [ADR 0000: アーキテクチャ上の意思決定を記録する](0000-record-architecture-decisions.md)
- [ADR 0014: 商品書き込み API の入力と空レスポンスを表現する](0014-model-product-write-api.md)
- [ADR 0016: 要求側入力 Entity の命名を統一し、旧名を非推奨の別名で残す](0016-unify-request-input-entity-names.md)
- [ADR 0031: 受注の Service を Services\Sale に改名する](0031-rename-sales-service-to-sale.md)
- [非推奨のクラス名の対応表](../class-aliases.md)
- 実装とテストの出典コミット: `ddc8801`
