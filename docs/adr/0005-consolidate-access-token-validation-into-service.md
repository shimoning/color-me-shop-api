# ADR 0005: アクセストークン検証を Service に集約する

- 状態: 採用
- 決定日: 2026-09-11

## 文脈

`Client` では、アクセストークンの同一の検証が 8 ブロックに重複していた。一方、
`Services\Service` を直接生成する利用者にはこの検証が適用されず、空文字から `Bearer ` という
不正な Authorization ヘッダを生成し、送信し得た。また、`Client` の検証が `empty()` を使っていた
ため、空文字ではない文字列 `"0"` も意図せず拒否していた。

## 判断

- アクセストークンの検証を、基底 `Services\Service` の副作用のない private static バリデータへ
  集約する。
- バリデータは、コンストラクタでトークンを保持する前と、`_request()` が選択した実効値の両方から
  呼び出す。これにより、Service メソッドへ渡された上書きトークンも HTTP 送信前に検証する。
- 空文字は厳密に `=== ""` で判定し、文字列 `"0"` は有効な不透明トークンとして扱う。
- `Client` 自身の検証は削除し、Service の生成またはリクエスト生成へ検証を委譲する。
- `Services\OAuth` は Bearer トークンを入力に取らないため、本判断の対象外とする。

## 代替案と却下理由

- `Values\AccessToken` 値オブジェクトを導入する案は、既存の `Entities\OAuth\AccessToken` と名称が
  衝突し、公開シグネチャへの影響も大きいため、今回は見送る。
- `Client` の検証を残したまま Service にも検証を追加する段階移行案は、検証が二重化し、将来の
  変更で両者が乖離するリスクが残るため採用しない。

## 帰結

挙動が変わる条件は次のとおりである。

| 条件 | 変更後 |
| --- | --- |
| `Client` の API メソッドで保持値または明示 override が空文字 | Service 生成時に HTTP 送信前の `ParameterException` |
| 具象 Service を空文字で直接生成 | **コンストラクタで** `ParameterException` (従来は生成でき、`Bearer ` を送信し得た) |
| 非空トークンの Service メソッドへ空文字を override | リクエスト生成時に `ParameterException` |
| トークン未指定の `new Client()` で非 OAuth API を呼ぶ | PHP の生の `Error` ではなく `ParameterException` |
| 文字列 `"0"` | **有効な不透明トークンとして受理**し `Bearer 0` を送信 (従来は `empty()` により拒否) |
| `null` の override | 従来どおり「override なし」 |
| その他の非空文字列 | 変更なし |

明示的な空文字は Client の状態に残るため、後続呼び出しも保持中の旧トークンへフォールバックせず
失敗する。この挙動変更は破壊的変更として 0.9.0 でリリースする。

## 関連

- 本 ADR は [ADR 0001: テスト境界と HTTP クライアント注入](0001-testing-boundaries-and-http-client-injection.md)
  のアクセストークン検証に関する判断を更新する。
- 出典コミット: `714c883`、`def5b8e`。
