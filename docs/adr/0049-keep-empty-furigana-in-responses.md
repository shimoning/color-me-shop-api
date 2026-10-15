# ADR 0049: 応答の furigana の空文字を null にまとめない

- 状態: 採用
- 決定日: 2026-10-15

## 文脈

顧客（`Customer\Customer`）とお届け先（`Sale\Delivery`）の応答の `furigana` は、値オブジェクト `Furigana` に変換している。
null を許す指定には、falsy な値をすべて `null` にする `nullable` を使っていたため、応答の `""` も `null` になっていた。
[ADR 0012](0012-allow-nullability-from-api-observations.md) は、子 Entity については `null` だけを許す `allowNull` を使うと
決めているが、値オブジェクトについては決めていなかった。

2026-10-15 にテスト用ショップで確かめると、顧客とお届け先のどちらも、`furigana` に送った `""` と `null` をそれぞれそのまま
保存し、応答でも別の値として返した。既存のデータにも、`""` の顧客とお届け先、`null` の顧客があった。実測の出典:
[顧客とお届け先の `furigana` の実測記録](../api-furigana-observation.md)（`f9b81fe`）。

入力側では、お届け先の更新の `Sale\DeliveryUpdateInput` の `furigana` を、`''` は `''` のまま、`null` は `null` として送るように
変えていた（`037a5e1`）。顧客の入力も同じ扱いだった。

## 判断

- **顧客とお届け先の応答の `furigana` は、`""` を `null` にまとめず、`Furigana('')` として返す。** `null` とキーの欠損は
  `null` を返す。実 API が `""` と `null` を区別して保存して返すため、ライブラリで区別を失わない。利用者の指定による。
  出典: `f9b81fe`、`def0464`。
- `null` 以外の falsy な値のうち、文字列でない値（`0`、`false`、`[]` など）は、ADR 0012 と同じく構築時に
  `InvalidFieldException` にする。`'0'` のような文字列は、`'abc'` などほかの形式の不正な文字列と同じく、`isValid()` が
  `false` の `Furigana` として返す。出典: `def0464`。

## 代替案と却下理由

- **`nullable` のまま、`""` を `null` にまとめる案**は、利用者が `null` かどうかだけで空を判定できる。しかし実 API が保存して
  いる `""` と `null` の区別を失い、入力側で区別して送る扱いとも揃わない。

## 帰結

応答の `furigana` が `""` の顧客とお届け先では、`getFurigana()` が `null` ではなく `Furigana('')` を返す。空を判定する利用者の
コードは、`null` と `get() === ''` の両方を扱う必要がある。

## 関連

- [ADR 0000: アーキテクチャ上の意思決定を記録する](0000-record-architecture-decisions.md)
- [ADR 0012: 実 API の観測に基づき Entity の null 許容を判断する](0012-allow-nullability-from-api-observations.md)
- [顧客とお届け先の `furigana` の実測記録](../api-furigana-observation.md)（出典コミット: `f9b81fe`）
- 実装とテストの出典コミット: `def0464`
