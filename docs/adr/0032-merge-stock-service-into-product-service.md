# ADR 0032: 在庫の Service を Services\Product に統合する

- 状態: 採用
- 決定日: 2026-10-06

## 文脈

在庫情報 API（`GET /v1/stocks`）は、`Services\Stock::page()` が扱っていた。
[ADR 0030](0030-place-entities-by-api-path.md) で、在庫の Entity を例外として `Entities\Product\Stock` に移した。
グループ・カテゴリー・商品広告は、Entity を `Product` の下に置いたうえで、Service も `Services\Product` が
扱っている。在庫だけは、Entity が `Product` の下にあるのに、Service は独立していた。

## 判断

- **在庫一覧の取得を `Services\Product::stocks()` に統合する。** グループ・カテゴリー・商品広告と同じく、
  Entity を `Product` の下に置いたものは Service も `Services\Product` で扱う。メソッド名は、商品一覧の
  `products()` と並ぶよう `stocks()` とする。出典: `5e03b5c`。
- `Client::getStocks()` の公開シグネチャと挙動は変えず、内部で `Services\Product::stocks()` を呼ぶ。
  出典: `5e03b5c`。
- **`Services\Stock` は非推奨として残し、次のメジャーな変更で削除する。** `page()` は
  `Services\Product::stocks()` に委譲する。メソッド名が違うため、クラスの別名（ADR 0016）では互換を
  保てない。出典: `5e03b5c`。

## 代替案と却下理由

- **`Services\Stock` を独立したまま残す案**は、変更がない。しかし Entity を `Product` の下に置いたものの中で、
  在庫だけが別の Service になる。
- **`Services\Stock` をこのリリースで削除する案**は、コードベースが簡潔になる。しかし `Services\Stock` を
  直接生成しているコードが壊れる。利用者の指定により、非推奨として残す。
- **メソッド名を `Services\Stock` と同じ `page()` にする案**は、移行が置換だけで済む。しかし
  `Services\Product` の中で商品一覧の `products()` と区別できない。

## 帰結

`Product` の下に置いた Entity の API は、すべて `Services\Product` から扱えるようになる。

次の点が変わる。

- `Services\Stock` を直接使っていたコードは動作するが、非推奨になる。`Services\Product::stocks()` に
  書き換える必要がある
- `Services\Stock` の削除まで、在庫一覧の取得経路が 2 つある

## 関連

- [ADR 0000: アーキテクチャ上の意思決定を記録する](0000-record-architecture-decisions.md)
- [ADR 0030: Entity の名前空間を API の URL に沿わせる](0030-place-entities-by-api-path.md)
- 実装とテストの出典コミット: `5e03b5c`
