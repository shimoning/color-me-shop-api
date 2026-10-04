# 実装メモ

この文書は、PHPDoc から外した実装上の注意と、その理由を残す。ADR に記録するほどの設計判断ではないが、
コードを読むだけでは意図が分かりにくいものを扱う。API の実測は各観測記録に、設計判断は `docs/adr/` に
書く（[CONTRIBUTING.md](../CONTRIBUTING.md) の「PHPDoc」の節）。

## `RequestEntity` の明示フィールドの追跡と、旧形式の直列化データ

対象: `src/Contracts/RequestEntity.php`、`src/Entities/Entity.php`

要求側の Entity は、コンストラクタ配列や setter で明示したフィールドだけを送信する。そのために、明示した
フィールドを Entity の内部で追跡している（[ADR 0014](adr/0014-model-product-write-api.md)）。

この追跡を加える前に `serialize()` された `RequestEntity` は、追跡の情報を持たない。復元すると追跡の
情報は未初期化のままになるため、次のように扱う。

- `toArrayRecursive()` は、追跡を加える前の挙動（`null` を省略し、初期化済みの非 `null` のフィールドを
  直列化する）にフォールバックする
- 復元後に setter を使った場合は、その時点で初期化済みかつ非 `null` のフィールドを追跡の種にしてから、
  setter で設定したフィールドを追加する。setter を呼ぶ前の値も保たれる

## `Errors` の構築

対象: `src/Communicator/Errors.php`

### 要素ごとに例外を捕捉する

`errors[]` の各要素は、フィールド単位で検証してから `Entities\Error` に組み立てるため、通常は構築時の
例外に到達しない。それでも、将来のフィールドの追加や Entity の変換経路の変更で例外が発生した場合に
`Errors` 自体は返せるよう、要素ごとに `Throwable` を捕捉する。想定外の例外に対する最後の防御であり、
構築に失敗した要素だけを読み飛ばす。

### 応答のコンテナの形を、再パースで判定する

`json_decode()` の連想配列化では、JSON の `{}` と `[]` がどちらも PHP の `[]` になり、数値のような
object のキーも int のキーに変わる。そのため、`errors` がオブジェクトか配列かを、キーではなく実際の
JSON の形で判定するために、`stdClass` を使って本文を再パースしている。

`Response` が持つ連想配列のツリーに加えて再パースの結果も組み立てるため、構築中のメモリと処理時間は
応答の大きさに比例して増える。実 API のエラーの件数は小さいという前提で、形の判定の正確さを優先して
いる。異常に大きなエラー応答では、一時的なメモリの増加と遅延が起こりうる。

### `code` を文字列に正規化する

API の契約上 `code` は integer だが、`Entities\Error::getCode()` は文字列を返す既存の契約を保っている。
そのため `Errors` は、`Error` を組み立てる前に integer の `code` を文字列に正規化し、integer でも文字列でも
ない値は欠損として扱う。

`PHP_INT_MAX` を超える JSON の integer は `json_decode()` で float になり、欠損として扱われる既知の制約が
ある。`Response` 全体に `JSON_BIGINT_AS_STRING` を適用すると、他の Entity の int のフィールドまで文字列に
なってしまうため、実 API の `code` が 6 桁である現状では、この局所的な欠損の扱いを選んでいる。
