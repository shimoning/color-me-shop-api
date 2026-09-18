# Entity フィールド監査

## 結論

公式 OpenAPI と現行の宣言プロパティを突合した結果、プロパティ名の差分は **18 件・7 Entity**
だった。内訳はレスポンス 12 件、リクエスト 6 件である。リクエストの `before` / `after` 2 件は
既存コメントどおり同義の `make_date_min` / `make_date_max` を使うため見送り、**追加候補は 16 件・
7 Entity** とする。

構造化された不足フィールドの型は [ADR 0008](adr/0008-model-structured-response-fields-as-entities.md)
に従い、専用 Entity を提案する。この監査文書を作成した調査時点では `src/` と `tests/` を変更しておらず、
実装は後続の別コミットで行った。

この調査時点以降の変更は、後続の ADR を参照する。

## 調査方法

- 取得日時: 2026-09-15 17:17:29 +0900 (JST)
- 出典: [カラーミーショップ API OpenAPI 3.0.2](https://api.shop-pro.jp/v1/spec/open_api.json)
- 取得方法: `curl --fail --silent --show-error --location`
- 取得結果: HTTP 200、ETag `W/"e796146582cfc44169d9dac8274cec62"`
- SHA-256: `e796146582cfc44169d9dac8274cec624baf8c06756756a071a10856e5129172`
- コード監査基点: `60def840e555a17890af3d7cfa896c4d62e62538`

レスポンス系は各 operation の 200 response 内の実体スキーマ（共通化されているものは
`components.schemas` も併用）を、リクエスト系は後述の operation の query parameters または
request body を正本とした。`Entity::__construct()` と同じ、次の変換で API 名をプロパティ名へ
変換し、継承プロパティと `FIELD_NAMES` の逆引きも含めて Reflection で比較した。

```php
lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $apiField))))
```

このため、例えば `shop_mail_1` は正しく `shopMail1` になり、`Shop::FIELD_NAMES` による
`shopMail1 => shop_mail_1` / `shopMail2 => shop_mail_2` も照合済みである。単純な大文字境界の
正規表現比較は用いていない。

null 許容性は [ADR 0002](adr/0002-entity-nullability-from-openapi.md) に従い、レスポンスでは
`nullable: true` の有無を根拠にした。レスポンススキーマの `required` は必須性の根拠にしていない。
query parameter の `?T` は値として `null` を送れるという意味ではなく、「任意 parameter を送らない」
状態を表す。request body でも `required` と nullable は別の軸として扱った。

## operation 対応表

| Entity | 突合対象 |
| --- | --- |
| `Sales\SearchParameters` | `GET /v1/sales` (`operationId: getSales`) の query parameters |
| `Sales\SaleUpdater` | `PUT /v1/sales/{sale_id}` (`updateSale`) の `requestBody.application/json.sale`。`$id` は path の `sale_id` を表す |
| `Sales\SaleDeliveryUpdater` | 同 `updateSale` の `sale.sale_deliveries[]`。`SaleDelivery` からの継承プロパティを含めた |
| `Customer\SearchParameters` | `GET /v1/customers` (`getCustomers`) の query parameters |
| `OAuth\Options` | OpenAPI `info.description` に記載された認可 URL (`GET /oauth/authorize`) の query とトークン交換 (`POST /oauth/token`) の form parameter。これらは `paths` にはない |

主なレスポンス対応は `getSales` / `getSale` / `updateSale`、`statSale`、`getShop`、
`getCustomers` / `getCustomer`、`getProductCategories` / `getProductCategory*`、
`getProductGroups` / `getProductGroup`、`getDeliveries`、`getPayments` である。nested Entity は親の
該当 object / array item スキーマと突合した。

## 不足フィールド

「nullable: false」は `nullable` が省略されている場合を含む。提案型のクラス名は公開 API になるため、
ADR 0008 の方針を満たす説明用の候補名であり、実装コミットで最終確認する。

| Entity | 不足フィールド | 仕様上の型 / nullable | 提案プロパティ型 | 提案名 | `OBJECT_FIELDS` / `FIELD_NAMES` | enum 候補 | 判断と根拠 |
| --- | --- | --- | --- | --- | --- | --- | --- |
| `Sales\Sale` | `segment` | object / true | `?SaleSegment` | `$segment` | OBJECT: `nullable` + Entity が必要。FIELD: 不要 | なし | **追加**。分割受注の ID、名称、金額群を型付きで保持する |
| `Sales\Sale` | `totals` | object / true | `?SaleTotals` | `$totals` | OBJECT: `nullable` + Entity が必要。FIELD: 不要 | なし | **追加**。税率別税額・割引・税込合計を失っている |
| `Sales\Sale` | `application` | object / true | `?SaleApplication` | `$application` | OBJECT: `nullable` + Entity が必要。FIELD: 不要 | なし | **追加**。受注作成元 OAuth アプリ名を公開する |
| `Sales\Sale` | `shop_coupon` | object / true | `?SaleShopCoupon` | `$shopCoupon` | OBJECT: `nullable` + Entity が必要。FIELD: 不要 | なし | **追加**。利用クーポンの ID、名称、コードを公開する |
| `Sales\SaleDetail` | `tax_reduced` | boolean / false | `bool` | `$taxReduced` | どちらも不要 | なし | **追加**。軽減税率対象かを表す scalar |
| `Sales\SaleDetail` | `customizations` | array&lt;object&gt; / false | `array` (`list<SaleCustomization>`) | `$customizations` | OBJECT: `array` + Entity が必要。FIELD: 不要 | なし | **追加**。各要素は `title` と `value` を持つ |
| `Customer\Customer` | `make_date` | integer / false | `int` | `$makeDate` | どちらも不要 | なし | **追加**。既存 Entity と同じ Unix timestamp 保持方式に揃える |
| `Customer\Customer` | `update_date` | integer / false | `int` | `$updateDate` | どちらも不要 | なし | **追加**。既存 Entity と同じ Unix timestamp 保持方式に揃える |
| `Customer\Customer` | `membership` | object / true | `?Membership` | `$membership` | OBJECT: `nullable` + Entity が必要。FIELD: 不要 | なし | **追加**。一覧では `progress` が省略され、単体取得だけで返る点を nested Entity 側で許容する |
| `Customer\Customer` | `external_accounts` | array&lt;object&gt; / true | `?array` (`list<ExternalAccount>|null`) | `$externalAccounts` | OBJECT: `array` + `nullable` + Entity が必要。FIELD: 不要 | `provider` 用の既存 enum なし。新規候補 | **追加**。`null` と空配列を保持し分ける共通変換修正を先行させる |
| `Product\Category` | `meta_tag` | object (`allOf`) / false | `MetaTag` | `$metaTag` | OBJECT: Entity が必要。FIELD: 不要 | なし | **追加**。`title` / `keywords` / `description` は各 nullable。Group と同じ型を共有する |
| `Product\Group` | `meta_tag` | object / true | `?MetaTag` | `$metaTag` | OBJECT: `nullable` + Entity が必要。FIELD: 不要 | なし | **追加**。Category と同じワイヤ形式・意味なので共有型を使う |
| `Sales\SearchParameters` | `after` | string / false、query 自体は任意 | `?DateTime` | `$after` | OBJECT: Value 変換が必要。FIELD: 不要 | なし | **見送り**。`make_date_min` と同義で、既存コメントが非対応を明示する |
| `Sales\SearchParameters` | `before` | string / false、query 自体は任意 | `?DateTime` | `$before` | OBJECT: Value 変換が必要。FIELD: 不要 | なし | **見送り**。`make_date_max` と同義で、二重 API を避ける |
| `Sales\SearchParameters` | `customer_mail` | string / false、query 自体は任意 | `?string` | `$customerMail` | どちらも不要 | なし | **追加**。顧客メールアドレスの部分一致検索が現在送れない |
| `Customer\SearchParameters` | `line_uid` | string / false、query 自体は任意 | `?string` | `$lineUid` | どちらも不要 | なし | **追加**。LINE UID 完全一致検索が現在送れない |
| `Customer\SearchParameters` | `membership_id` | string / false、query 自体は任意 | `?string` | `$membershipId` | どちらも不要 | なし | **追加**。会員ランク ID 完全一致検索が現在送れない |
| `Customer\SearchParameters` | `receive_mail_magazine` | boolean / false、query 自体は任意 | `?bool` | `$receiveMailMagazine` | どちらも不要 | なし | **追加**。メルマガ受信可否検索が現在送れない |

注: 上表の `Product\Category` の `meta_tag` 行は監査時点 (PR #37) の記述である。現行契約は [ADR 0012](adr/0012-allow-nullability-from-api-observations.md) のとおり nullable で、欠損・`null` は `null` として扱う。

追加候補のトップレベルフィールドに、そのまま再利用できる既存 enum はない。全
`src/Constants/*.php` を確認した。nested の `external_accounts[].provider` は OpenAPI 上の enum が
現在 `0` (LINE) のみだが、対応 enum は存在しない。OAuth の scope には既存 `AuthScope` と
`Values\Scopes` があり、後述のとおり `Options` へ重複保持させない。

## 差分なし Entity の網羅表

| リソース | Entity | 結果 / 対応スキーマ |
| --- | --- | --- |
| Sales | `Sales\SaleDelivery` | 不足なし。`sale.sale_deliveries[]` / `saleDelivery` |
| Sales | `Sales\Stat` | 不足なし。`GET /v1/sales/stat` の `sales_stat` |
| Sales request | `Sales\SaleUpdater` | 不足なし。`updateSale` body の `sale` 3項目を保持し、`$id` は path 用 |
| Sales request | `Sales\SaleDeliveryUpdater` | 不足なし。`updateSale` body の nested 28項目は継承分を含めて宣言済み |
| Shop | `Shop\Shop` | 不足なし。`GET /v1/shop` の `shop`。数字付きメール項目は `FIELD_NAMES` も確認済み |
| Delivery | `Delivery\Delivery` | 不足なし。`GET /v1/deliveries` の items |
| Delivery | `Delivery\Charge` | 不足なし。`delivery.charge` |
| Delivery | `Delivery\Area` | 不足なし。`charge_ranges_by_area[]` / `charge_ranges_max_weight[]` |
| Delivery | `Delivery\Weight` | 不足なし。`charge_ranges_by_weight[]` の tuple を `weight` / `areas` へ変換する実装を確認 |
| Payment | `Payment\Payment` | 不足なし。`GET /v1/payments` の items |
| Payment | `Payment\Cod` | 不足なし。`payment.cod` |
| Payment | `Payment\Card` | 不足なし。`payment.card` |
| Payment | `Payment\Brand` | 不足なし。`payment.card.brands[]` |
| Payment | `Payment\Financial` | 不足なし。`payment.financial` |
| 共通 | `Pagination` | 不足なし。`meta` の `total` / `limit` / `offset` |
| 共通 | `Collection` | API field を直接 hydrate しない list wrapper のため不足なし |
| 共通 | `Page` | items と `Pagination` を合成する wrapper。API field は各 Entity に委譲するため不足なし |
| 共通 | `Error` | 非 2xx の `errors[]` に現れる `code` / `message` / `status` / 任意の `field` を保持済み |
| OAuth | `OAuth\AccessToken` | OpenAPI 本文の成功例 `access_token` / `token_type` / `scope` は保持済み。formal response schema はない |
| OAuth | `OAuth\ErrorResponse` | OpenAPI に token error schema はない。ADR 0007 / RFC 6749 の `error`、`error_description`、`error_uri`、`state` は保持済み |
| OAuth request | `OAuth\Options` | 後述の責務分担では不足なし。クラスは `Entity` 非継承だが監査対象に含めた |
| 基底 | `Entity` | domain schema に対応しない hydration 基盤。比較対象外だが、全サブクラスの変換規則を確認した |

## 見送り対象

### Sales の別名 query

`getSales` の `after` と `before` は仕様上それぞれ `make_date_min` と `make_date_max` の同義語である。
`Sales\SearchParameters` のクラスコメントも前者をサポートしないと明示している。表面積を増やして
同じ query を二重指定できる状態にする利点がないため、今回も追加しない。

### OAuth の Service 所有 parameter

OpenAPI の `paths` には OAuth operation がなく、`info.description` の手順・表だけが正本である。
`OAuth\Options` は接続設定を保持し、request 1回ごとの値は `Services\OAuth` が組み立てる設計なので、
次の parameter は仕様に存在しても `Options` の不足プロパティには数えない。

| parameter | operation | 現在の所有者 | 判断 |
| --- | --- | --- | --- |
| `response_type` | `GET /oauth/authorize` query | `Services\OAuth::getUrl()` が `code` を固定指定 | `Options` には追加しない |
| `scope` | 同上 | `getUrl(Scopes $scopes)`。`Scopes` は既存 `AuthScope` を利用 | `Options` には追加しない |
| `code` | `POST /oauth/token` form | `exchangeCode2Token(string $code)` | request ごとの値なので追加しない |
| `grant_type` | 同上 | `exchangeCode2Token()` が `authorization_code` を固定指定 | `Options` には追加しない |

`client_id`、`client_secret`、`redirect_uri` は `Options` に存在する。`endpoint_uri` は API parameter
ではなく接続先設定である。

### ライブラリ内部の wrapper 状態

`Collection::$_items`、`Page` の pagination context、`Pagination` の response context、
`OAuth\ErrorResponse::$_response`、`OAuth\AccessToken::$scopes` などはワイヤ上の field ではない。
OpenAPI との差分として削除・追加の対象にはしない。

## 参考情報（今回は対象外）

不足フィールド以外の型・null 許容性は修正対象に含めない。調査中に確認できた既存差異は次のとおり。

- `Sale::$memo` は現行 OpenAPI から消えているが、ADR 0002 の互換性判断により残されている。
- `Sale::$customer` は未型付け、`SaleDetail::$productModelNumber` と
  `SaleDelivery::$furigana` は PHP 側が nullable だが response schema に `nullable: true` がない。
- `Shop::$contractStartDate`、`$contractEndDate`、`$prefId`、`$prefName`、`$address2` は PHP 側が
  nullable だが response schema に `nullable: true` がない。
- `Payment::$cod`、`$card`、`$financial` は PHP 側が nullable、schema 側は nullable ではない。
  ただし各 description は決済種別により存在すると明記しており、省略可能性との区別が必要である。
- `Customer::$sex` と検索条件は OpenAPI enum の `not_applicable` を含むが、監査時点（PR #37）の
  `Sex` enum は `male` / `female` のみだった。
  - **対応済み（Issue #54、2026-09-17）**: `Sex::NOT_APPLICABLE` を追加。
- OAuth の説明にある scope のうち `write_shop_coupons`、`read_templates`、`write_templates`、
  `read_analytics` は既存 `AuthScope` enum にない。そのため `Scopes` と `AccessToken` でも扱えない。
- `updateSale` request の `point_state` は `cenceled` と記載される一方、response schema と既存
  `PointState` は `canceled` である。仕様側の typo と考えられるが、実 API 確認なしに変更しない。
- `Error::$code` は OpenAPI では integer、公開 getter は string である。既存 communicator が
  互換性のため string へ正規化している。
- OAuth token / error は OpenAPI に formal schema がなく、`AccessToken::$createdAt` などは
  OpenAPI だけでは妥当性を確定できない。

## コミット分割案と見積り

見積りには Entity / getter / `OBJECT_FIELDS`、fixture、単体・横断契約テスト、静的解析を含む。
今回はコミットしない。

| 順序 / リソース | 想定コミット | 主な作業 | 見積り |
| --- | --- | --- | --- |
| 1 / 共通 | `fix: nullableなEntity配列でnullを保持する` | `Entity::build()` の nullable 配列契約、横断テスト | 0.5日 |
| 2 / Sales | `feat: 受注レスポンスの不足フィールドを追加する` | Sale 4項目、SaleDetail 2項目、nested Entity 5種、getter と fixture | 1.5〜2日 |
| 3 / Sales | `feat: 受注検索に顧客メール条件を追加する` | `customer_mail` と query テスト。`before` / `after` は追加しない | 0.25日 |
| 4 / Customer | `feat: 顧客レスポンスの不足フィールドを追加する` | timestamp 2項目、membership / external accounts、nested Entity と provider enum 判断 | 1.5〜2日 |
| 5 / Customer | `feat: 顧客検索の不足条件を追加する` | `line_uid`、`membership_id`、`receive_mail_magazine` と query テスト | 0.5日 |
| 6 / Product | `feat: カテゴリーとグループにメタタグを追加する` | 共有 `MetaTag`、2 Entity の getter / nullability テスト | 0.75日 |
| 7 / OAuth | コードコミットなし | formal schema 不在。責務分担を維持 | 0日 |
| 8 / Shop | コードコミットなし | 不足フィールドなし | 0日 |
| 9 / Delivery | コードコミットなし | 不足フィールドなし | 0日 |
| 10 / Payment | コードコミットなし | 不足フィールドなし | 0日 |
| 11 / 共通 docs | `docs: Entityフィールド監査と構造化型の判断を記録する` | 本レポートと ADR 0008 | 0.25日 |

実装総量はおおむね **4.5〜5.5人日**。nested Entity の公開命名レビューと、実 API fixture で
`external_accounts: null` / `[]` を確認できるかにより増減する。

## 実装時の確認事項

1. 各追加フィールドについて、存在・欠損・`null`・不正型の fixture を先に追加する。
2. `external_accounts` は `null` と `[]` の両方をテストし、ADR 0002 / 0008 の契約を固定する。
3. 一覧と単体取得で `membership.progress` の有無が異なるケースをテストする。
4. `Category` の `meta_tag` は nullable ではないが、`required` を根拠に存在を仮定せず、欠損時は
   既存の `MissingFieldException` 契約に従う。
   注: 上記 4 は監査時点 (PR #37) の記述である。現行契約は [ADR 0012](adr/0012-allow-nullability-from-api-observations.md) のとおり nullable で、欠損・`null` は `null` として扱う。
5. `composer check` と全 Entity の横断契約テストを実行し、今回の参考情報は別スコープに保つ。
