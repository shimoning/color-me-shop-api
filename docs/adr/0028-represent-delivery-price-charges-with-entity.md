# ADR 0028: 配送料の金額別区分を専用 Entity で表現する

- 状態: 採用
- 決定日: 2026-10-05

## 文脈

`Entities\Delivery\Charge::$chargeRangesByPrice` は、公式 OpenAPI の `charge.charge_ranges_by_price` を
`list<array{int, int}>` のまま保持し、`getChargeRangesByPrice()` もそのまま返していた。各組の第 1 要素は
区分の上限金額、第 2 要素はその区分の配送料だが、利用者は `$ranges[0][0]` と `$ranges[0][1]` の意味を
公式仕様から自分で読み取る必要があった。

同じ形の組は、ライブラリの中ですでに 2 か所で Entity にしている。

- 代引き手数料の区分（`payment.cod.fees`）は `Payment\CodFee`（`upperLimit` / `fee`）で表す
  （[ADR 0011](0011-represent-cod-fees-with-entity.md)）
- 同じ `Charge` の重量別の配送料（`charge_ranges_by_weight`）は `Delivery\Weight` で表す

`getChargeRangesByPrice()` の PHPDoc は、公式 OpenAPI の説明をそのまま写し、「`[3000, 100]` であれば
3000円以下の場合」としていた。しかし代引き手数料では、この「以下」が誤りで、上限はその区分に含まれない
（未満）ことが実測で分かっていた。配送料の境界は確かめられていなかった。

2026-10-05 に、ショップのオーナーが管理画面で配送方法を注文金額別に設定し、画面の表記と API の応答を
突き合わせた。詳細は[配送料の金額別区分の実測記録](../api-delivery-charge-observation.md)にある。
出典: `cec94c3`。

- 管理画面の「〜500円未満：1200円」は `[500, 1200]` として返った。**組の第 1 要素は、その区分に含まれない
  上限である。** 公式 OpenAPI の「以下」は、設定上の境界の説明として誤っている
- 管理画面の「上記金額以上：10円」は `charge_max_price` = `10` として返った

## 判断

- **`charge_ranges_by_price` の各区分を `Delivery\Price` で表す。** 上限金額を `getUpperLimit()`、
  その区分の配送料を `getCharge()` で返す。クラス名は、同じ `Delivery` 名前空間の `Area`（地域ごと）・
  `Weight`（重量ごと）に揃え、何による配送料の区分かを表す。項目名は代引き手数料の `CodFee` の
  `upperLimit` に揃える。出典: `81d708e`、`cc07151`。
- `Charge::getChargeRangesByPrice()` は `list<Price>` を返す。元の組は `getRaw()` から取得できる。
  出典: `81d708e`。
- 各組は、連番のキーを持つちょうど 2 つの整数の配列でなければならず、そうでなければ
  `InvalidFieldException` にする。形が同じ代引き手数料の `CodFee` と同じ厳しさにする。
  出典: `81d708e`、`c1a5e51`。
- **上限はその区分に含まれない（未満）ことを、「公式 OpenAPI との差分:」として PHPDoc に書く。**
  `charge_max_price` は最後の区分の上限以上の配送料であることも書く。実測で確かめた。
  出典: `cec94c3`、`9cb4664`。

## 代替案と却下理由

- **組を `list<array{int, int}>` のまま返し、PHPDoc で各要素の意味を説明する案**は、互換性を保てる。しかし
  ADR 0011 と同じく、値の意味を公開型から伝えるという本ライブラリの方針から外れ、同じ形の代引き手数料や
  重量別の配送料とも揃わない。
- **基底 `Entity` に「組の i 番目をこの項目に割り当てる」宣言を加え、`Weight` と `CodFee` の組み立ても
  そこへ移す案**は、今後の組にも宣言だけで対応できる。しかし変更範囲が広く、既存の動いている組み立ての
  リファクタリングを含む。利用者と協議のうえ、既存の 2 つと同じく個別に組み立てる。
- **クラス名を `PriceCharge` にする案**は、配送料の区分であることが名前から分かる。しかし同じ名前空間の
  `Area` と `Weight` は区分の軸だけを名前にしており、配送料であることは名前空間と親の `Charge` から
  分かる。利用者の指定により `Price` とする。
- **項目名を `getPrice()` / `getCharge()` にする案**は、`Weight::getWeight()` と揃う。しかし `price` だけでは
  区分の上限であることが伝わりにくい。利用者と協議のうえ、`CodFee` と同じ `upperLimit` を採る。

## 帰結

配送料の金額別区分の各要素の意味が、型と名前から読み取れるようになる。代引き手数料、重量別の配送料と
同じ形になる。

次の点が変わる。破壊的変更のため、次のマイナーバージョンで行う。

- `Charge::getChargeRangesByPrice()` の戻り値が `list<array{int, int}>` から `list<Price>` に変わる。
  `[0]` / `[1]` で読んでいたコードは `getUpperLimit()` / `getCharge()` に書き換える必要がある
- `Charge` の配列化の出力も変わる。`toArray()` の `charge_ranges_by_price` は `Price` のリストになり、
  `toArrayRecursive()` では `[['upper_limit' => 500, 'charge' => 1200], ...]` の形になる。API の元の組の
  形は `getRaw()` に残る
- 要素が 2 つでない組や整数でない要素を含む応答は、`Charge` の構築時に `InvalidFieldException` になる。
  これまでは組の中身を検証していなかった。実 API の応答では、そうした組は観測していない

境界は管理画面の設定上のもので、実際の注文で配送料がどの区分で計算されるかは観測していない。代引き
手数料の ADR 0011 と同じ限界である。

## 関連

- [ADR 0000: アーキテクチャ上の意思決定を記録する](0000-record-architecture-decisions.md)
- [ADR 0011: 代引き手数料区分を専用 Entity で表現する](0011-represent-cod-fees-with-entity.md)
- [配送料の金額別区分の実測記録](../api-delivery-charge-observation.md)（出典コミット: `cec94c3`）
- 実装とテストの出典コミット: `81d708e`
- クラス名の変更の出典コミット: `cc07151`
- PHPDoc への境界の記載の出典コミット: `9cb4664`
- 組の検証の厳しさの出典コミット: `c1a5e51`
