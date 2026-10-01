# ADR 0022: 受注更新の受注 ID をメソッド引数で受け取る

- 状態: 採用
- 決定日: 2026-10-01

## 文脈

0.19.0 までの受注更新は、受注 ID を入力クラス `Entities\Sales\SaleUpdateInput` に持たせていた。

```php
$client->updateSale($updater); // $updater->getId() で path を組み立てる
```

`Services\Sales::update()` は `SaleUpdateInput::getId()` で path を組み立て、同じ `id` を body の
`sale` にも含めて送っていた。

```json
{"sale": {"id": 1001, "paid": true}}
```

これには 2 つの問題があった。

1. **body に公式仕様にない `id` を送っていた。** 公式 OpenAPI の `PUT /v1/sales/{sale_id}` で `sale` に
   定義されているのは `paid` / `point_state` / `sale_deliveries` の 3 つで、受注 ID は path parameter
   である。`tests/Entities/ApiFieldNameTest.php` は `SaleUpdateInput::$id` を、公式フィールドにない
   内部フィールドの唯一の例外として登録していた（Issue #53）。
2. **他の更新 API と形が揃っていなかった。** 顧客・商品・グループ・カテゴリー・バリエーションの更新は、
   いずれも ID をメソッド引数で受け取り、入力クラスに ID を持たせていない。

```php
$client->updateProduct($productId, $input);
$client->updateCustomer($customerId, $input);
```

body から `id` を外してよいかを確かめるため、2026-10-01 に実測した。詳細は
[受注更新の body に含めた `id` の実測記録](../api-sale-update-id-observation.md)にある。出典: `c56b3cd`。

- body の `id` の有無で、実 API の挙動は変わらなかった
- body に存在しない ID を入れても 200 で、path の受注として処理された。**実 API は body の `id` を
  見ておらず、更新対象は path だけで決まる**

## 判断

- `Services\Sales::update()` と `Client::updateSale()` は、受注 ID を第 1 引数で受け取る。
  `update(int|string $id, SaleUpdateInput $input, ?string $accessToken = null)` とし、他の Service の
  `update()` と同じ形にする。path は引数の ID から組み立てる。出典: `de62e14`。
- **`SaleUpdateInput` から `id` プロパティと `getId()` を削除する。** 入力クラスは要求 body に対応する
  フィールドだけを持つ。body に `id` は含まれなくなる。実 API が body の `id` を見ないことを確かめた。
  出典: `c56b3cd`、`de62e14`。
- `SaleUpdateInput::convert(Sale $sale)` は `id` を設定しない。受注 ID は取得した受注の `getId()` などから
  メソッド引数で渡す。出典: `de62e14`。
- **旧い呼び方 `update($input)` への移行措置は設けない。** 旧い呼び方は `TypeError` になる。出典: `de62e14`。
- `sale_deliveries[]` の要素の `id`（`SaleDeliveryUpdateInput::$id`）は、公式の要求スキーマに定義されて
  いるため変えない。出典: `de62e14`。
- `ApiFieldNameTest` の内部フィールド例外を削除する。入力クラスのフィールドがすべて公式の要求スキーマに
  含まれる状態にする。出典: `de62e14`。
- README の受注更新の例を新しいシグネチャに直し、0.19.0 までの呼び方からの変更点を記す。出典: `b3333d4`。

## 代替案と却下理由

- **body から `id` を外すだけで、`SaleUpdateInput` が `id` を持ち path に使う設計は残す案**は、
  呼び出し側を変えずに済み、破壊的変更にならない。Issue #53 も当初はこの形を想定していた。しかし受注
  更新だけが ID を入力クラスに持つ不揃いが残り、入力クラスに「送らないフィールド」という特例を
  `RequestEntity` の仕組みへ持ち込むことになる。利用者と協議のうえ、他の更新 API と形を揃えることを
  優先した。
- **`SaleUpdateInput::$id` と `getId()` を非推奨として残す案**は、`getId()` を呼んでいる利用者のコードを
  壊さない。しかし「宣言はあるが送らない」特例を `RequestEntity` に足す必要があり、次のメジャーな変更で
  削除するまで複雑さが残る。利用者と協議のうえ採用しない。
- **第 1 引数に `SaleUpdateInput` が来たら従来どおり `getId()` を使い、非推奨の警告を出す移行措置**は、
  既存の呼び出しをすぐには壊さない。しかし引数の型が `int|string|SaleUpdateInput` のような合併型になり、
  他の Service と形が揃うのは移行措置を消すときまで先送りになる。`id` を削除する判断とも両立しない。
  `0.x` であり、破壊的変更もマイナーバージョンの更新で行う規約（CONTRIBUTING.md）のため、利用者と協議の
  うえ採用しない。

## 帰結

受注更新が、他の更新 API と同じ「ID は引数、入力クラスは body」の形になる。入力クラスのフィールドが
すべて公式の要求スキーマに含まれ、`ApiFieldNameTest` の例外がなくなる。

次の点が変わる。破壊的変更のため、次のマイナーバージョン（0.20.0）で行う。

- `Services\Sales::update()` と `Client::updateSale()` のシグネチャが変わる。旧い呼び方
  `updateSale($updater)` は `TypeError` になる。`updateSale($saleId, $updater)` に書き換える必要がある
- `SaleUpdateInput::getId()` がなくなる。コンストラクタ配列に `id` を渡しても、宣言のないキーとして
  無視され、body にも含まれない
- 0.19.0 以前に `serialize()` した `SaleUpdateInput`（旧名 `SaleUpdater` を含む）を `unserialize()` すると、
  旧データの `id` が宣言のない動的プロパティとして復元され、PHP 8.2 以降では非推奨警告が出る。
  [ADR 0021](0021-drop-state-from-token-error-response.md) の `ErrorResponse::$state` と同じ扱いとし、
  許容する。出典: `de62e14`。

実 API の観測は、値を変えない更新に限られる。body の `id` が別の受注を指したまま値を変更する場合は
観測していないが、本 ADR の後はライブラリが body に `id` を送らないため、この問題は生じない。

## 関連

- [ADR 0000: アーキテクチャ上の意思決定を記録する](0000-record-architecture-decisions.md)
- [ADR 0016: 要求側入力 Entity の命名を統一し、旧名を非推奨の別名で残す](0016-unify-request-input-entity-names.md)
- [ADR 0021: トークンエラー応答から state を外す](0021-drop-state-from-token-error-response.md)
- [受注更新の body に含めた `id` の実測記録](../api-sale-update-id-observation.md)（出典コミット: `c56b3cd`）
- Issue #53
- 実装とテストの出典コミット: `de62e14`
- README の出典コミット: `b3333d4`
