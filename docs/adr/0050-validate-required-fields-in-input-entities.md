# ADR 0050: 入力 Entity の必須フィールドを生成時に検証する

- 状態: 採用
- 決定日: 2026-10-16

## 文脈

入力 Entity の必須フィールドは、各入力 Entity が `REQUIRED_FIELDS` として公開し、検証は Service の private メソッドが
送信前に行っていた。欠けていれば `ParameterException` を投げていた。対象は、顧客の作成・更新・ポイント増減、受注の作成
（明細とお届け先を含む）、ピックアップの作成・更新である（[ADR 0014](0014-model-product-write-api.md)、
[ADR 0015](0015-model-customer-write-api.md)、[ADR 0024](0024-model-sale-create-api.md)、
[ADR 0041](0041-require-address2-in-customer-update.md)）。ピックアップの必須フィールドは `REQUIRED_FIELDS` を持たず、
Service に直接書かれていた。

検証を Service に置いたのは、全 Entity を空の配列で作れることを前提とする `EntityContractTest` の契約を保つためだった
（ADR 0015）。一方で、`StocksIncrementInput` の `increment` や `OptionCreateInput` の `values[]` の `name` は、入力 Entity の
コンストラクタで検証し、`InvalidFieldException` を投げていた。入力の不備が分かる場所と例外の型が、入力 Entity ごとに
分かれていた。

## 判断

- **必須フィールドは、入力 Entity のコンストラクタで検証する。** 欠けていれば `InvalidFieldException` を投げる。Service は
  検証せず、入力 Entity を直列化して送る。入力の不備は、入力を作った時点で、ほかのフィールドの不備と同じ型で分かる。
  利用者の指定による。出典: `d70997b`。
- 必須を満たすかは、キーがあるかだけで決める。明示した `null` を受け付けるかは、これまでどおり各フィールドの型で決まる。
  コンストラクタが受け付ける API の名前とプロパティの名前の、どちらのキーでも満たしたものとして扱う。出典: `d70997b`。
- `REQUIRED_FIELDS` は public な定数のまま残し、`FIELD_TYPES` には統合しない。`FIELD_TYPES` は値の変換の宣言で、必須の
  フィールドの多くは変換を持たない。利用者の指定による。ピックアップの入力にも `REQUIRED_FIELDS` を足す。出典: `d70997b`。
- 受注の作成で明細やお届け先の必須フィールドが欠けていれば、`details[1]` のようにどの要素かと、欠けたフィールドを
  例外のメッセージに残す。`details` が空の配列なら、これまでどおり拒否する。出典: `d70997b`。
- `EntityContractTest` は、必須フィールドを持つ入力 Entity を、必須フィールドだけの最小の値で作るように変える。出典:
  `d70997b`。

## 代替案と却下理由

- **Service で検証したまま、例外の型だけを `InvalidFieldException` に揃える案**は、生成時の契約テストを変えずに済む。
  しかし不備が分かるのは送信時のままで、必須ではないフィールドの不備と分かる時点が揃わない。
- **例外を `ParameterException` のままにする案**は、捕捉していた利用者のコードを変えずに済む。しかし入力 Entity の生成時の
  不備のうち、必須フィールドの欠落だけが別の型になる。
- **`REQUIRED_FIELDS` を `FIELD_TYPES` に統合する案**は、フィールドの宣言が 1 か所にまとまる。しかし `FIELD_TYPES` の意味が
  変換の宣言から広がり、変換を持たないフィールドも書くことになる。公開している `REQUIRED_FIELDS` の非推奨化も要り、
  この変更の範囲を超える。

## 帰結

必須フィールドが欠けた入力は、送信しない場合も作れない。必須フィールドを満たした入力 Entity は、送信の前に必須の
不備を持たない。

次の点が変わる。

- 必須フィールドの欠落は、送信時の `ParameterException` ではなく、生成時の `InvalidFieldException` になる。
  `ParameterException` で捕捉していたコードは、生成を囲んで `InvalidFieldException`（または親の `ColorMeApiException`）を
  捕捉する必要がある
- `unserialize()` ではコンストラクタが動かないため、必須フィールドを検証しない

## 関連

- [ADR 0000: アーキテクチャ上の意思決定を記録する](0000-record-architecture-decisions.md)
- [ADR 0014: 商品書き込み API の入力と空レスポンスを表現する](0014-model-product-write-api.md)
- [ADR 0015: 顧客書き込み API の入力を作成と更新で分ける](0015-model-customer-write-api.md)
- [ADR 0024: 受注作成 API の入力を表現する](0024-model-sale-create-api.md)
- [ADR 0041: 顧客データの更新で address2 を必須にする](0041-require-address2-in-customer-update.md)
- 実装とテストの出典コミット: `d70997b`
