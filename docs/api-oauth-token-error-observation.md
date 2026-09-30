# トークンエラー応答の実測記録

## この文書の位置づけと収集条件

この文書は、`Entities\OAuth\ErrorResponse` が表すトークンエンドポイントのエラー応答について、
実 API の挙動を記録する。現在のライブラリ仕様ではない。実測の出典はこの文書を追加・更新した各
コミットであり、[ADR 0000](adr/0000-record-architecture-decisions.md) の収集条件・検証可能性の規則に
従う。

収集日は **2026-09-30（Asia/Tokyo）**。対象はテスト用ショップのアプリ。`POST /oauth/token` へ、
本ライブラリの `Communicator\Request` でフォーム送信した。リダイレクト URI はアプリに登録済みの
`http://localhost:8765/callback` を使った。

**有効な認可コードは使っていない。** 実在しない 64 文字の値を送っており、この観測でアクセストークンは
発行されていない。client secret は記録しない。応答ボディに client secret が含まれないことも確かめた。

## 背景

`ErrorResponse` は `error` / `error_description` / `error_uri` / `state` の 4 フィールドを型付きで
保持し、`getState()` を公開している（[ADR 0007](adr/0007-handle-oauth-errors-with-a-dedicated-entity.md)）。

しかし [RFC 6749](https://datatracker.ietf.org/doc/html/rfc6749) で `state` を含むエラー応答は、
認可エンドポイントがリダイレクトのクエリで返す認可エラー応答（§4.1.2.1）である。トークン
エンドポイントのエラー応答（§5.2）は `error` / `error_description` / `error_uri` の 3 つで、`state`
を定義していない。

| | 定義 | 現れる場所 | フィールド |
| --- | --- | --- | --- |
| 認可エラー応答 | §4.1.2.1 | リダイレクト URI のクエリ文字列 | `error`, `error_description`, `error_uri`, `state` |
| トークンエラー応答 | §5.2 | `POST /oauth/token` の JSON ボディ | `error`, `error_description`, `error_uri` |

`ErrorResponse` を生成するのは `Services\OAuth::exchangeCode2Token()` だけで、渡すのはトークン
エンドポイントの応答ボディである。公式 OpenAPI にトークンエンドポイントの formal schema はなく、
エラー時の応答例もない。RFC にないフィールドでもカラーミーが独自に返している可能性は否定できない
（公式の応答例にない `created_at` が実在した前例がある。[日時フィールドの実測記録](api-unixtime-observation.md)）
ため、実測した。

## 観測

| | 条件 | HTTP | 返ったキー | `error` | `state` |
| --- | --- | ---: | --- | --- | --- |
| A | 実在しない認可コード | 400 | `error`, `error_description` | `invalid_grant` | なし |
| B | A のリクエストに `state` を添える | 400 | `error`, `error_description` | `invalid_grant` | なし |
| C | 誤った client secret | 401 | `error`, `error_description` | `invalid_client` | なし |
| D | `grant_type=password` | 403 | （空ボディ） | — | — |
| E | `code` を送らない | 400 | `error`, `error_description` | `invalid_request` | なし |

**トークンエラー応答に `state` は現れない。** リクエストに `state` を添えても（ケース B）返さない。
RFC 6749 §5.2 の定義どおりである。

`exchangeCode2Token()` をケース A と同じ条件で呼ぶと `ErrorResponse` が返り、`getState()` は
`null` だった。

`error_uri` もどのケースにも現れなかった。

### `error_description` の言語はケースで異なる

| | `error_description` |
| --- | --- |
| A, B | `The provided authorization grant is invalid, expired, revoked, does not match the redirection URI used in the authorization request, or was issued to another client.` |
| C | `クライアント認証に失敗しました。クライアントIDが正しいかご確認ください。` |
| E | `Missing required parameter: code.` |

英語の定型文と日本語が混在する。C の日本語は JSON の `\uXXXX` エスケープで返り、デコード後の
文字列は上表のとおりである。RFC 6749 §5.2 は `error_description` を US-ASCII の範囲に限っており、
C はその規定から外れる。認可エラー応答の `error_description` も日本語だった
（[認可応答の `state` の実測記録](api-oauth-state-observation.md)）。

### 未対応の `grant_type` は 403 の空ボディ（ケース D）

RFC 6749 §5.2 に従えば `400` と `unsupported_grant_type` が返るはずだが、`403` で空のボディが返った。
`exchangeCode2Token()` は `grant_type=authorization_code` しか送らないため、ライブラリ利用者がこの
応答に当たることはない。本件とは別の差異として記録だけ残す。

## 確かめていないこと

- 実在する認可コードを使ったときのエラー（使用済み、期限切れ、`redirect_uri` の不一致など）。
  いずれも A と同じ `invalid_grant` と考えられるが、観測していない
- `invalid_scope` や `unauthorized_client` など、認可コードの交換では起こしにくいエラー
- ケース D の 403 が認可サーバーの応答か、手前の中継層の応答か
- 観測は 1 アプリで、各ケース 1 回ずつである

## 関連

- [ADR 0007: OAuth エラーを専用 Entity で扱う](adr/0007-handle-oauth-errors-with-a-dedicated-entity.md)
- [認可応答の `state` の実測記録](api-oauth-state-observation.md)
- Issue #87
