# ADR 0011: 代引き手数料区分を専用 Entity で表現する

- 状態: 採用
- 決定日: 2026-09-16

## 文脈

公式 OpenAPI の `components.schemas.payment.properties.cod` では、代引き決済の設定を次のように
定義している。

- `fees` は「手数料が変わる決済金額の区分」である。外側は `type: array`、各要素も
  `type: array` であり、その要素は `type: integer`、`minItems: 2`、`maxItems: 2` である。
  したがって各区分は整数2要素固定のタプルである。説明には「`[3000, 100]` であれば、
  3000円以下の場合、手数料は100円であることを表す」とある。管理画面の設定表記と GET 応答の
  対応から見ると、第1要素は設定上の排他的上限であり、この公式説明は設定上の境界の説明として
  誤っている。実決済時に手数料がどの区分で計算されるかは未観測であり、本記録では確定しない。
  例は `[[3000, 100], [5000, 200]]` である。
- `fee_max` は「`fees` に設定されている区分以上の金額の場合の手数料」と説明されているが、
  「区分以上」だけでは最後の閾値以上という設定上の境界を特定できない。
- `changeable` は「手数料が決済金額によって変わるか否か」を表す。
- `changeable_by_total` は手数料計算に用いる金額を表し、`true` なら決済総額、`false` なら
  商品合計額を用いる。

2026-09-16 にテスト用ショップの実 API と管理画面を突き合わせた。管理画面で「0円〜300円未満」
を100円、「上記金額〜500円未満」を70円、「上記金額以上」を10円とした設定は、API では
`fees=[[300, 100], [500, 70]]`、`fee_max=10` として返った。したがって、タプルの第1要素は
設定上、その区分に含まれない排他的上限であり、数値の `fee_max` は設定上、最後の閾値以上、
すなわちこの例では500円以上に対応する手数料として返る。公式 OpenAPI の「3000円以下」は
設定上の境界の説明として誤っている。実決済時に手数料がどの区分で計算されるかは未観測であり、
本記録では確定しない。「区分以上」は境界の説明として不正確である。また、上限手数料が未設定の
応答では `fee_max` キーが存在し、値は `null` だった。固定手数料の応答では同キー自体が欠損していた。
管理画面では `changeable=false` で一律手数料、`changeable=true` で区分手数料が選択される。
区分手数料を選択した応答にも `Payment.fee` の一律手数料の入力値が残り、区分設定は `cod.fees`
に返る。これらの値が実決済時にどう計算されるかは未観測である。
実測の出典: [決済設定の実測記録](../api-payment-structure.md)。

現行の `Entities\Payment\Cod::$fees` は `array` であり、タプルの第1要素が上限金額、第2要素が
手数料であることを型から読み取れない。`Cod::getFees()` の PHPDoc も `array<int>` であり、実際の
二次元配列と一致しない。Issue #28 の nullable 対応で `array<int>|null` になっても不一致は残ると
レビューで指摘されており、nullable 化後にも残る既存の不整合である。
コードの出典: `4b85ca5`、`e5d2654`。実データの出典: [決済設定の実測記録](../api-payment-structure.md)。

利用者は `$fees[0][0]` と `$fees[0][1]` の意味を公式仕様から自分で解釈しなければならない。
これは API レスポンスを型付き Entity として扱い、値の意味を公開型から伝える本ライブラリの
価値から外れている。

## 判断

公式 OpenAPI の定義は 2026-09-16 時点のものを参照した。実 API と管理画面の観測結果は
[決済設定の実測記録](../api-payment-structure.md)を更新したコミット
`10a42835f188bfe04fb816822583cdffeea66a41` を出典とする。

- `fees` の各区分は `Shimoning\ColorMeShopApi\Entities\Payment\CodFee` で表す。代引き設定を
  表す `Cod` と同じ名前空間とし、汎用的すぎる `Fee` ではなく決済種別を含む名前にすることで、
  他の手数料との混同を避ける。
  出典: `4b85ca5`、`e5d2654`。
- `CodFee` は `int $upperLimit` と `int $fee` を持ち、`getUpperLimit()` と `getFee()` で取得する。
  `upperLimit` はタプルの第1要素に対応する設定上の排他的上限であり、その金額を区分に含めない。
  `fee` は第2要素に対応し、その `upperLimit` 未満の区分に設定された手数料を表す。公式 OpenAPI の
  「3000円以下」は設定上の境界の説明として誤っており、設定上は「3000円未満」として扱う。
  実決済時に手数料がどの区分で計算されるかは未観測であり、本判断では確定しない。
  コードの出典: `e5d2654`。境界の出典:
  [決済設定の実測記録](../api-payment-structure.md)。
- `Cod::getFees()` は `CodFee` の配列を返す。固定手数料の実 API 応答では `fees` の欠損が
  観測されているため、`Cod::$fees` と `getFees()` の nullable は維持し、公開契約は
  `list<CodFee>|null` とする。フィールドが欠損した場合は `null`、存在する場合は Entity の
  リストとして扱う。実データの出典: [決済設定の実測記録](../api-payment-structure.md)。
- `fees=[]` は未観測である。応答に空配列が存在する場合は欠損を表す `null` と区別して空のリストと
  して扱うが、その場合の `fee_max` の設定上の対応範囲は定めない。未観測の組み合わせから手数料の計算規則を
  推測しない。観測範囲の出典: [決済設定の実測記録](../api-payment-structure.md)。
- `Cod` 側で `fees` の数値キーのタプルを `CodFee` の意味付きフィールドへ正規化し、各区分の
  0 と 1 の連続した数値キーからなる2整数の区分かを個別に検証し、不正な区分の位置を識別可能にする。
  `OBJECT_FIELDS` だけでは数値キーを意味付きフィールドへ対応付けられない。
  `Delivery\Charge` の重量別配送料と同じく、
  親にあたる `Cod` で変換する。出典: `8a6560f`、`b89f3ce`、
  [決済設定の実測記録](../api-payment-structure.md)。
- `feeMax` は各 `CodFee::$upperLimit` で表す区分に収まらない金額に設定された手数料であり、
  `CodFee` の要素ではない。`getFees()` と `getFeeMax()` の公開契約では、各 `upperLimit` は
  排他的上限であり、設定上、最大の `upperLimit` 以上の金額には値があれば `feeMax` が対応する。
  例えば `fees=[[300, 100], [500, 70]]`、`fee_max=10` では、設定上、300円は第2区分、
  500円は `feeMax` の区分に入る。実決済時の計算結果は未観測である。
  `Cod::$feeMax` と `getFeeMax()` は nullable とし、`feeMax=null` は明示的な `fee_max: null` と
  キー欠損を getter 上では区別しない。前者は上限手数料の未設定、後者は固定手数料の応答で観測
  されている。元のキーの有無は `getRaw()` で確認でき、`null` を別の手数料で補完しない。
  コードの出典: `e5d2654`。境界と `null` の出典:
  [決済設定の実測記録](../api-payment-structure.md)。

## 代替案と却下理由

- PHPDoc だけを `array<array{int, int}>|null` または `list<array{int, int}>|null` に直す案は、
  実装コストが最も小さく要素数も静的解析へ伝えられる。しかし、添字 0 と 1 の意味は型に
  現れず、利用者による解釈と説明文への依存が残るため採用しない。
- 各区分を `['threshold' => 3000, 'fee' => 100]` のような連想配列へ変換する案は、添字より
  読みやすい。しかし型付きオブジェクトではなく、キー名の typo を実行前に検出できず、
  getter による契約も提供できないため採用しない。
- `Cod` に区分番号を受け取る `getFeeUpperLimit(int $index)` や `getFee(int $index)` を追加し、
  タプルを残す案は、個別取得の添字依存を別の API へ移すだけである。区分の反復時には依然として
  生のタプルを扱い、範囲外の区分番号という新しい失敗条件も増えるため採用しない。
- `Values\CodFee` のような値オブジェクトにする案は、2整数の組を不変値として表せる。しかし既存の
  `Values` は単一値の検証と取得を主目的とし、`fees` は API レスポンス内の構造である。既存 Entity の
  型検証、`InvalidFieldException`、`toArray()`、`toArrayRecursive()`、`getRaw()` の契約へ揃えるため、
  専用 Entity を採用する。

## 帰結

利用者は `getUpperLimit()` と `getFee()` により、各値の意味を型と名前から判断できる。一方、
`Cod::getFees()` の各要素が配列から `CodFee` に変わるため、本判断は破壊的変更である。主に次の
コードが壊れる。

- `$fees[0][0]`、`$fees[0][1]`、`[$upperLimit, $fee] = $fees[0]` のような添字アクセスと
  配列の分割代入。
- `array_map(fn (array $row) => $row[1], $fees)` のように、各区分を `array` と型宣言した
  コールバックや、`array_column()` など二次元配列を前提とした加工。
- `array<array{int, int}>` や `array` の要素を要求する関数、DTO、プロパティへ各区分を渡すコード。
  外側は配列のままだが、要素は `CodFee` になるためである。
- `json_encode($cod->getFees())` や `json_encode($cod->toArray())` がタプルの JSON 配列を返すことに
  依存するコード。変換後は配列内に Entity が入り、従来と同じ出力形状を保証しない。

`Cod::toArray()` は浅い変換なので、`fees` には `CodFee` オブジェクトの配列が入る。
`Cod::toArrayRecursive()` は各 Entity を再帰変換するため、各区分は
`['upper_limit' => 3000, 'fee' => 100]` のような意味付きの連想配列になり、元のタプル形状には
戻らない。これは [ADR 0004](0004-entity-to-array-is-not-round-trippable.md) の契約に従う。
一方、`Cod::getRaw()` は受け取った API レスポンスを保持する既存契約を変えず、`fees` の元の
タプル配列または欠損状態をそのまま返す。

Issue #28 の nullable 化も、本変更と同様に `getFees()` の公開契約を変える。

`CodFee` は OpenAPI 上の独立した object schema ではなく、名前のないタプルへライブラリが意味を
与える Entity である。そのため、`upper_limit` と `fee` は公式 API のフィールド名ではなく、
`CodFee` が公開する意味付きの名前である。

## 関連

- [Issue #28: `Cod` の nullable 対応](https://github.com/shimoning/color-me-shop-api/issues/28)
- [決済設定の実測記録](../api-payment-structure.md)（出典コミット: `10a42835f188bfe04fb816822583cdffeea66a41`）。
- [ADR 0000 の実 API 観測結果に関する出典ルール](0000-record-architecture-decisions.md)
- [公式 OpenAPI の `payment.cod`](https://api.shop-pro.jp/v1/spec/open_api.json)
- [ADR 0002: Entity の null 許容性を OpenAPI に合わせる](0002-entity-nullability-from-openapi.md)
- [ADR 0003: 暗黙変換より意味上正しい型を優先する](0003-prefer-semantic-types-over-legacy-coercion.md)
  - 本判断は、生のタプルとの互換性より、上限金額と手数料という意味を型で表すことを優先する。
    破壊的変更となる呼び出し方と移行先を明示して採用する点でも ADR 0003 と整合する。
- [ADR 0004: Entity の `toArray()` は往復可能な直列化ではない](0004-entity-to-array-is-not-round-trippable.md)
  - `toArray()` と `toArrayRecursive()` の契約の出典コミット: `7a9e566`。
- 出典コミット: `4b85ca5`、`e5d2654`、`f3f5845`、`3fe06e5`、`b00077f`、`a0506c1`、`7a9e566`。
