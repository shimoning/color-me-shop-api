# OAuth スコープと公式 OpenAPI の突合記録

## 結論

ライブラリが実装している 41 操作を公式 OpenAPI の `security` と突き合わせた。35 操作には具体的な
スコープが宣言されており、各 Service と `Client` の PHPDoc に記載した。残る 6 操作は OAuth2 認証を
要求しながらスコープの宣言が空で、必要なスコープを公式定義から判断できない。これらには PHPDoc へ
スコープを記載していない。

商品画像の作成だけは 2 つのスコープを同時に要求する。

## 収集条件

公式 OpenAPI は 2026-09-26 に取得した。出典:
[https://api.shop-pro.jp/v1/spec/open_api.json](https://api.shop-pro.jp/v1/spec/open_api.json)。

`securitySchemes` に定義されているのは `OAuth2`（authorizationCode フロー）だけで、グローバルの
`security` はない。各操作の `security` 配列は、要素間が OR、1 要素内の複数スコープが AND を表す。

スコープ名の一覧は `Constants\AuthScope` にあり、公式 OpenAPI の `info.description` の scope 表を
出典とする。`read_products` / `write_products` / `read_sales` / `write_sales` /
`read_shop_coupons` / `write_shop_coupons` / `read_templates` / `write_templates` /
`read_analytics` の 9 つである。

## スコープが宣言されている操作

| 必要な scope | 件数 | 対象 |
| --- | ---: | --- |
| `read_products` | 6 | 商品一覧・単体、バリエーション一覧・単体、画像一覧、商品広告一覧 |
| `write_products` | 17 | 商品の作成・更新、バリエーション更新、オプションとオプション値の作成・削除、ピックアップの作成・更新・削除、画像削除、グループの作成・更新、カテゴリーの作成・更新、小カテゴリーの作成・更新 |
| `read_sales` | 5 | 受注一覧・単体・集計、顧客一覧・単体 |
| `write_sales` | 6 | 受注の更新・キャンセル・メール送信、顧客の追加・更新、ショップポイントの増減 |
| `read_products` + `write_products` | 1 | 商品画像の作成 |

### 商品画像の作成だけが 2 スコープを要求する

`POST /v1/products/{product_id}/images` の `security` は `[{OAuth2: [read_products, write_products]}]`
であり、1 要素内に 2 つのスコープを持つため両方が必要である。同じ画像リソースでも
`DELETE /v1/products/{product_id}/images/{position}` は `write_products` だけを要求する。
書き込み操作が読み取りスコープも要求する理由は公式ドキュメントに説明がない。

ライブラリではプラン制限により画像作成の成功系を観測できていないため
（[ADR 0014](adr/0014-model-product-write-api.md)）、この要求が実 API でも同じかは未検証である。

## スコープの宣言が空の操作

次の 6 操作は `security` に `[{OAuth2: []}]` を持つ。OAuth2 認証は要求するが、必要なスコープの
宣言がない。アクセストークン自体は必要である。

| 操作 | Service | 推測されるスコープ | 根拠と留保 |
| --- | --- | --- | --- |
| `GET /v1/shop` | `Shop::get()` | 該当なしの可能性 | 公式の 9 scope にショップ情報に対応するものがない |
| `GET /v1/payments` | `Payment::all()` | 該当なしの可能性 | 同上。受注処理に使う参照情報だが対応する scope がない |
| `GET /v1/deliveries` | `Delivery::all()` | 該当なしの可能性 | 同上 |
| `GET /v1/groups` | `Product::groups()` | `read_products` | 同じリソースの `POST /v1/groups` が `write_products` を要求する |
| `GET /v1/groups/{id}` | `Product::group()` | `read_products` | 同上。`PUT /v1/groups/{id}` が `write_products` を要求する |
| `GET /v1/categories` | `Product::categories()` | `read_products` | 同じリソースの `POST /v1/categories` が `write_products` を要求する |

推測欄はいずれも**未検証**である。確かめるにはスコープを限定したアクセストークンを個別に発行して
各操作を呼ぶ必要があり、OAuth の認可フローをスコープごとにやり直すことになる。現時点では実施して
いない。

商品グループとカテゴリーは、書き込み側にスコープが宣言されているのに読み取り側だけ空である。同じ
リソースで読み書きの宣言が非対称であるため、記載漏れの可能性が高いと考えている。一方、ショップ・
決済・配送は公式の scope 表に対応する項目がなく、トークンさえあれば取得できる設計とも解釈できる。
どちらの解釈も確定できないため、PHPDoc には推測を書かず、この文書に留める。

## `security` キー自体がない操作

`POST /v1/sales`（受注の作成）は `security` キーを持たない。グローバルの `security` もないため、
OpenAPI の意味論では認証不要という宣言になる。他のすべての受注 API が `read_sales` または
`write_sales` を要求することと整合せず、記載漏れと考えられる。この操作はライブラリ未実装であり、
実装する際に実 API で確認する必要がある。

## ライブラリが使っていないスコープ

`read_shop_coupons` / `write_shop_coupons` / `read_templates` / `write_templates` は、対応する API
（ショップクーポン、テンプレート）をライブラリが実装していないため使われない。`read_analytics` は
公式 OpenAPI の `paths` にこれを要求する操作がなく、対応する API が仕様に現れない。

これらは `Constants\AuthScope` には定義されており、利用者が `Values\Scopes` へ渡して認可 URL を
組み立てることはできる。

## 関連

- [ADR 0014: 商品書き込み API の入力と空レスポンスを表現する](adr/0014-model-product-write-api.md)
- [ADR 0015: 顧客書き込み API の入力を作成と更新で分ける](adr/0015-model-customer-write-api.md)
- [enum と公式 OpenAPI の突合記録](enum-openapi-audit.md)
