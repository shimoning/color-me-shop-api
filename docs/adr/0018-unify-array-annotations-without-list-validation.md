# ADR 0018: 配列フィールドのアノテーションを list に統一し、リスト形状は検証しない

- 状態: 採用
- 決定日: 2026-09-28

## 文脈

Entity の配列フィールドの PHPDoc アノテーションが整備されていなかった。コレクションを返す getter 27 件
の記法が 3 通りに割れ、要素型の誤りと注釈の欠落があった。出典: `073dfe4`。

| 記法 | 件数 |
| --- | ---: |
| `list<…>` | 15 |
| `array<…>` | 11 |
| `X[]` | 1 |

同じ API フィールドでも記法が異なった。`Product\Product::getUnavailablePaymentIds()` は `list<int>`、
`Delivery\Delivery::getUnavailablePaymentIds()` は `array<int>` である。`@var` 注釈のない配列
プロパティが 17 件あり、`Sales\SaleDelivery::getDetailIds()` は要素型を `array<string>` としていたが
公式 OpenAPI と実測はいずれも `int` だった。

発端は PR #76 の独立レビューで、`Delivery\DeliveryDateTimes::getPeriods()` が `list<string>` を宣言し
ながら `array_is_list()` 相当の検証をせず、`[2 => '午前中']` のような非 list 配列も受理する点を指摘
されたことである。当初は非 list を `InvalidFieldException` で拒否する方針で issue #77 を立てたが、
調査の結果この検証は採らないこととし、issue の対象をアノテーションの整備へ組み替えた。

一方で、応答側にはすでにリスト形状を拒否している箇所が 2 つあった。`Product\BigCategory` の
`children` と `Payment\Cod` の `fees` の外側で、いずれも非 list の配列を `InvalidFieldException` に
していた。出典: `db7f7ed`、`5edb544`。導入時の根拠は形状の固定であり、実測やバグに基づくものでは
なかった。

### 公式 OpenAPI の確認

2026-09-28 に取得した[公式 OpenAPI](https://api.shop-pro.jp/v1/spec/open_api.json)で、対象 25
フィールドがすべて `type: array` であることを確認した。`type: object` のマップは 1 つもない。
JSON 配列は `json_decode(..., true)` で必ず PHP の list になる。

### 実 API の観測

同日、13 エンドポイントの生のレスポンス文字列を走査し、配列を期待する位置の直後が `[` か `{` かを
数えた。受注は `make_date_min` に古い日付を指定して取得した。

| 結果 | 件数 |
| --- | --- |
| `[`（JSON 配列） | 22 キー・のべ 125 箇所 |
| `{`（JSON オブジェクト） | 0 |

観測したすべての位置で `array_is_list()` が真だった。未観測は 2 キーで、`siblings_sale_ids`（受注 4 件
とも `segment` が `null`）と `external_accounts`（顧客 9 件ともキー自体が欠損）である。後者は過去の
fixture に配列と `null` の記録がある。`Delivery\Weight::$areas` は API のキーではなく、
`charge_ranges_by_weight` の tuple からライブラリが組み立てる派生値である。

`children` は[カテゴリーの観測記録](../api-category-structure.md)で常に `array<object>`、`fees` は
[決済の観測記録](../api-payment-structure.md)で「2 整数のタプルの配列」であり、OpenAPI の内側の配列には
`minItems: 2` と `maxItems: 2` の指定がある。

### 非 list が生じる条件

PHP では、連番でない数値キーを持つ JSON オブジェクトだけが非 list になる。`{"0":"a","1":"b"}` は
連番のため list になり、`{}` と `[]` も list になる。つまり `{"2":"a"}` の形だけが該当する。

### 利用者から見た list と非 list の差

`foreach` と `count()` はどちらでも同じに動く。差が出るのは `json_encode()` で、非 list は `[1,2,3]`
ではなく `{"2":1}` になる。ただし `toArray()` をコンストラクタへ戻せる直列化形式として保証しない
方針を [ADR 0004](0004-entity-to-array-is-not-round-trippable.md) で定めているため、この用途は
公式には支えていない。

## 判断

- コレクションを返す getter と配列プロパティのアノテーションは `list<…>` に統一する。`array<…>` と
  `X[]`、`SaleDeliveryUpdateInput[]` の記法は使わない。公式 OpenAPI が対象フィールドをすべて
  `type: array` と宣言し、実測でも例外がないためである。出典: `c89ca68`。
- `array<…>` は PHPStan では `array<array-key, T>` を意味し、キーの連番性を保証しない。仕様が配列と
  定めている以上、実態より弱い宣言であるため採らない。出典: `c89ca68`。
- 連想配列やマップを返すものは `array<キー型, 値型>` のままとする。`toArray()`、
  `toArrayRecursive()`、`getRaw()`、コンストラクタの `$data` が該当する。これらは list ではない。
  出典: `c89ca68`。
- `@var` 注釈のない配列プロパティには注釈を付ける。要素型は対応する getter、`OBJECT_FIELDS`、
  コンストラクタの変換処理、公式 OpenAPI から判断する。`Delivery\Charge` の `charge_ranges_by_price`
  は境界金額と送料の 2 整数タプルのため `list<array{int, int}>`、`charge_ranges_by_weight` は
  `Weight` へ変換して保持するため `list<Weight>` とする。出典: `c89ca68`。
- `Sales\SaleDelivery::getDetailIds()` の要素型は `int` に修正する。公式 OpenAPI が
  `array` of `integer` とし、実測でも `int` だったためである。出典: `c89ca68`。
- **リスト形状の実行時検証は追加しない。** 非 list を `InvalidFieldException` で拒否しない。
  出典: `c89ca68`。
  - 守る対象の形状が発生しない。公式 OpenAPI が全件 `type: array` と宣言し、実測でもオブジェクト
    形状は 0 件である。
  - [ADR 0009](0009-opt-in-enum-fallback.md) と [ADR 0017](0017-opt-in-value-fallback.md) は、
    想定外の応答値で Entity 全体の構築が失敗するのを避けるためにフォールバックを導入した判断で
    ある。応答側に新しく厳格な例外を足すのはこの流れに反する。
  - 利用者への実益が小さい。`foreach` と `count()` に差はなく、差が出る `json_encode()` の用途は
    ADR 0004 で保証していない。
- **応答側にすでにあったリスト形状の外側ガード 2 箇所も同じ理由で撤去する。** `Product\BigCategory`
  の `children` と `Payment\Cod` の `fees` である。いずれも `[]` で追記して保持するため、入力のキーが
  どうであれ getter は list を返し、宣言は保たれる。配列でない値を拒否する `is_array` の検査は残す。
  出典: `e691aaf`。
- **撤去しない `array_is_list()` を明確にする。**
  - `Payment\Cod` の内側タプルの検査は、`[上限, 手数料]` という位置の意味を保証するものであり、
    リスト形状の検証ではない（[ADR 0011](0011-represent-cod-fees-with-entity.md)）。OpenAPI の
    `minItems: 2` / `maxItems: 2` にも対応する。
  - 要求側の `Product\ProductInput` と `Product\OptionCreateInput` の検査は残す。非 list の PHP 配列を
    `json_encode()` すると JSON オブジェクトになり、API が配列を期待する位置へ不正なボディを送る。
    送信前に止める実益があり、要求側を厳格に保つ [ADR 0013](0013-expand-opt-in-enum-fallback.md) と
    [ADR 0014](0014-model-product-write-api.md) の契約に沿う。
  - `Communicator\Errors` の `array_is_list()` は拒否ではなく、生 JSON を持たない応答で要素が
    エラー object かどうかを見分ける判別式であり、対象外である。
- **`array_values()` による正規化も行わない。** 宣言と実装を一致させる手段としては例外より穏当だが、
  どのフィールドを list として正規化するかを基底 `Entity` が知る必要があり、そのための宣言
  （`LIST_FIELDS` 相当）か PHPDoc の実行時解析を新たに導入することになる。発生しない形状のために
  機構を増やす判断はしない。
- この結果、**要素の型は検証するがキーの連番性は検証しない**という非対称が残る。`list<…>` の宣言は、
  公式 API 契約と実測に基づく応答の形を述べたものであり、利用者が手で組み立てた配列に対する保証では
  ない。この限界を ADR とリリースノートに記録する。
- 記法の逸脱を検出する横断テストを追加する。新しい配列フィールドを追加したときに、`list<…>` でない
  記法や `@var` の欠落を検出できるようにする。出典: `c89ca68`。

## 代替案と却下理由

- 非 list を `InvalidFieldException` で拒否する案は、宣言と実装を厳密に一致させられる。しかし発生
  しない形状を守ることになり、想定外の応答で Entity の構築を失敗させない ADR 0009 / 0017 の方向と
  逆行する。手で組み立てた配列や過去に `serialize()` されたデータを持つ利用者には破壊的変更にも
  なるため採用しない。
- hydration 時に `array_values()` で正規化する案は、例外を投げず BC リスクもなく、JSON 配列のキーは
  意味を持たないため情報も失わない。しかし list として扱うフィールドを基底 `Entity` が識別する機構が
  必要で、発生しない形状のために宣言または実行時解析を増やすことになるため今回は採用しない。将来
  実 API が非 list を返す事例を観測した場合は、この案を第一候補として再検討する。
- アノテーションを `array<…>` へ弱めて統一する案は、実装が検証していない現状に忠実で、コード変更も
  最小である。しかし公式 OpenAPI が配列と宣言している事実を型で表せず、利用者の静的解析に渡る情報が
  減る。記法を統一する機会としても後退であるため採用しない。
- 記法を統一せず要素型の誤りと注釈の欠落だけを直す案は、変更が最小である。しかし同じ API フィールド
  で記法が異なる状態が残り、新しいフィールドを追加するときにどちらへ倣うべきか判断できないため
  採用しない。
- 既存の外側ガード 2 箇所を残す案は、変更が少ない。しかし新規フィールドでは検証せず既存の 2 箇所
  だけ検証する不整合が残り、応答側の方針を 1 つの規則で説明できなくなるため採用しない。

## 帰結

配列フィールドのアノテーションが 1 つの規則で説明できるようになる。利用者の IDE 補完と静的解析には、
公式 API 契約に沿った要素型とキーの連番性が渡る。

一方で、キーの連番性は実行時に検証されない。応答が公式 API 契約に従う限り `list<…>` の宣言は真で
あるが、利用者が手で組み立てた配列や `unserialize()` した古いデータでは宣言と実態が食い違いうる。
実 API がオブジェクト形状を返す事例を観測した場合は、正規化案を含めて再検討する。

`BigCategory::children` と `Cod::fees` に非 list の配列を渡していた利用者は、従来 `InvalidFieldException`
だったものが list として保持されるようになる。緩和方向の挙動変更であり、リリースノートで告知する。

横断テストにより、新しい配列フィールドで記法が再び割れることは防げる。ただし要素型の正しさは
テストでは担保できず、公式 OpenAPI との突合が引き続き必要である。

## 関連

- [ADR 0000: アーキテクチャ上の意思決定を記録する](0000-record-architecture-decisions.md)
- [ADR 0004: Entity::toArray は round-trip を保証しない](0004-entity-to-array-is-not-round-trippable.md)
- [ADR 0009: 未知の enum 値を opt-in でフォールバックする](0009-opt-in-enum-fallback.md)
- [ADR 0011: 代引き手数料区分を Entity で表現する](0011-represent-cod-fees-with-entity.md)
- [ADR 0013: 応答に使う enum のフォールバック対象を拡張する](0013-expand-opt-in-enum-fallback.md)
- [ADR 0014: 商品書き込み API の入力と空レスポンスを表現する](0014-model-product-write-api.md)
- [ADR 0017: 応答に使う値オブジェクトへ opt-in のフォールバックを導入する](0017-opt-in-value-fallback.md)
- [カテゴリー API 応答構造の実測記録](../api-category-structure.md)
- [決済 API 応答構造の実測記録](../api-payment-structure.md)
- [公式 OpenAPI](https://api.shop-pro.jp/v1/spec/open_api.json)（2026-09-28 取得）
- アノテーション統一と横断テストの出典コミット: `c89ca68`
- 応答側の外側ガード撤去の出典コミット: `e691aaf`
- 既存ガードの導入コミット: `db7f7ed`（`BigCategory`）、`5edb544`（`Cod`）
