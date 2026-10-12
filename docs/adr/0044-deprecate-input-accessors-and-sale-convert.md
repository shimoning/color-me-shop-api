# ADR 0044: 入力 Entity の getter と setter、受注の convert() を非推奨にする

- 状態: 採用
- 決定日: 2026-10-12

## 文脈

入力 Entity（`Entities\RequestEntity` を実装する Entity）は 27 個ある。そのうち 25 個は、コンストラクタに配列を渡す
方式だけで組み立て、getter も setter も持たない。次だけが揃っていなかった。

- 受注の更新入力 `Sale\SaleUpdateInput` は、3 項目の setter と getter と、取得した受注から作る `convert()` を持つ。
- 受注のお届け先の更新入力 `Sale\DeliveryUpdateInput` は、23 項目の setter を持つ。getter は
  [ADR 0043](0043-align-base-types-and-placement.md) で、応答の `Sale\Delivery` を継承しなくしたときになくなった。
- 商品・在庫・商品の広告の検索条件 `SearchParameters` は、`getDisplayState()` を持つ。src では使っておらず、テストだけが
  enum への変換を確かめるために使っていた。

入力 Entity の値は、ライブラリが `toArray()` / `toArrayRecursive()` で送信内容にする。src で入力 Entity の getter を
呼ぶ箇所はない。部分更新の「渡した項目だけを送り、`null` も明示すれば送る」動作も、`Customer\CustomerUpdateInput` の
ようにコンストラクタだけで実現できている。

`convert()` は、取得した受注の `paid`、`point_state` と、すべてのお届け先の現在の値を写した `SaleUpdateInput` を作る。
README は「API が必要とする現在値を引き継げる」と説明していた。しかし受注の更新 API は、公式 OpenAPI の項目を 1 つずつ
送っても、送らなかった項目を消さず、変えなかった（[更新 API で送らなかった項目の扱いの実測記録](../api-partial-update-observation.md)
の「受注の全項目」、2026-10-12）。現在の値を引き継ぐ必要はない。さらに `convert()` は、すべてのお届け先の全項目を送るため、
取得してから更新するまでに管理画面などで変わった値を、古い値で上書きするおそれがある。送っても無視される `pref_name`、
`total_charge`、`tracking_url` も送る。

## 判断

- **入力 Entity は getter も setter も持たず、コンストラクタの配列で組み立てる。** 揃っていない次のメソッドを
  `@deprecated 0.31.0` にし、次のメジャーな変更で削除する。動作は変えない。利用者の指定による。出典: `ae1d0b1`。
  - `Sale\SaleUpdateInput` の `isPaid()`、`getPointState()`、`getSaleDeliveries()`、`setPaid()`、`setPointState()`、
    `setSaleDeliveries()`
  - `Sale\DeliveryUpdateInput` の 23 個の setter
  - 商品・在庫・商品の広告の `SearchParameters` の `getDisplayState()`
- **`Sale\SaleUpdateInput::convert()` を非推奨にする。** 送りたい項目だけをコンストラクタの配列で渡す。受注の更新 API が
  差分の更新として振る舞うことは実測で確かめた。利用者の指定による。出典: `ae1d0b1`、`6836c43`。
- 非推奨は、ほかの非推奨のメソッドと同じく docblock の `@deprecated` だけで示し、実行時の警告は出さない。出典: `ae1d0b1`。

## 代替案と却下理由

- **setter は残し、getter だけを非推奨にする案**は、項目名の補完と型の確認が効く利点を残せる。しかし setter を持つのは
  27 個のうち 2 個だけで、組み立て方が 2 通りになる。補完と型の確認は入力 Entity 全体の課題で、2 個にだけ setter が
  あっても解決しない。
- **`getSaleDeliveries()` だけを残す案**は、`convert()` で作ったあと一部のお届け先だけを書き換える使い方を残せる。しかし
  `convert()` 自体を非推奨にするので、その使い方も不要になる。
- **`convert()` を、送る項目を絞る形に作り直す案**は、取得した受注から組み立てる入口を残せる。しかし何を送るかは利用者が
  決めることで、受注の更新は送った項目だけを変えるため、現在の値から組み立てる必要がない。
- **非推奨にせず、今回で削除する案**は、揃うのが早い。しかし利用者のコードが予告なく壊れ、これまでの非推奨の扱い
  （[ADR 0016](0016-unify-request-input-entity-names.md) など）とも揃わない。

## 帰結

入力 Entity の組み立て方が、コンストラクタの配列に揃う。非推奨のメソッドは次のメジャーな変更まで動作する。

次の点が変わる。

- 非推奨のメソッドを呼ぶコードは、静的解析や IDE で非推奨として示される
- README の受注の更新の例は、`convert()` と setter を使わず、送りたい項目だけをコンストラクタに渡す形になる

## 関連

- [ADR 0000: アーキテクチャ上の意思決定を記録する](0000-record-architecture-decisions.md)
- [ADR 0016: 要求側入力 Entity の命名を統一し、旧名を非推奨の別名で残す](0016-unify-request-input-entity-names.md)
- [ADR 0043: 基底の型と配置を揃える](0043-align-base-types-and-placement.md)
- [更新 API で送らなかった項目の扱いの実測記録](../api-partial-update-observation.md)
- 実装とテストの出典コミット: `ae1d0b1`
