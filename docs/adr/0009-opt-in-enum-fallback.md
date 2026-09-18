# ADR 0009: 未知の enum 値を opt-in でフォールバックする

- 状態: 採用
- 決定日: 2026-09-16

## 文脈

`Entity::buildEnum()` は、API の値に対応する case がない場合に例外を投げ、フィールドの hydration
を失敗させている。この厳格な拒否は、誤った値を見逃さない既存 enum 共通の慣習である。一方、
`Customer.external_accounts[].provider` は API 側で新しい provider が追加され得るため、既知の
provider を利用していない顧客を含むレスポンスまで `Customer` 全体として構築できなくなる
前方互換性のリスクがある。判断時点の実装: `a2b5814`。

## 判断

- 未知値の代替 case を返す `FallbackEnum` インターフェースを導入し、実装した enum だけが
  `Entity::buildEnum()` のフォールバックへ opt-in する。インターフェースを実装しない既存 enum
  は、従来どおり未知値を厳格に拒否する。既存の検証慣習を既定動作として維持しながら、将来の
  値追加を許容する必要があるフィールドだけを明示できるためである。
  出典: `df86e33ad76cc4c937e9bddd16be0314973e9147`。
- 現時点では `ExternalAccountProvider` だけが `FallbackEnum` を実装する。他の enum へ一律に
  展開すると、本来検出すべき不正値まで隠す可能性があるため、必要性と意味を個別に評価する。
  他の enum への展開は別 Issue で検討する。
  出典: `df86e33ad76cc4c937e9bddd16be0314973e9147`。
- `ExternalAccountProvider::UNKNOWN` の backing value は `-1` とする。API の provider 値は
  `0` 以上であり、API が返す実値と衝突しない番兵として区別できるためである。`UNKNOWN` は
  API 仕様上の provider ではなく、未知の API 値を型付きプロパティで表現するためだけに使う。
  出典: `df86e33ad76cc4c937e9bddd16be0314973e9147`。
- `-1` の選択は、現時点の API の provider 値が `0` 以上であり衝突しないという
  前提に基づく暫定的な判断である。将来 API が `-1` を正式な provider 値として採用した
  場合は `UNKNOWN` の番兵と衝突し、正規の値と未知値を区別できなくなる。その場合を
  再検討の条件とし、番兵値を別の値へ移すか、専用の値オブジェクトやラッパーを用いるなど
  フォールバックの表現方法自体を見直す。
  出典: `df86e33ad76cc4c937e9bddd16be0314973e9147`。
- 未知の生値は型付きプロパティでは `UNKNOWN` に集約する。これにより元の値そのものは型付き
  プロパティから失われ、`toArrayRecursive()` では番兵値 `-1` になる。一方、受信した元の生値は
  `Entity::getRaw()` に残るため、調査や将来の再解釈に利用できる。この非対称性を受け入れる。
  出典: `df86e33ad76cc4c937e9bddd16be0314973e9147`。

## 代替案と却下理由

- 全 enum の未知値を `null` または共通の値へ変換する案は、既存の厳格な入力検証を弱め、不正値と
  将来追加された値を区別できないため却下する。
  出典: `df86e33ad76cc4c937e9bddd16be0314973e9147`。
- `Entity::buildEnum()` に `ExternalAccountProvider` のクラス名を直接記述する案は、基底 Entity が
  個別ドメインの enum に依存し、将来の適用判断も条件分岐の追加になるため却下する。
  出典: `df86e33ad76cc4c937e9bddd16be0314973e9147`。
- 未知値を例外のまま扱う案は、API に provider が追加されただけで `Customer` 全体の hydration が
  失敗する前方互換性リスクを解消できないため却下する。
  出典: `df86e33ad76cc4c937e9bddd16be0314973e9147`。
- `UNKNOWN` に `0` 以上の値を割り当てる案は、現在または将来の API provider 値と衝突し得るため
  却下する。
  出典: `df86e33ad76cc4c937e9bddd16be0314973e9147`。

## 帰結

未知の external account provider を含む顧客レスポンスも構築でき、利用者は型付きプロパティで
`UNKNOWN` として扱える。既知値 `0` は引き続き `LINE` になる。その他の enum はインターフェースを
実装しない限り挙動が変わらず、未知値に対して例外を投げる。

フォールバック後の直列化値は元の API 値ではなく `-1` である。元の値が必要な処理は `getRaw()` を
参照する必要がある。また、別の enum に同じ仕組みを適用する際は、自動展開せず別 Issue で妥当性と
番兵値を判断する。

## 関連

- [ADR 0008: 構造化されたレスポンスフィールドを Entity として表現する](0008-model-structured-response-fields-as-entities.md)
- [ADR 0003: 暗黙変換より意味上正しい型を優先する](0003-prefer-semantic-types-over-legacy-coercion.md)
- 判断時点の実装コミット: `a2b5814`
- opt-in 対象の追加判断は [ADR 0013](0013-expand-opt-in-enum-fallback.md) により更新された。
