# ADR 0010: 代引き手数料区分を専用 Entity で表現する

- 状態: 採用
- 決定日: 2026-09-16

## 文脈

公式 OpenAPI の `components.schemas.payment.properties.cod` では、代引き決済の設定を次のように
定義している。

- `fees` は「手数料が変わる決済金額の区分」である。外側は `type: array`、各要素も
  `type: array` であり、その要素は `type: integer`、`minItems: 2`、`maxItems: 2` である。
  したがって各区分は整数2要素固定のタプルである。説明には「`[3000, 100]` であれば、
  3000円以下の場合、手数料は100円であることを表す」とあり、例は
  `[[3000, 100], [5000, 200]]` である。
- `fee_max` は「`fees` に設定されている区分以上の金額の場合の手数料」である。
- `changeable` は「手数料が決済金額によって変わるか否か」を表す。
- `changeable_by_total` は手数料計算に用いる金額を表し、`true` なら決済総額、`false` なら
  商品合計額を用いる。

現行の `Entities\Payment\Cod::$fees` は `array` であり、タプルの第1要素が上限金額、第2要素が
手数料であることを型から読み取れない。Issue #28 の nullable 対応で用いる `Cod.php:33` の
PHPDoc `array<int>|null` も実際の二次元配列と一致しないことがレビューで指摘されており、
nullable 化後にも残る既存の不整合である。

利用者は `$fees[0][0]` と `$fees[0][1]` の意味を公式仕様から自分で解釈しなければならない。
これは API レスポンスを型付き Entity として扱い、値の意味を公開型から伝える本ライブラリの
価値から外れている。

## 判断

- `fees` の各区分を表す `Shimoning\ColorMeShopApi\Entities\Payment\CodFee` を
  `src/Entities/Payment/CodFee.php` に追加する。代引き設定を表す `Cod` と同じ名前空間へ置き、
  汎用的すぎる `Fee` ではなく決済種別を含む名前にすることで、他の手数料との混同を避ける。
- `CodFee` は `int $upperLimit` と `int $fee` を持ち、`getUpperLimit()` と `getFee()` で取得する。
  `upperLimit` はタプルの第1要素に対応し、公式の「3000円以下」という説明から、その金額を
  含む上限であることを表す。`fee` は第2要素に対応し、その区分で適用する手数料を表す。
- `Cod::getFees()` は `CodFee` の配列を返す。Issue #28 で、実 API における `fees` の欠損が
  確認されているため、`Cod::$fees` と `getFees()` の nullable は維持し、公開契約は
  `list<CodFee>|null` とする。フィールドが欠損した場合は `null`、存在する場合は Entity の
  リストとして扱う。
- OpenAPI の `minItems: 2` と `maxItems: 2` を入力境界で検証する。各区分は添字 0 と 1 を持つ
  連続した2要素の配列でなければならず、両要素とも整数でなければならない。違反時は
  `fees[n]` を識別できる `InvalidFieldException` とし、生の `TypeError`、警告、添字未定義を
  利用者へ漏らさない。Entity への具体的な変換経路やヘルパーの配置は実装時に決める。
- `feeMax` は各 `CodFee::$upperLimit` で表す区分に収まらない金額へ適用する手数料であり、
  `CodFee` の要素ではない。`getFees()` と `getFeeMax()` の PHPDoc では、`upperLimit` が境界を
  含むことと、最大の `upperLimit` を超えた場合に `feeMax` を用いる関係を相互に説明する。

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

Issue #28 の nullable 化も `getFees()` の公開契約を変えるため、本変更と同じ破壊的変更のリリースに
まとめ、移行案内を一度に行うのが妥当である。

`ApiFieldNameTest` の fixture は OpenAPI のオブジェクトプロパティ名を検証する。`CodFee` は OpenAPI 上の
独立した object schema ではなく、名前のないタプルへライブラリが意味を与える Entity なので、
`upper_limit` と `fee` を公式 API フィールドとして fixture へ登録しない。Issue #33 のような登録漏れと
区別できるよう、この除外を実装時の専用テストで明示し、`CodFee` の変換と配列化はそこで検証する。
既存の `Payment\Cod` は引き続き `ApiFieldNameTest` の対象とする。

## 関連

- [Issue #28: `Cod` の nullable 対応](https://github.com/shimoning/color-me-shop-api/issues/28)
- [Issue #33: `ApiFieldNameTest` fixture の登録漏れ](https://github.com/shimoning/color-me-shop-api/issues/33)
- [公式 OpenAPI の `payment.cod`](https://api.shop-pro.jp/v1/spec/open_api.json)
- [ADR 0002: Entity の null 許容性を OpenAPI に合わせる](0002-entity-nullability-from-openapi.md)
- [ADR 0003: 暗黙変換より意味上正しい型を優先する](0003-prefer-semantic-types-over-legacy-coercion.md)
  - 本判断は、生のタプルとの互換性より、上限金額と手数料という意味を型で表すことを優先する。
    破壊的変更となる呼び出し方と移行先を明示して採用する点でも ADR 0003 と整合する。
- [ADR 0004: Entity の `toArray()` は往復可能な直列化ではない](0004-entity-to-array-is-not-round-trippable.md)
