# 日時フィールドの実測記録

## この文書の位置づけと収集条件

この文書は、API が unixtime で返す日時フィールドを Entity がどう表現するかの判断のため、実 API の
挙動を記録する。現在のライブラリ仕様ではない。実測の出典はこの文書を追加・更新した各コミットであり、
[ADR 0000](adr/0000-record-architecture-decisions.md) の収集条件・検証可能性の規則に従う。

収集日は **2026-09-29（Asia/Tokyo）**。対象はテスト用ショップ。売上集計は本ライブラリの
`Communicator\Request` で認証済みの GET を実行した。OAuth トークンは、`localhost` でコールバックを
受け取る一時的な検証環境を立て、認可フローを 1 度だけ実行して観測した。

アクセストークン、client secret、認可コードは記録しない。キー、型、値の意味だけを残す。

## 背景

このライブラリは、API が unixtime（integer）で返す日時フィールドを Entity では `int` で保持し、
getter で `DateTimeImmutable` を返す慣行を持つ。日時らしき `int` プロパティ 43 件のうち 41 件は
この形だったが、`Sales\Stat::$date` と `OAuth\AccessToken::$createdAt` の 2 件だけが `int` のまま
だった。いずれも unixtime であるという確証がなかったため、実測して確かめた。

## 売上集計の `date`（GET /v1/sales/stat）

公式 OpenAPI では `type: integer`、description は「集計の基準日」、example は `1363151732`。

クエリパラメータは `make_date`（string、形式は `2017-04-12` や `2017/04/12` など、省略時は今日）で
ある。本ライブラリの `Services\Sales::stat()` は `DateTimeInterface` を受け取り `Y-m-d` で送信する。

| 送った `make_date` | HTTP | 返った `date` | 解釈 |
| --- | ---: | --- | --- |
| `2026-09-01` | 200 | `1788188400` | 2026-09-01 00:00:00 JST（UTC では前日 15:00） |
| `2022-06-15` | 200 | `1655218800` | 2022-06-15 00:00:00 JST |
| `2022/06/15` | 200 | `1655218800` | 同上。スラッシュ形式も受理される |

**`date` は unixtime であり、基準日の 00:00:00 JST を指す。** 集計値も基準日に応じて変わり、
`make_date` が実際にフィルタとして機能していることを確認した。

なお、存在しないパラメータ名 `date` を送った場合は `make_date` 省略時と同じ扱いになり、当日の
基準日が返った。

## OAuth トークンの `created_at`（POST /oauth/token）

公式 OpenAPI にトークンエンドポイントの formal schema はない。`info.description` に示された応答例は
`access_token` / `token_type` / `scope` の 3 つだけで、**`created_at` は spec 全体で 1 度も
現れない**（`external_accounts[]` と `shop_coupon` の同名フィールドを除く）。

実測した応答は次のとおり。

| 項目 | 観測 |
| --- | --- |
| HTTP | 200 |
| 応答のキー | `access_token`, `token_type`, `scope`, **`created_at`** |
| キーの型 | すべて string、`created_at` のみ **int** |
| `created_at` の値 | トークン発行時刻の unixtime（観測時の現在時刻と一致） |
| `token_type` | **`Bearer`**（先頭大文字） |

**`created_at` は実在し、unixtime である。** 公式ドキュメントの応答例に記載がないだけで、実 API は
返す。

`token_type` は公式の応答例では `bearer`（小文字）だが、実 API は `Bearer` を返した。本ライブラリは
`token_type` の値を比較していないため実害はない。`Communicator\RequestOptions` は Authorization
ヘッダを組み立てる際に常に `Bearer ` を前置する。

### 検証環境について

認可コードは 1 度しか交換できず、`.env` にも保存されないため、通常の実測スクリプトでは観測できない。
今回は `localhost` の一時サーバーでコールバックを受け取る方法をとった。この環境はリポジトリには
含めていない（サンプルとして整備する場合は別途検討する）。

観測で発行したアクセストークンはカラーミー側で有効なまま残るため、不要であれば管理画面の許可済み
アプリ一覧から失効させる必要がある。

## 未観測

- 売上集計の `date` が `null` になる場合。公式 OpenAPI に nullable 指定はなく、観測でも常に整数だった。
- OAuth トークン応答に `expires_in` や `refresh_token` が含まれる場合。公式ドキュメントは
  「アクセストークンに有効期限はありません」と明記しており、観測した応答にもこれらのキーはなかった。

## 関連

- [ADR 0000: アーキテクチャ上の意思決定を記録する](adr/0000-record-architecture-decisions.md)
- [Entity のフィールド突合記録](entity-field-audit.md)
- [エラー応答の実測記録](api-error-responses.md)
- [公式 OpenAPI](https://api.shop-pro.jp/v1/spec/open_api.json)（2026-09-29 取得）
