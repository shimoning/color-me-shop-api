# 契約プランによる API の制限

## この文書の位置づけと収集条件

この文書は、カラーミーショップの契約プランによって、使えない API と、使えるが制約が変わる API をまとめる。
公式 OpenAPI の記載と、テスト用ショップでの実測を分けて書く。実測の詳細は、各観測記録にある。

公式 OpenAPI は **2026-10-21（Asia/Tokyo）** に取得した [`open_api.json`](https://api.shop-pro.jp/v1/spec/open_api.json)
（41 パス）を使った。全文から「プラン」「プレミアム」「401200」を機械的に探し、ショップ情報の応答の
`contract_plan` と `domain_plan` の説明を除いたすべての記載を拾った。

テスト用ショップのショップ情報 GET の `contract_plan` は `unknown` で、契約プランは API からは分からない。
プレミアムプランのショップでは、どの API も実測していない。

## 使えない API

公式 OpenAPI が「カラーミーショップでご契約中のプランがプレミアムのアカウントでのみご利用いただけます。」と
説明している操作は、次の 2 つだけだった。

| API | ライブラリのメソッド | 使えるプラン | テスト用ショップでの実測 |
| --- | --- | --- | --- |
| `POST /v1/sales`（受注データの作成） | `Client::createSale()` | プレミアム | `401`（code `401200`「現在契約中のプランではご利用いただけません。」）。必須項目を欠いた body でも、body の検証より先にこの `401` が返った（[受注作成 API の実測記録](api-sale-create-observation.md)） |
| `POST /v1/products/{product_id}/images`（商品画像の登録） | `Client::createProductImage()` | プレミアム | `401`（code `401200`）（[商品応答構造の実測記録](api-product-structure.md#画像-api)） |

使えないプランでは、ライブラリは `401` の `Results\Errors` を返す。どちらも、成功時の応答の形は公式 OpenAPI に
基づいており、実 API では確かめていない。

### 商品画像の削除はプランの制限を受けない

`DELETE /v1/products/{product_id}/images/{position}`（`Client::deleteProductImage()`）には、公式 OpenAPI に
プランの記載がない。画像の登録が `401` になるテスト用ショップで、メイン画像とサブ画像の削除はどちらも `204` で
成功した（2026-10-21、[商品応答構造の実測記録](api-product-structure.md#2026-10-21-の追加観測画像の削除)）。
ただし、画像の登録が使えないプランでは、削除した画像を API で戻せない。

## 使えるが、プランによって制約が変わるもの

| 対象 | 制約 | ライブラリでの扱い |
| --- | --- | --- |
| 商品の作成・更新の `sales_price` と `members_price`（`ProductCreateInput`、`ProductUpdateInput`） | フリープランでは最低価格が 100 円 | 検証しない。API の検証に委ねる |
| バリエーションの更新の `option_price` と `option_members_price`（`VariantUpdateInput`） | フリープランでは最低価格が 100 円 | 検証しない。API の検証に委ねる |
| テンプレートのページの `html`（`/v1/templates/{template_id}/pages/{page_type}`） | プラチナとプレミアムは最大 120,000 文字、ほかは最大 65,530 文字 | テンプレートの API は実装していない |

この表は公式 OpenAPI の記載だけに基づき、実 API では確かめていない。

## 確かめていないこと

- プレミアムプランのショップで、受注データの作成と商品画像の登録が成功すること
- フリープランのショップで、最低価格を下回る価格を送ったときの応答
- テスト用ショップの実際の契約プラン
