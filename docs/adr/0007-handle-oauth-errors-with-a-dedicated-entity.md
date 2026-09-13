# ADR 0007: OAuth エラーを専用 Entity で扱う

- 状態: 採用
- 決定日: 2026-09-13

## 文脈

実 API のエラー応答を 28 ケース収集して検証した結果、ColorMe API 本体はすべて
`{"errors":[{"code":integer,"message":string,"status":integer}]}` 形式を返し、対象項目が
ある場合だけ `field` を加えることが分かった。一方、`POST /oauth/token` に不正なクライアント情報を
送った場合の HTTP 401 応答は、RFC 6749 に従う次の形式だった。

```json
{"error":"invalid_client","error_description":"クライアント認証に失敗しました。クライアントIDが正しいかご確認ください。"}
```

OAuth 応答には `errors` 配列がないため、従来の `Communicator\Errors::build()` では要素数 0 の
コレクションになる。利用者は元のレスポンス本文を直接解析しなければ `invalid_client` と説明を
構造化して取得できなかった。

## 判断

- RFC 6749 のエラーレスポンスを表す `Entities\OAuth\ErrorResponse` を追加する。既存の
  `Entities\Error` と意味も構造も異なることをクラス名と名前空間で明示する。
- `ErrorResponse` は既存の `Entities\Entity` を継承し、必須の `error` と、省略可能な
  `error_description`、`error_uri`、`state` を型付きプロパティとして保持する。必須値の欠損は
  getter で `MissingFieldException`、存在する値の型不正は構築時に `InvalidFieldException` とする。
- `getRaw()` と `toArray()` は Entity の既存契約を利用する。追加プロパティは `getRaw()` に残し、
  `toArray()` には RFC 6749 の4フィールドだけを含める。
- `ErrorResponse` は元の `Communicator\Response` を保持し、`getResponse()` から HTTP ステータス、
  生ボディ、リクエスト情報を調査できるようにする。
- `Services\OAuth::exchangeCode2Token()` と委譲元の `Client::exchangeCode2Token()` は、パース済み
  ボディに `error` キーがあれば `ErrorResponse`、`errors` キーがあれば従来の `Errors` を返す。
  HTTP ステータスよりレスポンス形式を優先するため、2xx に `error` キーがある場合も前者として扱う。
  OAuth フィールドが不正型で Entity を構築できない場合と、どちらのキーもない非 2xx 応答は、元の
  レスポンスを失わないよう空の場合も `Errors` として返す。
- 2xx でも空ボディまたは非配列 JSON の応答は、有効なアクセストークンにも RFC 6749 のエラーにも
  構造化できない。PR #23 で導入した診断経路を維持し、元レスポンスを保持する空の `Errors` を返す。

## 代替案と却下理由

- OAuth の `error` と `error_description` を `Errors` または `Entities\Error` に詰め込む案は、
  `code`、`message`、`status` を持つ ColorMe API 本体の形式と意味が異なり、利用者がどちらの形式かを
  型で判別できなくなる。存在しない整数ステータスやコードを合成する必要もあるため却下した。
- OAuth エラーだけ例外として投げる案は、HTTP エラーを戻り値として扱い、元レスポンスを参照できる
  既存 API の利用方法と一致しない。通信成功後のプロトコルエラーだけ制御フローが変わるため却下した。
- `Errors` の件数を 0 のままにして生ボディの利用だけを案内する案は、標準化されたフィールドを毎回
  利用者が JSON 解析する必要があり、型付きクライアントとして必要な情報を提供できないため却下した。

## 帰結

実 API で観測した OAuth エラーコードと日本語の説明を型付き getter から取得でき、必要に応じて元の
HTTP ステータスと生ボディも調査できる。ColorMe API 本体の `Errors` と `Entities\Error` の挙動は
変更せず、OAuth エンドポイントが将来 `errors` 配列形式を返した場合も従来どおり処理できる。

一方、`Client::exchangeCode2Token()` と `Services\OAuth::exchangeCode2Token()` の公開戻り値は
`AccessToken|Errors` から `AccessToken|ErrorResponse|Errors` に変わる。OAuth の失敗を `Errors` だけで
判定していた利用者には破壊的変更となるため、0.9.0 に含める。

## 関連

- [RFC 6749 Section 5.2: Error Response](https://www.rfc-editor.org/rfc/rfc6749#section-5.2)
- PR #23
