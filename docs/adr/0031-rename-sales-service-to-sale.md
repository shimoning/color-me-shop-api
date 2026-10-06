# ADR 0031: 受注の Service を Services\Sale に改名する

- 状態: 採用
- 決定日: 2026-10-06

## 文脈

`Services` のクラスは `Customer` / `Delivery` / `Gift` / `OAuth` / `Payment` / `Product` / `Shop` / `Stock` と単数形で、
受注の `Services\Sales` だけが複数形だった。

[ADR 0030](0030-place-entities-by-api-path.md) で Entity の名前空間 `Sales` を `Sale` に改めた。このとき、
影響範囲を分けるために `Services` のクラス名は変えなかった。その結果、`Entities\Sale\Sale` を返す Service が
`Services\Sales` という組み合わせになっていた。

`Services` のクラスは、利用者が直接生成できる公開クラスである。通常の入口は `Client` で、`Client` のメソッド名
（`getSales()` など）は Service のクラス名に依存しない。

## 判断

- **`Services\Sales` を `Services\Sale` に改名する。** ほかの Service と同じ単数形にし、Entity の名前空間
  `Entities\Sale` と揃える。出典: `be4f286`。
- `Client` のメソッド名は変えない。出典: `be4f286`。
- 旧名 `Services\Sales` は、Entity の旧名と同じ仕組み（ADR 0016）で非推奨の別名として残し、次のメジャーな
  変更で削除する。別名の仕組みと対応表は Entity に限らず、改名したクラス全般を扱うものとする。出典: `be4f286`。
- Service は HTTP クライアントなどの実行時の依存を持ち、直列化して使うことを想定していないため、旧名での
  `unserialize()` は別名のテストの対象から外す。旧名での生成、`instanceof` と型宣言は確かめる。出典: `be4f286`。
- Service の分け方は変えない。`Services\Stock` が返す在庫の Entity は ADR 0030 で `Entities\Product\Stock` に
  移ったが、Service の所属は今回扱わない。出典: `be4f286`。

## 代替案と却下理由

- **`Services\Sales` のままにする案**は、互換性の影響がない。「受注・売上」の領域名として複数形も英語として
  自然である。しかし Service の中で `Sales` だけが命名から外れ、Entity の名前空間とも揃わない。
- **Service の分け方も Entity の配置に合わせる案**（在庫を `Services\Product` に寄せるなど）は、Entity と
  Service の対応が揃う。しかし `Client` の内部構成の変更を含み、改名とは別の判断であるため、今回は扱わない。

## 帰結

Service のクラス名がすべて単数形になり、受注の Entity の名前空間と揃う。

次の点が変わる。

- `Services\Sales` を直接生成していたコードは、旧名のままでも動作するが、非推奨になる
- `get_class()` の結果は新名になる。クラス名の文字列に依存するコードは互換でない（ADR 0029 と同じ）
- `Services\Sale` と `Entities\Sale\Sale` を両方使うファイルでは、`use ... as` が必要になる

## 関連

- [ADR 0000: アーキテクチャ上の意思決定を記録する](0000-record-architecture-decisions.md)
- [ADR 0016: 要求側入力 Entity の命名を統一し、旧名を非推奨の別名で残す](0016-unify-request-input-entity-names.md)
- [ADR 0030: Entity の名前空間を API の URL に沿わせる](0030-place-entities-by-api-path.md)
- [非推奨のクラス名の対応表](../class-aliases.md)
- 実装とテストの出典コミット: `be4f286`
