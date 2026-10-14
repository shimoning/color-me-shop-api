# ADR 0048: API の結果を表す型を Results 名前空間に移す

- 状態: 採用
- 決定日: 2026-10-15

## 文脈

API の呼び出しは、成功の Entity のほかに、失敗や本文のない成功を表す型を返す。これらの型の置き場所が割れていた。

- `Communicator\Errors` は、ColorMe API 本体のエラー応答を表す。`Entities\Error` の一覧で、`Entities\Collection` を
  継承し、元の `Communicator\Response` を持つ
- `Communicator\NoContent` は、`204 No Content` の成功を表す。元の `Response` だけを持つ
  （[ADR 0014](0014-model-product-write-api.md)）
- `Entities\OAuth\ErrorResponse` は、OAuth のトークンエンドポイントのエラー応答を表す。`Entities\Entity` を継承し、
  元の `Response` を持つ（[ADR 0007](0007-handle-oauth-errors-with-a-dedicated-entity.md)）

`Communicator` には、ほかに `Request`、`RequestOptions`、`RequestMeta`、`Response` があり、通信そのものを扱う。
`Errors` は `Entities` のクラスを継承しながら `Communicator` に置かれていた。また、どちらもエラーの内容と元の `Response` を
持つ `Errors` と `ErrorResponse` の置き場所が、`Communicator` と `Entities` に分かれていた。

## 判断

- **API の呼び出しが返す結果のうち、元の `Response` を持つ型を `Results` 名前空間に置く。** `Errors` と `NoContent` を
  `Results` に、`ErrorResponse` を `Results\OAuth` に移す。`Communicator` は通信そのものを扱う型だけにする。
  利用者の指定による。出典: `5aed6b4`。
  - `ErrorResponse` は OAuth に固有のため、[ADR 0047](0047-nest-constants-by-url.md) と同じく API の名前空間を付ける。
    `Results` の直下で `Errors` と並ぶと、どちらが何のエラーか名前から分からなくなる。利用者の指定による
  - 名前空間の名前は、HTTP の応答を表す `Communicator\Response` と紛らわしくないよう `Results` にする。利用者の
    指定による
- **型の継承は変えない。** `Errors` は `Entities\Collection` を、`ErrorResponse` は `Entities\Entity` を継承したまま
  にする。エラー 1 件を表す `Entities\Error` は、API の本文から作るデータなので `Entities` に残す。出典: `5aed6b4`。
- 旧名は [ADR 0016](0016-unify-request-input-entity-names.md) と同じ仕組みで非推奨の別名として残し、次のメジャーな変更で
  削除する。利用者の指定による。出典: `5aed6b4`。
- 0.32.0 以前に `serialize()` した `Errors`、`NoContent`、`ErrorResponse` も復元できるようにする。PHP の private プロパティの
  キーには宣言したクラスの名前が入るため、別名だけでは元の `Response` が復元されない。旧名のキーを新名に読み替え、
  PHP 8.2 以降でも、移動による非推奨警告を新たに出さない。0.18.0 以前の `ErrorResponse` の `state` は、
  [ADR 0021](0021-drop-state-from-token-error-response.md) のとおり動的プロパティとして復元する。この読み替えも、別名と
  同じく次のメジャーな変更で削除する。出典: `998750f`。

## 代替案と却下理由

- **配置を変えず、`Communicator` には通信の結果として元の `Response` を持つ型を置くと規則を書く案**は、利用者のコードを
  書き換えずに済む。しかし `Errors` と同じく元の `Response` を持つ `ErrorResponse` が `Entities` に残り、規則と配置が
  揃わない。
- **`Errors` だけを `Entities` に移す案**は、`Collection` と `Error` の隣に並ぶ。しかし Entity ではない `NoContent` が
  `Communicator` に残り、結果の型が 2 か所に分かれたままになる。

## 帰結

API の呼び出しが返す型は、成功の Entity を除いて `Results` から探せる。`Communicator` は通信そのものを扱う。

次の点が変わる。

- `Errors` は多くの呼び出しで `instanceof` に使う型のため、旧名を参照する利用者のコードの多くが非推奨の参照になる。
  旧名のままでも動作する
- `::class` や `get_class()` などで得るクラス名の文字列は新名になる（[ADR 0029](0029-drop-parent-prefix-from-entity-names.md)
  と同じ）

## 関連

- [ADR 0000: アーキテクチャ上の意思決定を記録する](0000-record-architecture-decisions.md)
- [ADR 0007: OAuth エラーを専用 Entity で扱う](0007-handle-oauth-errors-with-a-dedicated-entity.md)
- [ADR 0014: 商品書き込み API の入力と空レスポンスを表現する](0014-model-product-write-api.md)
- [ADR 0016: 要求側入力 Entity の命名を統一し、旧名を非推奨の別名で残す](0016-unify-request-input-entity-names.md)
- [ADR 0047: Constants の enum を API の URL に合わせた名前空間に移す](0047-nest-constants-by-url.md)
- [非推奨のクラス名の対応表](../class-aliases.md)
- 実装とテストの出典コミット: `5aed6b4`、`998750f`
