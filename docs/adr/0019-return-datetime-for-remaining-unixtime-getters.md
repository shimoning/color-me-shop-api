# ADR 0019: 残る 2 つの unixtime getter を DateTimeImmutable へ変更する

- 状態: 採用
- 決定日: 2026-09-29

## 文脈

このライブラリは、API が unixtime（integer）で返す日時フィールドを Entity では `int` で保持し、
getter で `DateTimeImmutable` を返す慣行を持つ。日時らしき `int` プロパティ 43 件のうち 41 件は
この形だったが、次の 2 件だけが `int` のまま残っていた。出典: `073dfe4`。

| クラス | プロパティ | 残っていた理由 |
| --- | --- | --- |
| `Sales\Stat` | `$date` | 公式 OpenAPI は `type: integer`「集計の基準日」とだけ定め、値の意味を示していない |
| `OAuth\AccessToken` | `$createdAt` | 公式 OpenAPI にトークンエンドポイントの formal schema がなく、`info.description` の応答例にも `created_at` の記載がない |

`$createdAt` については、[Entity のフィールド突合記録](../entity-field-audit.md)が「OpenAPI だけでは
妥当性を確定できない」と留保していた。2022 年に OpenAPI の応答例と一致する形で作られた後、翌日の
コミットで根拠の記録なく追加されたフィールドであり、実在するかも確かめられていなかった。

2026-09-29 に両方を実測した。詳細は[日時フィールドの実測記録](../api-unixtime-observation.md)に
ある。出典: `9da693d`。

- `GET /v1/sales/stat` の `date` は unixtime で、基準日の 00:00:00 JST を指す
- `POST /oauth/token` は `access_token` / `token_type` / `scope` / `created_at` の 4 キーを返し、
  `created_at` は unixtime である。公式ドキュメントの応答例に記載がないだけで実在する

OAuth は認可コードが 1 度しか交換できずブラウザでの承認を挟むため、`localhost` でコールバックを
受ける一時環境を立てて認可フローを 1 度だけ実行して観測した。**1 ショップ・1 回の観測であり、
存在と型を確かめる範囲に留まる。**

[ADR 0003](0003-prefer-semantic-types-over-legacy-coercion.md) は「暗黙変換で既存挙動を再現するより、
API の意味とライブラリ内の一貫性に合う型を選ぶ」ことを定め、その帰結として「互換性を変える場合は、
影響を受ける呼び出し方を明示して判断を残す」としている。本 ADR はその個別の記録である。

## 判断

- `Sales\Stat::getDate()` と `OAuth\AccessToken::getCreatedAt()` の戻り値を `DateTimeImmutable` に
  変更する。実測で両方とも unixtime と確認でき、41 件の先例と揃える理由が立つためである。
  出典: `3395232`。
- プロパティは `int` のまま保持し、getter で `(new DateTimeImmutable)->setTimestamp()` を返す。
  既存の `Delivery::getMakeDate()` と同じ形にする。応答の生値を Entity が保持する契約を変えない。
  出典: `3395232`。
- 元の整数が必要な利用者には `Entity::getRaw()` を退避経路とする。新しい getter は追加しない。
  出典: `3395232`。
- 公式仕様と実測が食い違う点は、該当する getter の PHPDoc に差異と観測日を明記する。
  `getCreatedAt()` は公式応答例に記載がないこと、`getTokenType()` は公式例の `bearer` に対し実測が
  `Bearer` であること、`getDate()` は公式が値の意味を示していないことをそれぞれ記す。
  [CONTRIBUTING.md](../../CONTRIBUTING.md) の規約に従う。出典: `bf5ee64`。
- fixture は実 API の形に合わせる。`sales_stat.json` の `date` は `20240101` という YYYYMMDD 風の
  整数で実 API と一致していなかったため unixtime にし、`oauth_token.json` の `token_type` は実測
  どおり `Bearer` にする。出典: `3395232`。

## 代替案と却下理由

- 2 件を `int` のまま残す案は、破壊的変更を避けられる。しかし 43 件中 2 件だけが不統一という状態が
  残り、利用者は getter ごとに戻り値の型を確かめる必要がある。実測で unixtime と確定した以上、
  不統一を残す理由がないため採用しない。
- `getDateTime()` のような getter を別に追加し、既存の `int` 版も残す案は、互換性を保てる。しかし
  同じ値に 2 つの入口ができ、41 件の先例とも揃わない。公開 API が膨らむだけで、`getRaw()` という
  既存の退避経路があるため採用しない。
- `int` のまま残して PHPDoc にだけ「unixtime である」と書く案は、変更が最小である。しかし
  ADR 0003 が定めた「API の意味に合う型を選ぶ」方針に反し、利用者が毎回 `DateTimeImmutable` へ
  変換することになるため採用しない。

## 帰結

日時を返す getter の戻り値型が、43 件すべてで `DateTimeImmutable`（nullable なものは
`?DateTimeImmutable`）に揃う。利用者は getter ごとに型を確かめる必要がなくなる。

一方、2 つの getter の戻り値型が変わる破壊的変更である。`getDate()` / `getCreatedAt()` の戻り値を
整数として扱っていた利用者は修正が必要になる。元の整数は `getRaw()` から取得できる。

`OAuth\AccessToken::$createdAt` は、2022 年以来はじめて実在と型が確認された。ただし 1 ショップ・
1 回の観測であり、公式ドキュメントに記載がないフィールドである点は変わらない。API 側が応答から
外した場合、`getCreatedAt()` は `MissingFieldException` になる。

## 関連

- [ADR 0000: アーキテクチャ上の意思決定を記録する](0000-record-architecture-decisions.md)
- [ADR 0003: 暗黙変換より意味に合う型を選ぶ](0003-prefer-semantic-types-over-legacy-coercion.md)
- [日時フィールドの実測記録](../api-unixtime-observation.md)（出典コミット: `9da693d`）
- [Entity のフィールド突合記録](../entity-field-audit.md)
- 実装と fixture の出典コミット: `3395232`
- PHPDoc への差異明記の出典コミット: `bf5ee64`
- 既存 PHPDoc の `last_7days` の誤記訂正（本 ADR の判断とは別件）の出典コミット: `ecbe363`
