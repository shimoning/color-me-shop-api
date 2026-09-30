# ADR 0021: トークンエラー応答から state を外す

- 状態: 採用
- 決定日: 2026-09-30

## 文脈

[ADR 0007](0007-handle-oauth-errors-with-a-dedicated-entity.md) は、トークンエンドポイントのエラー応答を
表す `Entities\OAuth\ErrorResponse` を導入した。このとき、必須の `error` と、省略可能な
`error_description`・`error_uri`・`state` を型付きプロパティとして保持し、`toArray()` には
「RFC 6749 の 4 フィールド」を含めると決めた。

しかし RFC 6749 でこの 4 フィールドが揃うのは、認可エンドポイントがリダイレクトのクエリで返す
**認可エラー応答（§4.1.2.1）** である。`ErrorResponse` が表す**トークンエラー応答（§5.2）** は
`error` / `error_description` / `error_uri` の 3 フィールドで、`state` を定義していない。ADR 0007 は
この 2 つを取り違えていた。

| | 定義 | 現れる場所 | フィールド |
| --- | --- | --- | --- |
| 認可エラー応答 | §4.1.2.1 | リダイレクト URI のクエリ文字列 | `error`, `error_description`, `error_uri`, `state` |
| トークンエラー応答 | §5.2 | `POST /oauth/token` の JSON ボディ | `error`, `error_description`, `error_uri` |

`ErrorResponse` を生成するのは `Services\OAuth::exchangeCode2Token()` だけで、渡すのはトークン
エンドポイントの応答ボディである。認可エラー応答はリダイレクトのクエリで利用者のコールバックに届く
ものであり、ライブラリはこれを受け取る経路を持たない。

この取り違えは [ADR 0020](0020-accept-state-in-authorization-url.md) で認可 URL に `state` を渡せる
ようにしたことで表に出た。README では、コールバックで照合する `state` の説明の少し下に、
`ErrorResponse::getState()` を「認可リクエストと応答を対応付ける値」として紹介する記述が並んでいた。
読者は照合に使う値がここから取れると読みうる。Issue #87 はこの問題を切り出したものである。

RFC の定義にないフィールドでも、カラーミーが独自に返している可能性はあった。公式 OpenAPI に
トークンエンドポイントの formal schema はなく、エラー時の応答例もない。公式の応答例にない
`created_at` が実在した前例もある。そこで 2026-09-30 に実測した。詳細は
[トークンエラー応答の実測記録](../api-oauth-token-error-observation.md)にある。出典: `f99ba5e`。

- 5 条件でエラーを起こした。4 件は `error` と `error_description` の 2 キーからなる OAuth エラー
  応答で、`error_uri` は現れなかった。残る 1 件（未対応の `grant_type`）は 403 の空ボディだった
- **いずれの応答にも `state` は現れなかった。** リクエストに `state` を添えても返さなかった

## 判断

- `ErrorResponse` から `state` プロパティを削除する。保持する型付きフィールドを RFC 6749 §5.2 の
  3 つに揃える。実測でも実 API が返さないことを確かめた。出典: `05c302b`。
- **`toArray()` と `toArrayRecursive()` から `state` を外す。** プロパティの削除により、Entity の既存の
  仕組みのまま自然に外れる。ADR 0007 の「`toArray()` には RFC 6749 のフィールドだけを含める」という
  意図は、正しいフィールドの集合で保たれる。出典: `05c302b`。
- `getState()` は削除せず非推奨とする（`@deprecated 0.19.0`）。次のメジャーな変更で削除する。値は
  `getRaw()` から読み、文字列のときだけ返し、それ以外は `null` を返す。呼び出している利用者のコードが
  すぐに壊れないようにするためである。出典: `05c302b`。
- 応答に `state` が含まれた場合は、他の未知のキーと同じく追加プロパティとして `getRaw()` にだけ残す。
  ADR 0007 の「追加プロパティは `getRaw()` に残す」規則に従う。出典: `05c302b`。
- README では、コールバックで照合する認可応答の `state` と区別できるように書き分け、`toArray()` の
  説明も 3 フィールドに直す。出典: `fbdbe44`。

ADR 0007 は採用済みのため書き換えず、「ADR 0021 により更新」の参照だけを追記する。

## 代替案と却下理由

- **`state` プロパティを残し、`toArray()` と `toArrayRecursive()` をオーバーライドして出力から除く案**は、
  型検証が残り、変わる挙動を配列化だけに絞れる。しかし Entity の基底の仕組みを迂回する特例が
  `ErrorResponse` にだけ入る。RFC の定義になく実 API も返さないフィールドを型検証し続けることにもなり、
  構造と出力の食い違いが残るため採用しない。
- **`getState()` も即座に削除する案**は、構造が最も素直になる。しかし呼び出している利用者のコードが
  未定義メソッドの呼び出しで即座に壊れる。値を返し続けても害はなく、非推奨の期間を置く方が利用者に
  やさしいため採用しない。
- **`toArray()` の `state` は非推奨の期間中は残し、`getState()` の削除と同時に外す案**は、挙動の変更を
  1 回にまとめられる。しかし RFC にも実 API にもないキーを、配列化の出力に `null` として載せ続けること
  になる。`0.x` であり、破壊的変更もマイナーバージョンの更新で行う規約（CONTRIBUTING.md）のため、
  先送りする利点が小さい。利用者と協議のうえ採用しない。
- **何も変えず PHPDoc と README の説明だけを直す案**は、変更が最小である。しかし「RFC 6749 の 4
  フィールド」という誤った前提が型と配列化の出力に残り、説明とコードが食い違ったままになるため採用
  しない。
- **`ErrorResponse` を認可エラー応答にも使えるよう、コールバックのクエリから組み立てる手段を加える案**は、
  `state` の置き場所として意味が通る。しかしライブラリはコールバックを受け取る経路を持たず、HTTP 層の
  抽象を持ち込む必要がある。ADR 0020 で照合ヘルパーを設けなかったのと同じ理由で採用しない。

## 帰結

`ErrorResponse` の型付きフィールドと `toArray()` の出力が、RFC 6749 §5.2 のトークンエラー応答に
一致する。README 上で 2 つの `state` を取り違える余地がなくなる。

次の挙動が変わる。`0.x` のため、次のマイナーバージョン（0.19.0）で行う。

- `toArray()` と `toArrayRecursive()` の出力から `state` キーが消える。配列化した結果を `state` キー
  込みで扱っていたコードは修正が必要になる
- 応答の `state` が文字列以外だった場合、これまでは構築時に `InvalidFieldException` となり、
  `exchangeCode2Token()` は `Errors` へフォールバックしていた。今後は `ErrorResponse` を返し、
  `getState()` は `null` になる。実 API は `state` を返さないため、実際に影響する場面はないと考えている
- `getState()` の呼び出しは引き続き動作する。`@deprecated` を解釈する IDE や静的解析のルールでは警告の対象になる
- 0.18.0 以前に `serialize()` した `ErrorResponse` を `unserialize()` すると、旧データの `state` が
  宣言のない動的プロパティとして復元され、PHP 8.2 以降では `Creation of dynamic property ... is
  deprecated` の非推奨警告が出る（PHP 8.1 では出ない）。`getState()`・`toArray()`・`getRaw()` の値は
  正しく、動作は止まらない。エラー応答を直列化して保存する使い方はまれと考え、`__unserialize()` で
  旧 `state` を捨てる対応はしない。Entity 基底の private プロパティの復元を自前で再現する必要があり、
  変更の複雑さに見合わないためである。利用者と協議のうえ許容する

トークンエラー応答の実測は 1 アプリで、各ケース 1 回ずつである。カラーミーが将来 `state` を返す
ようになっても、`getRaw()` と非推奨の `getState()` から取得できる。

## 関連

- [ADR 0000: アーキテクチャ上の意思決定を記録する](0000-record-architecture-decisions.md)
- [ADR 0007: OAuth エラーを専用 Entity で扱う](0007-handle-oauth-errors-with-a-dedicated-entity.md)（本 ADR により更新）
- [ADR 0020: 認可 URL に state を受け取る](0020-accept-state-in-authorization-url.md)
- [トークンエラー応答の実測記録](../api-oauth-token-error-observation.md)（出典コミット: `f99ba5e`）
- Issue #87
- 実装とテストの出典コミット: `05c302b`
- README の出典コミット: `fbdbe44`
