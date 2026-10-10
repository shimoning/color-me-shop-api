# ADR 0043: 基底の型と配置を揃える

- 状態: 採用
- 決定日: 2026-10-10

## 文脈

`src/` 全体の型の種類と配置を見直したところ、次の不揃いがあった。

- Service の基底 `Services\Service` は `abstract` だが、Entity の基底 `Entities\Entity` は具象クラスだった。
  `Entity` を直接生成する箇所は src にもテストにもなかった。
- 入力 Entity はどれも `Entity` を直接継承するが、受注のお届け先の更新入力 `Sale\DeliveryUpdateInput` だけは
  応答の `Sale\Delivery` を継承していた。そのため、応答の getter を持ち、`Sale\Delivery` の `instanceof` を満たしていた。
- `Entities` には API のデータを表す Entity を置くが、OAuth のアプリ設定 `Entities\OAuth\Options` は `Entity` を
  継承しない設定の入れ物だった。使うのは `Services\OAuth` だけである。
- Service はどれも `Services\Service` を継承するが、`Services\OAuth` だけは継承していなかった。`Service` は
  コンストラクタでアクセストークンを必須にしている。`Services\OAuth` は、そのアクセストークンを取得するための
  Service で、アクセストークンの代わりに `Options` を受け取る。

## 判断

- **`Entities\Entity` を `abstract` にする。** `Services\Service` と揃える。出典: `1c7fbd8`。
- **`Sale\DeliveryUpdateInput` は `Entity` を直接継承する。** ほかの入力 Entity と揃える。持つ項目は、公式 OpenAPI の
  受注の更新 (`PUT /v1/sales/{sale_id}`) の `sale.sale_deliveries[]` にある 28 項目とし、送信内容は変えない。
  setter は今のまま残し、setter のない `account_id` / `sale_id` / `delivery_id` / `detail_ids` はコンストラクタで渡す。
  応答の getter はなくなる。利用者の指定による。出典: `1c7fbd8`。
- **`Entities\OAuth\Options` を `Services\OAuth\Options` に移す。** 使う Service の下に置き、`Entities` には Entity だけを
  置く。`Services\Product` と `Services\Product\Variant` と同じく、クラスと同じ名前の名前空間を作る。クラス名は変えない。
  旧名は [ADR 0016](0016-unify-request-input-entity-names.md) と同じ仕組みで非推奨の別名として残し、次のメジャーな
  変更で削除する。利用者の指定による。出典: `1c7fbd8`。
- **`Services\OAuth` は `Services\Service` を継承しない。** `Service` はアクセストークンを前提にした基底で、
  アクセストークンを取得する側の `Services\OAuth` には当てはまらない。コードは変えず、例外であることをこの ADR に
  記録する。利用者の指定による。

## 代替案と却下理由

- **`Sale\DeliveryUpdateInput` を、setter のある 23 項目と `id` だけにする案**は、応答由来の項目を入力から外せる。
  しかし公式 OpenAPI の要求には `account_id` などの 4 項目も含まれ、公式 API に準拠する方針
  （[ADR 0035](0035-follow-api-without-client-side-features.md)）に反するうえ、送信内容が変わる破壊的変更になる。
- **`Sale\DeliveryUpdateInput` をコンストラクタ方式に揃え、setter を非推奨にする案**は、ほかの入力と組み立て方まで
  揃う。しかし `Sale\SaleUpdateInput` の setter も含めて利用者のコードへの影響が大きく、今回は継承元だけを揃える。
- **`Options` を `Communicator\OAuthOptions` に移す案**は、`Communicator\RequestOptions` と並ぶ。しかしクラス名も
  変わり、使う Service との結び付きも見えにくくなる。
- **アクセストークンを持たない共通の基底を作り、`Service` と `Services\OAuth` の両方に継承させる案**は、Service の
  継承関係が揃う。しかし共有できるのは HTTP クライアントの保持くらいで、基底を 1 段増やすほどの重複がない。

## 帰結

基底の型の `abstract` が揃い、入力 Entity の継承元と、`Entities` に置くものが揃う。

次の点が変わる。

- `new Entity(...)` で `Entity` を直接生成していたコードは動かなくなる
- `Sale\DeliveryUpdateInput` は `Sale\Delivery` の `instanceof` を満たさなくなり、`getName()` などの応答の getter を
  持たなくなる。送信内容は変わらない
- `Entities\OAuth\Options` を参照していたコードは、旧名のままでも動作するが、非推奨になる

## 関連

- [ADR 0000: アーキテクチャ上の意思決定を記録する](0000-record-architecture-decisions.md)
- [ADR 0016: 要求側入力 Entity の命名を統一し、旧名を非推奨の別名で残す](0016-unify-request-input-entity-names.md)
- [ADR 0035: 公式 API にない機能をライブラリで作らない](0035-follow-api-without-client-side-features.md)
- [ADR 0042: RequestEntity を Entities\RequestEntity に移す](0042-move-request-entity-to-entities.md)
- [非推奨のクラス名の対応表](../class-aliases.md)
- 実装とテストの出典コミット: `1c7fbd8`
