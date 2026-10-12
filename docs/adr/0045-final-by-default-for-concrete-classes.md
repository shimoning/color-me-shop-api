# ADR 0045: 継承しない具象クラスを final にし、既存のクラスは @final で予告する

- 状態: 採用
- 決定日: 2026-10-12

## 文脈

`src/` のクラスに `final` を付けるかどうかに、規則がなかった。`final` が付いていたのは `Aliases`、`Communicator\NoContent`、
`Values` の `Limit` の子 6 個と `Values\Product\Sort` の 9 個だけで、同じ `Values` でも `Furigana`、`DateTime`、`Scopes` には
付いていなかった。ほかの具象クラスは、ライブラリの中で継承されないものも含めて、継承できた。

継承については、次の判断がある。

- [ADR 0020](0020-accept-state-in-authorization-url.md) は、このライブラリが想定する拡張点を `ClientInterface` の
  コンストラクタ注入とし、Service や `Client` のサブクラス化ではないとした。
- [ADR 0023](0023-validate-scalar-array-elements-via-field-types.md) は、`Entity` の継承を互換性の契約の外とした。

PHP のライブラリでは、継承させる意図のない具象クラスを `final` にする流れがある。Symfony は既存のクラスを docblock の
`@final` で予告してから、次のメジャーな版で `final` にする。一方で API の SDK は、利用者がテストでモックできるように、
クライアントを `final` にしないことが多い。PHPUnit の `createMock()` は `final` のクラスをモックできず、このライブラリは
`Client` と Service のインターフェースを持たない。

## 判断

- **継承を前提にしたクラスは `abstract` に、ライブラリの中で継承しない具象クラスは `final` にする。** 新しく足すクラスは、
  最初から規則に従う。利用者の指定による。出典: `7ce446a`。
- **`Client` と Service は `final` にしない。** 利用者がテストでモックする対象だからである。ADR 0020 が想定外としたのは
  継承による拡張で、モックではない。`Services\OAuth\Options` は Service ではなく設定の入れ物なので、`final` の対象にする。
  利用者の指定による。出典: `7ce446a`。
- **ライブラリの中で継承される具象クラスは `final` にしない。** `Entities\Collection`、`Values\Limit`、`Values\Sort`、
  `Exceptions\ColorMeApiException`、`Exceptions\InvalidFieldException`、`Exceptions\MissingFieldException` が当たる。
  `Collection` 以外は src で直接生成していないが、利用者が生成していると壊れるため、`abstract` にはしない。出典: `7ce446a`。
- **規則に従っていない既存の具象クラスは、0.32.0 では docblock の `@final` で予告し、次のメジャーな版で `final` にする。**
  動作は変えない。利用者の指定による。出典: `7ce446a`。
- 規則はテストで守り、新しいクラスの `final` の付け忘れと、例外のクラスへの誤った `final` を検出する。
  出典: `7ce446a`、`8d9c69a`。

## 代替案と却下理由

- **`Client` と Service も `final` にする案**は、規則に例外がない。しかし利用者は `Client` をモックできなくなり、
  HTTP クライアントを差し替えて応答を組み立てるしかなくなる。インターフェースを足して `final` にする方法もあるが、
  公開する型が大きく増える。
- **Entity を継承できるまま残す案**は、利用者が Entity を継承してメソッドを足す使い方を残せる。しかし ADR 0023 で
  `Entity` の継承は契約の外としており、Entity だけを例外にする理由が弱い。Entity は配列から生成でき、モックする必要もない。
- **今回で `final` を付ける案**は、揃うのが早い。しかし継承している利用者のコードが予告なく壊れる。
- **値オブジェクトだけを `final` に揃える案**は、影響が最も小さい。しかし Entities と Communicator の規則がないままになる。

## 帰結

具象クラスを継承できるかどうかが、規則で決まる。新しいクラスは最初から `final` になり、テストが付け忘れを検出する。

次の点が変わる。

- 0.32.0 では動作は変わらない。`@final` のクラスを継承しているコードは、次のメジャーな版で動かなくなる
- PHPStan は `@final` のクラスの継承を `class.extendsFinalByPhpDoc` として指摘する。利用者は静的解析で予告に気づける

## 関連

- [ADR 0000: アーキテクチャ上の意思決定を記録する](0000-record-architecture-decisions.md)
- [ADR 0006: Entity のプロパティ解決を通常のインスタンスプロパティに統一する](0006-limit-entity-property-kinds.md)
- [ADR 0020: 認可 URL に state を受け取る](0020-accept-state-in-authorization-url.md)
- [ADR 0023: スカラー配列の要素型を FIELD_TYPES で宣言して検証する](0023-validate-scalar-array-elements-via-field-types.md)
- [ADR 0043: 基底の型と配置を揃える](0043-align-base-types-and-placement.md)
- 実装とテストの出典コミット: `7ce446a`、`8d9c69a`
