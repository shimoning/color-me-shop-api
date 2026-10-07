# ADR 0034: 商品の Service をサブリソースごとの Service に分ける

- 状態: 採用
- 決定日: 2026-10-07

## 文脈

`Services\Product` は 813 行あり、ほかの Service（最大で `Services\Sale` の 291 行）の 3 倍近くあった。
商品本体のほか、バリエーション・オプション（値を含む）・ピックアップ・画像・グループ・カテゴリー・
商品広告・在庫の API を 1 つのクラスで扱い、公開メソッドは約 40 個あった。カテゴリーの応答の判定、
ピックアップの入力の検証、画像の multipart 送信といった補助処理も同じクラスにあった。

[ADR 0030](0030-place-entities-by-api-path.md) で Entity をサブリソースごとの名前空間
（`Entities\Product\Variant` など）に分けたが、Service は 1 クラスのままだった。
[ADR 0032](0032-merge-stock-service-into-product-service.md) で在庫の Service を `Services\Product` に統合し、
[ADR 0033](0033-unify-client-and-service-method-names.md) でサブリソースのメソッド名を
`variantPage()` / `groupAll()` などに揃えたことで、このクラスに処理が集まっていた。

## 判断

- **`Services\Product` を、Entity の名前空間と同じ単位のサブ Service に分ける。** 出典: `416c5c3`。
  - `Services\Product\Variant`（`page` / `one` / `update`）
  - `Services\Product\Option`（`create` / `delete`）、`Services\Product\Option\Value`（`create` / `delete`）
  - `Services\Product\Pickup`（`create` / `update` / `delete`）
  - `Services\Product\Image`（`all` / `create` / `delete`）
  - `Services\Product\Group`（`one` / `all` / `create` / `update`）
  - `Services\Product\Category`（`all` / `create` / `update` / `createChild` / `updateChild`）
  - `Services\Product\Advertising`（`page`）、`Services\Product\Stock`（`page`）
- メソッド名は、ADR 0033 の Service の規則（クラス名にあたる対象を省き、`page` / `one` / `all` を使う）に
  従う。引数の名前・型・既定値・戻り値・挙動は、移す元のメソッドと同じにする。出典: `416c5c3`。
- ただし、カテゴリーの応答が期待した形でないときの `InvalidFieldException` のメッセージは、発生元のクラス名が
  `Services\Product` から `Services\Product\Category` に変わる。非推奨の `Services\Product` のメソッドを経由しても
  同じである。発生元は実際に処理するサブ Service の方が正確であり、0.24.0 の改名（ADR 0029）と同じく、例外
  メッセージのクラス名が変わることは受け入れて記録する。例外の型は変わらない。出典: `416c5c3`。
- 補助処理は、それを使うサブ Service に移す。出典: `416c5c3`。
- **サブ Service を公開の入口とする。** `Client` も内部でサブ Service を使う。`Client` の公開メソッドは
  変えない。出典: `416c5c3`。
- `Services\Product` には商品本体の `page` / `one` / `create` / `update` を残す。サブリソースの 24 メソッドは
  `@deprecated` を付けてサブ Service に委譲し、次のメジャーな変更で削除する。0.25.0 までの非推奨の旧名と
  `Services\Stock::page()` は、非推奨のメソッドを経由せず、サブ Service に直接委譲する。出典: `416c5c3`。

## 代替案と却下理由

- **サブ Service に分け、`Services\Product` を窓口として残す案**は、公開 API が変わらない。0.25.0 で付けた
  `variantPage()` などをすぐ非推奨にせずに済む。しかし `Services\Product` に委譲だけのメソッドが約 40 個
  残り、入口が `Services\Product` に集まったままになる。利用者の指定により、サブ Service を公開の入口とする。
- **補助処理だけを別のクラスに切り出す案**は、公開 API が変わらない。しかし減るのは一部で、メソッドの数と
  扱う対象の多さは変わらない。
- **トレイトで分割する案**は、ファイルを分けられる。しかしクラスとしての責務は分かれず、テストも
  `Services\Product` を通すことになる。

## 帰結

サブリソースごとの処理と補助処理が、Entity と同じ単位のクラスに分かれる。

次の点が変わる。

- `Services\Product` のサブリソースのメソッドは動作するが、非推奨になる。0.25.0 で付けた `variantPage()` /
  `groupAll()` / `stockPage()` なども含む。サブ Service に書き換える必要がある
- `Services\Product`（クラス）と `Services\Product\`（名前空間）、非推奨の `Services\Stock` と
  `Services\Product\Stock` が並ぶ。両方を使うファイルでは `use ... as` が必要になる
- 非推奨のメソッドを削除するまで、`Services\Product` は委譲のためのメソッドを抱える
- カテゴリーの応答が不正なときの例外メッセージに含まれるクラス名が `Services\Product\Category` になる。
  メッセージの文字列を照合しているコードは互換でない

## 関連

- [ADR 0000: アーキテクチャ上の意思決定を記録する](0000-record-architecture-decisions.md)
- [ADR 0030: Entity の名前空間を API の URL に沿わせる](0030-place-entities-by-api-path.md)
- [ADR 0032: 在庫の Service を Services\Product に統合する](0032-merge-stock-service-into-product-service.md)
- [ADR 0033: Client と Service のメソッド名を整理する](0033-unify-client-and-service-method-names.md)
- 実装とテストの出典コミット: `416c5c3`
