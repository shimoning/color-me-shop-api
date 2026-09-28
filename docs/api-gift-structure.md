# ギフト API 応答構造の実測記録

## この文書の位置づけと収集条件

この文書は、ギフト API の Entity 設計のため、実 API の挙動を記録する。現在のライブラリ仕様ではない。
実測の出典はこの文書を追加・更新した各コミットであり、[ADR 0000](adr/0000-record-architecture-decisions.md)
の収集条件・検証可能性の規則に従う。

収集日は **2026-09-28（Asia/Tokyo）**。対象はテスト用ショップ。本ライブラリの `Communicator\Request` で
認証済みの GET を 1 回実行した。公式との比較には、同日取得した
[公式 OpenAPI](https://api.shop-pro.jp/v1/spec/open_api.json) の `GET /v1/gift` の定義を用いた。

ショップ識別子、アクセストークン、注意事項の文面は載せない。キー、型、`null`・欠損、配列の要素数だけを残す。

## 対象 API

`GET /v1/gift`（`getGift`）。ギフト設定を取得する。パラメータはなく、書き込み API もない。
`security` は `[{OAuth2: []}]` で、OAuth2 認証を要求するがスコープの宣言が空である
（[スコープの突合記録](auth-scope-audit.md)）。

## 応答の構造

HTTP `200`。トップレベルは `gift` で、キー集合は**公式 OpenAPI の定義と完全に一致**した。欠損も余分なキーもない。

| キー | 観測型 | OpenAPI の nullable | 備考 |
| --- | --- | --- | --- |
| `account_id` | string | 指定なし | |
| `enabled` | boolean | true | `null` は未観測 |
| `noshi` | object | 指定なし | |
| `noshi.enabled` | boolean | true | |
| `noshi.text_enabled` | boolean | true | |
| `noshi.text_charge` | integer | true | |
| `noshi.types` | 配列（要素 1） | 指定なし | 要素は `{name: string, charge: integer}` |
| `noshi.comment` | string | true | 非空 |
| `card` | object | 指定なし | |
| `card.enabled` | boolean | true | |
| `card.text_enabled` | **null** | true | |
| `card.types` | **空配列** | 指定なし | |
| `card.comment` | **null** | true | |
| `wrapping` | object | 指定なし | |
| `wrapping.enabled` | boolean | true | |
| `wrapping.types` | **空配列** | 指定なし | |
| `wrapping.comment` | **null** | true | |
| `make_date` / `update_date` | integer | true | `null` は未観測 |

`types[]` の要素 `{name, charge}` は 3 箇所とも同じ定義で、`name` / `charge` に nullable 指定はない。
`card` に `text_charge` はなく、`wrapping` に `text_enabled` / `text_charge` はない。

## 未観測

- トップレベル `enabled`、`noshi` 配下の各値、`make_date` / `update_date` の `null`。
- `card.types` / `wrapping.types` の要素（いずれも空配列だった）。要素の型は `noshi.types` の観測と
  OpenAPI 定義に基づく。
- `types[]` の `name` / `charge` の `null`。

## 関連

- [ADR 0002: Entity の null 許容を OpenAPI に合わせる](adr/0002-entity-nullability-from-openapi.md)
- [ADR 0008: 構造化された応答フィールドを Entity で表現する](adr/0008-model-structured-response-fields-as-entities.md)
- [OAuth スコープと公式 OpenAPI の突合記録](auth-scope-audit.md)
- [公式 OpenAPI](https://api.shop-pro.jp/v1/spec/open_api.json)（2026-09-28 取得）
