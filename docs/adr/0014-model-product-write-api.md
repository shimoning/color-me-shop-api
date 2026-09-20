# ADR 0014: 商品書き込み API の入力と空レスポンスを表現する

- 状態: 採用
- 決定日: 2026-09-20

## 文脈

現行ライブラリの商品 API は読み取り系だけを公開している。書き込み系では JSON だけでなく画像の
`multipart/form-data` があり、成功ステータスも
`200`、`201`、`204` に分かれる。現行の `Communicator\Request` が公開する HTTP メソッドは
`get()`、`post()`、`put()` で、multipart 送信と `delete()` はない。出典: `10cf216`。

2026-09-20 に取得した[公式 OpenAPI](https://api.shop-pro.jp/v1/spec/open_api.json)で、
`/v1/products` 配下の POST、PUT、DELETE 12操作を確認した。要求と成功応答の形は次のとおりである。
要求ボディ自体は `PUT /v1/products/{product_id}` だけ `required` 指定がなく、他のボディあり操作は
`required: true` である。表中の「required なし」は、その object のプロパティを指定する OpenAPI の
`required` 配列がないことを表す。

| 操作 | Content-Type と要求ボディ | 成功応答 |
| --- | --- | --- |
| `POST /v1/products` | `application/json`。`product{name, price, category_id_big, cost, sales_price, members_price, model_number, expl, simple_expl, smartphone_expl, display_state, stock_managed, tax_reduced}`。`product` と子プロパティは required なし | `200`、JSON の `product` |
| `PUT /v1/products/{product_id}` | `application/json`。`product{name, price, category_id_big, category_id_small, cost, sales_price, members_price, model_number, expl, simple_expl, smartphone_expl, display_state, stock_managed, stocks, group_ids, variants, tax_reduced}`。要求ボディ、`product`、子プロパティはいずれも required なし | `200`、JSON の `product` |
| `PUT /v1/products/{product_id}/variants/{id}` | `application/json`。`variant{stocks, few_num, model_number, weight, option_price, option_members_price, option_market_price, option_cost}`。`variant` と子プロパティは required なし | `200`、JSON の `variant` |
| `POST /v1/products/{product_id}/options` | `application/json`。`option{name, values[]{name}}`。`option`、`name`、`values`、各値の `name` が required | `201`、JSON の `option` |
| `DELETE /v1/products/{product_id}/options/{id}` | ボディなし | `204`、ボディなし |
| `POST /v1/products/{product_id}/options/{option_id}/values` | `application/json`。`option_value{name}`。`option_value` と `name` が required | `201`、JSON の `option_value` |
| `DELETE /v1/products/{product_id}/options/{option_id}/values/{id}` | ボディなし | `204`、ボディなし |
| `POST /v1/products/{product_id}/pickups` | `application/json`。`pickup_type` と `order_num` をトップレベルに持ち、required なし | `200`、JSON の `pickup` |
| `PUT /v1/products/{product_id}/pickups` | `application/json`。`pickup_type` と `order_num` をトップレベルに持ち、required なし | `200`、JSON の `pickup` |
| `DELETE /v1/products/{product_id}/pickups/{pickup_type}` | ボディなし | `200`、JSON の削除済み `pickup` |
| `POST /v1/products/{product_id}/images` | `multipart/form-data`。binary の `image` と integer の `position` が required | `201`、JSON の `product_image` |
| `DELETE /v1/products/{product_id}/images/{position}` | ボディなし | `204`、ボディなし |

商品作成と更新は、いずれも `product` object を包む JSON であり、`product` 自体にもその子にも
required 指定がない。両者のプロパティ集合は同一ではなく、更新側には `category_id_small`、`stocks`、
`group_ids`、`variants` が加わる。更新の `stocks` は整数または `increment` object、`variants[].stocks`
も同じ2形状である。バリエーション単体更新では `stocks`、`few_num`、`model_number`、`weight`、
4種の価格が nullable で、一部は明示的な `null` で未設定へ戻すと説明されている。

画像作成は JPEG、PNG、GIF のファイルを受け取り、最大5MB、`position` は0〜49である。成功は
`201` である一方、OpenAPI は失敗として `422` に加えて `429` と `503` も定義している。

`204` の3操作には成功時の JSON がないため、応答 Entity を構築できない。一方、既存の
`Communicator\Errors` は元の `Communicator\Response` を保持し、利用者は成功型との union を
`instanceof Errors` で分岐できる。出典: `a6688cf`。成功時に単なる `true` を返すと、HTTP
ステータスやヘッダなどを後から参照する経路がなくなる。

[商品 API 応答構造の実測記録](../api-product-structure.md)は GET の観測だけを扱い、書き込み系へ
一般化しないことを明示している。同記録では OpenAPI にない `unlisted: boolean` を商品応答で
観測した。出典: `fa4bfbb`。公式 OpenAPI には商品そのものを削除する操作がなく、対象12操作の
DELETE はオプション、オプション値、ピックアップ、画像だけである。

## 判断

- 商品作成と更新の入力は、`Shimoning\ColorMeShopApi\Entities\Product\RequestEntity` という
  1つの入力 Entity で共用する。作成と更新はどちらも `product` object で、両者に required 指定が
  なく、作成専用型と更新専用型の必須性を型で区別できない。両操作のプロパティの和集合を表し、
  未設定フィールドは送信しない。これは既存の `Sales\SaleUpdater` と要求側 Entity の慣習に従う。
  出典: 公式 OpenAPI（2026-09-20 取得）、`0c7b73d`。
- バリエーション、オプション、オプション値、ピックアップにも、それぞれの要求形状を表す入力
  Entity を設ける。いずれも `Contracts\RequestEntity` を実装し、入力に使う enum は未知値の
  フォールバックを適用せず厳格に検証する。これは [ADR 0013](0013-expand-opt-in-enum-fallback.md) の
  要求側契約を維持する判断である。出典: `0c7b73d`。
- 入力 Entity は未設定と明示的な `null` を区別する。未設定フィールドは部分更新のため送信せず、
  OpenAPI が nullable とするフィールドへ利用者が明示した `null` は送信する。応答の欠損や実測に
  基づく nullable 化を定めた [ADR 0012](0012-allow-nullability-from-api-observations.md) を要求側へ
  一般化せず、要求側はその操作の OpenAPI に従う。出典: 公式 OpenAPI（2026-09-20 取得）、
  `0c7b73d`、`f56c22e`。
- 入力 Entity の取り込みと直列化は、通常のインスタンスプロパティだけを対象とする
  [ADR 0006](0006-limit-entity-property-kinds.md) の契約に従う。static や virtual property を要求へ
  混入させない。出典: `326f1ac8`、`0c7b73d`。
- `Communicator\Request` に DELETE 送信を追加する。商品書き込み系で使う3つの `204` DELETE と、
  削除済み `pickup` を返す `200` DELETE の双方を、同じ成功判定と既存の `Errors` 経路で扱う。
  出典: 公式 OpenAPI（2026-09-20 取得）、`10cf216`、`a6688cf`。
- `Communicator\Request` に `multipart/form-data` 送信を追加する。商品画像の入力はファイルパス
  または読み取り可能なストリームと `position` とし、JSON へ変換しない。画像作成の `422`、`429`、
  `503` を含む失敗応答は、他の API と同じ `Errors` 経路で返す。出典: 公式 OpenAPI
  （2026-09-20 取得）、`10cf216`、`a6688cf`。
- `204 No Content` の成功は `Shimoning\ColorMeShopApi\Communicator\NoContent` で表す。
  `NoContent` は API ボディ由来のフィールドを持たない値オブジェクトで、`Entity` は継承しない。
  `Errors` と同様に元の `Communicator\Response` を保持し、ステータスやヘッダを参照できるようにする。
  利用者は既存慣習どおり `instanceof Errors` で失敗を分岐し、それ以外を成功型として扱う。
  出典: `a6688cf`。
- 商品自体の削除 API は提供しない。公式 API に該当操作がないため、実 API の検証で作成した商品は
  ショップに残る。検証用商品は `display_state` または `unlisted` により非表示にする運用とするが、
  これは削除不能による検証環境の運用であり、ライブラリの仕様にはしない。出典: 公式 OpenAPI
  （2026-09-20 取得）、`fa4bfbb`。

## 代替案と却下理由

- `204` の成功を `true`、失敗を `Errors` とする案は、現時点の成否だけなら簡潔である。しかし
  成功時のステータスやヘッダを取得できず、将来成功結果へ情報を追加すると公開戻り値を再変更する
  必要があるため採用しない。元の `Response` を保持する `NoContent` なら拡張余地を残せる。
  出典: `a6688cf`。
- 商品作成用と商品更新用に別々の入力 Entity を設ける案は、OpenAPI 上の両 `product` object に
  required 指定がなく、型で作成・更新の必須項目差を表せない。重複する多数のプロパティと検証規則を
  二重管理するだけになるため採用しない。出典: 公式 OpenAPI（2026-09-20 取得）、`0c7b73d`。
- 商品画像を base64 文字列にして JSON 送信する案は、OpenAPI が binary の `image` と
  `position` を持つ `multipart/form-data` を要求しており、API 契約と一致しないため採用しない。
  出典: 公式 OpenAPI（2026-09-20 取得）、`10cf216`。

## 帰結

商品書き込み系は、JSON の入力 Entity、multipart の画像入力、ボディありの成功 Entity、
`NoContent`、`Errors` という応答形状ごとの型で表現できる。12操作の成功ステータスとボディの有無を
保持し、`204` でも元の HTTP 応答へアクセスできる。

商品作成と更新で1つの入力 Entity を共用するため、利用者は操作ごとの有効フィールドを公式 API 契約に
従って選ぶ必要がある。未設定と明示的な `null` は異なる要求として扱われる。要求側 enum は、応答側の
未知値フォールバックに影響されず厳格なままである。

商品自体を削除できないため、実 API の検証で作成したデータは残る。非表示化は検証環境のデータ運用で
あり、商品 Service が削除を提供することや、ライブラリが検証データを自動管理することは意味しない。

## 関連

- [ADR 0000: アーキテクチャ上の意思決定を記録する](0000-record-architecture-decisions.md)
- [ADR 0006: Entity のプロパティ解決を通常のインスタンスプロパティに統一する](0006-limit-entity-property-kinds.md)
- [ADR 0012: 実 API の観測に基づき Entity の null 許容を判断する](0012-allow-nullability-from-api-observations.md)
- [ADR 0013: 応答に使う enum のフォールバック対象を拡張する](0013-expand-opt-in-enum-fallback.md)
- [商品 API 応答構造の実測記録](../api-product-structure.md)（出典コミット: `fa4bfbb`）
- [公式 OpenAPI](https://api.shop-pro.jp/v1/spec/open_api.json)（2026-09-20 取得）
- `Errors` が元の `Response` を保持する契約の出典コミット: `a6688cf`
- `RequestEntity` と `Sales\SaleUpdater` の要求側契約の出典コミット: `0c7b73d`
- `Communicator\Request` の現行 HTTP メソッドの出典コミット: `10cf216`
