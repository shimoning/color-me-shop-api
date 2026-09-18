# ADR 0013: 応答に使う enum のフォールバック対象を拡張する

- 状態: 採用
- 決定日: 2026-09-18

## 文脈

[ADR 0009](0009-opt-in-enum-fallback.md) は、未知の API 値で Entity 全体の構築が止まる問題に対して
`FallbackEnum` による個別 opt-in を選び、最初の対象を `ExternalAccountProvider` に限った。
その判断の出典: `df86e33ad76cc4c937e9bddd16be0314973e9147`。

[enum と公式 OpenAPI の突合記録](../enum-openapi-audit.md) では、現行 23 enum の用途、
API の宣言値、値の性質を照合した。`PaymentType`、`DeliveryMethodType`、`ContractPlan`、
`ErrorCode` は外部要因による値追加があり得る。`Sex` と `KouzaType` は現行値だけなら
閉集合寄りだが、顧客属性の仕様拡張や応答側の値追加で構築が失敗し得る。
将来の追加頻度と実 API の未知値の出現は未検証である。出典:
`8ea49f5fc5b1616e4a31249e12e291fcf07c6ee6`。

既知の仕様値不足は未知値とは別に扱う。`Sex::NOT_APPLICABLE` と OAuth scope の不足 4 case は
既に追加され、前者は Issue #54、後者は Issue #55 に対応した。出典:
`2197642f413beb57db411090de6851b6193b8fab`、
`7371c6101c91dfce848dc83091eb3970b648fef8`。

## 判断

- `FallbackEnum` の opt-in 対象に `PaymentType`、`Sex`、`KouzaType`、`ErrorCode`、
  `DeliveryMethodType`、`ContractPlan` の 6 enum を追加する。いずれも応答の解釈に関係し、
  未知の有効値が入ったときに一覧やエラー応答の解釈まで止まる利用者の不利益を重く見る。
  `Sex` と `KouzaType` は元分析では閉集合寄りだったが、オーナー判断として応答の構築失敗を
  避ける方を優先する。出典: `8ea49f5fc5b1616e4a31249e12e291fcf07c6ee6`、
  `df86e33ad76cc4c937e9bddd16be0314973e9147`。
- 番兵は API 仕様上の値ではなく、未知の値を型付きプロパティで表す専用 case とする。
  int enum は ADR 0009 と同じ `-1`、string enum は正規値と衝突しない `unknown` を基本とし、
  正規の `unknown` / `other` と区別する必要がある enum には `__unknown__` を使う。
  現行 case と OpenAPI 定義との非衝突は次表のとおり。出典:
  `8ea49f5fc5b1616e4a31249e12e291fcf07c6ee6`、
  `df86e33ad76cc4c937e9bddd16be0314973e9147`。

  | enum | 仕様上の正規値・既存 case と衝突する候補 | 専用番兵 | 現行値との衝突 |
  | --- | --- | --- | --- |
  | `PaymentType` | `0`〜`45`（`OTHER = 9` を含む） | `-1` | なし |
  | `Sex` | `male`, `female`, `not_applicable` | `unknown` | なし |
  | `KouzaType` | `saving`, `checking` | `unknown` | なし |
  | `ErrorCode` | 既存の `401010`, `404100`, `422210` と実測コード `422007`, `500000` 等 | `unknown` | なし |
  | `DeliveryMethodType` | 正規値 `other` と、他の配送方法 4 値 | `__unknown__` | なし |
  | `ContractPlan` | 正規値 `unknown` と、他の契約プラン 11 値 | `__unknown__` | なし |

- 未知の生値は `getRaw()` に残し、型付きプロパティは番兵にする。
  `toArrayRecursive()` は生値ではなく番兵の backing value を返す非対称性を受け入れる。
  番兵値を API の正規値として扱わず、元値が必要な場合は `getRaw()` を参照する。
  既知の仕様値が enum に欠けている場合はフォールバックで隠さず case を追加する。
  `Sex::NOT_APPLICABLE` はその例である。出典:
  `df86e33ad76cc4c937e9bddd16be0314973e9147`、
  `8ea49f5fc5b1616e4a31249e12e291fcf07c6ee6`、
  `2197642f413beb57db411090de6851b6193b8fab`。
- `ErrorCode` は現行 `src/` から参照されず、`Error::$code` は string なので、
  `FallbackEnum` の実装だけではエラー応答と接続されない。`Error` に
  `getErrorCode(): ErrorCode` を設け、未知コードは専用番兵へ変換する。
  実測記録の `422007`、`500000` などは既知の case として追加する。
  既存の `getCode(): string` は戻り値型と生のコードを利用する呼び出し元との互換性のため維持する。
  出典: `8ea49f5fc5b1616e4a31249e12e291fcf07c6ee6`、
  `076a3182f948c05b43dca8f4b4ce12a8c365ec93`。
- 対象 enum の未知値で例外が出なくなることは利用者にとって挙動変更であり、告知を要する。
  例外を捕捉していた処理と番兵を扱う処理の違いを利用者が把握できるようにする。
  出典: `8ea49f5fc5b1616e4a31249e12e291fcf07c6ee6`、
  `df86e33ad76cc4c937e9bddd16be0314973e9147`。
- 今回追加しない残り 17 enum は現行の扱いを維持する。そのうち
  `ExternalAccountProvider` は既に opt-in 済みであり、他の 16 enum は厳格なままとする。
  実 API で未知の**有効**値を観測した場合、公式仕様が新値を追加した場合、または番兵が
  正規値と衝突した場合は、該当 enum の扱いを個別に再検討する。出典:
  `8ea49f5fc5b1616e4a31249e12e291fcf07c6ee6`、
  `df86e33ad76cc4c937e9bddd16be0314973e9147`。
- 本 ADR は ADR 0009 の opt-in 一覧を拡張する判断の更新である。個別 opt-in、
  番兵の性質、生値保持という原則は継続する。出典:
  `df86e33ad76cc4c937e9bddd16be0314973e9147`、
  `8ea49f5fc5b1616e4a31249e12e291fcf07c6ee6`。

## 代替案と却下理由

- 実害が確認されるまで `ExternalAccountProvider` のみを対象とする案は、未知値を含む
  決済・配送・顧客・ショップの応答が構築できなくなるリスクを残すため採用しない。
  出典: `8ea49f5fc5b1616e4a31249e12e291fcf07c6ee6`。
- `PaymentType` だけを追加する案は、元分析の限定案である。しかし、他の対象でも
  応答構築の継続を優先するというオーナー判断を満たさないため採用しない。
  出典: `8ea49f5fc5b1616e4a31249e12e291fcf07c6ee6`。
- ほぼ閉集合以外へ一律に適用する案は、状態遷移、料金、表示制御の不正値まで
  番兵に吸収し得るため採用しない。出典:
  `8ea49f5fc5b1616e4a31249e12e291fcf07c6ee6`、
  `df86e33ad76cc4c937e9bddd16be0314973e9147`。
- `ContractPlan` の正規 `unknown` や `DeliveryMethodType` の正規 `other` を
  未知値の番兵として兼用する案は、仕様上の値と未認識の値を区別できないため採用しない。
  出典: `8ea49f5fc5b1616e4a31249e12e291fcf07c6ee6`。

## 帰結

対象の応答に未知の有効値が含まれても型付きの番兵として扱える。一方、未知値を例外で
検出していた利用者には挙動変更となる。`getRaw()` と `toArrayRecursive()` の値は一致しない
場合があり、番兵を更新リクエストへそのまま送ると仕様外の値になる。

`ErrorCode` には型付きの解釈を追加し、従来の string の取得経路も残す。
対象外の厳格な enum は未知値に対する例外を維持する。

## 関連

- [ADR 0000: アーキテクチャ上の意思決定を記録する](0000-record-architecture-decisions.md)
- [ADR 0009: 未知の enum 値を opt-in でフォールバックする](0009-opt-in-enum-fallback.md)
- [ADR 0012: 実 API の観測に基づき Entity の null 許容を判断する](0012-allow-nullability-from-api-observations.md)（統合コミット: `45cb7d56ffcfe4db1267739b80c6aed885328641`）
- [enum と公式 OpenAPI の突合記録](../enum-openapi-audit.md)（出典コミット: `8ea49f5fc5b1616e4a31249e12e291fcf07c6ee6`）
- [エラー応答の実測記録](../api-error-responses.md)（出典コミット: `076a3182f948c05b43dca8f4b4ce12a8c365ec93`）
- Issue #39、#54、#55（#54 の統合コミット: `2197642f413beb57db411090de6851b6193b8fab`、#55 の統合コミット: `7371c6101c91dfce848dc83091eb3970b648fef8`）
