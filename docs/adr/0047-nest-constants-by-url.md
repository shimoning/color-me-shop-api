# ADR 0047: Constants の enum を API の URL に合わせた名前空間に移す

- 状態: 採用
- 決定日: 2026-10-13

## 文脈

Entity は、API の URL に合わせて名前空間を分けている（`Entities\Product\Advertising`、`Entities\Sale` など）。Service も
同じ階層に揃っている。一方で enum は、29 個すべてが `Constants` の直下に並んでいた。`Constants\AdvertisingCondition` は
`Entities\Product\Advertising` だけで、`Constants\ShopState` は `Entities\Shop` だけで使うように、多くの enum は 1 つの
API に属していた。複数の API で使う enum は、`Prefecture`（顧客、配送、受注、ショップ）と `Sex`（顧客、受注）、
エラー応答の `ErrorCode`、並び順の `SortDirection`、基底の `FallbackEnum` だった。

enum のクラス名の多くは、属する API の名前を接頭辞に持っていた（`ProductDisplayState`、`DeliveryChargeType`、`ShopState`
など）。Entity については、[ADR 0029](0029-drop-parent-prefix-from-entity-names.md) で、所属する名前空間の主 Entity の名前を
接頭辞として付けず、何に属するかは名前空間で表すと決めている。

## 判断

- **1 つの API に属する enum は、それを使う Entity と同じ URL の名前空間に移す。** 利用者の指定による。
  出典: `c1184c6`。
  - 複数の Entity で使う enum は、それらに共通する最も深い名前空間に置く。商品の表示状態は商品、在庫、商品の広告で
    使うため `Constants\Product` に置く
  - 受注のメール送信の API で使う `MailType` は `Constants\Sale` に、OAuth の `AuthScope` と `AuthRedirectUri` は
    `Constants\OAuth` に置く
- **移した enum のクラス名は、ADR 0029 と同じ規則で、名前空間の主 Entity の名前と重なる接頭辞を除く。** Entity と enum で
  命名の規則を揃える。`Constants\Product\DisplayState`、`Constants\Delivery\ChargeType`、`Constants\Shop\State`、
  `Constants\Payment\Type` などになる。利用者の指定による。出典: `9c70e95`。
  - 主 Entity の名前と違う語で始まる名前は変えない。`Sale` の `MailType` と `PointState`、`Shop` の `TaxType`、`Payment` の
    `KouzaType` などが当たる。`OAuth` の `AuthScope` と `AuthRedirectUri` の `Auth` も、名前空間の `OAuth` とは別の語なので
    変えない
- **複数の API で使う enum と、基底の `FallbackEnum` は `Constants` の直下に残す。** `Prefecture`、`Sex`、`ErrorCode`、
  `SortDirection` が当たる。出典: `c1184c6`。
- 旧名は [ADR 0016](0016-unify-request-input-entity-names.md) と同じ仕組みで非推奨の別名として残し、次のメジャーな変更で
  削除する。別名は 0.31.0 の名前から新しい名前へ直接対応させる。利用者の指定による。出典: `c1184c6`、`9c70e95`。

## 代替案と却下理由

- **`Constants` の直下のままにする案**は、互換性への影響がない。しかし Entity と Service の階層と揃わず、どの API の値かが
  名前空間から分からない。
- **名前空間だけを移し、クラス名は変えない案**は、利用者が書き換える量が少なく、`DisplayState` のような同じ名前の enum が
  増えない。しかし `Constants\Product\ProductDisplayState` のように名前空間とクラス名で同じ語が重なり、ADR 0029 で
  Entity に決めた規則と揃わない。
- **`Prefecture` と `Sex` を `Constants\Customer` などに置く案**は、直下の enum が減る。しかし複数の API で使う値を
  1 つの API の下に置くと、ほかの API から使うときに属する場所が誤解される。

## 帰結

enum の名前空間から、どの API の値かが分かる。Entity、Service、enum の階層と命名の規則が揃う。

次の点が変わる。

- 旧名を参照していたコードは、旧名のままでも動作するが、非推奨になる
- `::class` や `get_class()` などで得るクラス名の文字列は新名になる（ADR 0029 と同じ）
- `DisplayState`（商品、カテゴリー、グループ、配送）や `Type`（ピックアップ、決済）のように、名前空間の違う同じ名前の enum が
  ある。1 つのファイルで並べて使うときは、`use ... as` で別名を付ける

## 関連

- [ADR 0000: アーキテクチャ上の意思決定を記録する](0000-record-architecture-decisions.md)
- [ADR 0016: 要求側入力 Entity の命名を統一し、旧名を非推奨の別名で残す](0016-unify-request-input-entity-names.md)
- [ADR 0029: Entity のクラス名に親の名前の接頭辞を付けない](0029-drop-parent-prefix-from-entity-names.md)
- [ADR 0043: 基底の型と配置を揃える](0043-align-base-types-and-placement.md)
- [非推奨のクラス名の対応表](../class-aliases.md)
- 実装とテストの出典コミット: `c1184c6`、`9c70e95`
