# enum と公式 OpenAPI の突合記録

## 結論

`src/Constants/` の backed enum 23 個を公式 OpenAPI と突き合わせた。レスポンス側の
`enum:` と比較できる箇所では、現行ライブラリに既知の仕様値の不足はない。
`Sex::NOT_APPLICABLE` と `AuthScope` の不足 4 case は、監査基点までに追加済みである。
`PointState` と `ProductDisplayState` には OpenAPI のリクエスト・レスポンス間の不一致が残る。
実 API での受理値は未検証であり、仕様だけでは将来の値追加頻度も確定できない。

## 調査方法

- 元分析の仕様取得日: **2026-09-17 17:38 JST**。出典:
  [カラーミーショップ API OpenAPI JSON](https://api.shop-pro.jp/v1/spec/open_api.json)
  （OpenAPI 3.0.2、`info.version: 1.0.0`）。
- 2026-09-18 に同 URL を `curl --fail --silent --show-error --location` で再取得し、
  `src/Constants/`、使用箇所、表の主要な `enum:` を再確認した。再取得した JSON の SHA-256 は
  `0d61491b262f92a76aa0926734b5e946792ac2d2cef2ad2284e2b123d245817b`。
- 当時の `enum:` 値と OpenAPI 内の JSON Pointer は
  [監査用抜粋](enum-openapi-excerpt.json) に保存した。`null` はそのフィールドに `enum:` がなく、
  空の項目は対応する `enum:` 自体がないことを表す。説明文による照合値は抜粋に含めない。
  同じ JSON からの再抽出手順:

  ```sh
  curl --fail --silent --show-error --location https://api.shop-pro.jp/v1/spec/open_api.json --output /tmp/color-me-shop-openapi.json
  python3 docs/extract-enum-openapi-excerpt.py /tmp/color-me-shop-openapi.json 2026-09-18 > docs/enum-openapi-excerpt.json
  ```
- コード監査基点: `origin/master` の
  `3e5ced56f28a86c3a168b7319cf80741e4438b69`。
- 同じ用途・方向の OpenAPI 定義と enum の **backing value** を比較した。「不足」は
  ライブラリにない仕様値、「余り」はライブラリだけの値を指す。`enum:` がない箇所は
  説明文との照合にとどめ、正式な enum 差分とは区別した。
- 主に Entity のレスポンススキーマを使い、異なるリクエスト定義は併記した。
  `FallbackEnum` はインターフェースであり、23 個には数えない。実 API への通信は行っていない。

## enum 一覧

| enum / ライブラリの値 | 使用箇所 | OpenAPI 定義 | 差分（不足 / 余り） | 値の性質 | `FallbackEnum` |
| --- | --- | --- | --- | --- | --- |
| `AuthRedirectUri`: `urn:ietf:wg:oauth:2.0:oob` | `OAuth\Options.redirectUri`（文字列も受理） | `enum:` なし。OAuth 説明の `redirect_uri` は登録 URI | 比較不能 | 特別な入力定数。URI 全体は開集合 | 無 |
| `AuthScope`: 9 値（`read_products`、`write_products`、`read_sales`、`write_sales`、`read_shop_coupons`、`write_shop_coupons`、`read_templates`、`write_templates`、`read_analytics`） | `OAuth\AccessToken.scopes`、`Values\Scopes` | `enum:` なし。`info.description` の scope 表に同じ 9 値 | 正式な enum 比較は不能。説明表との差分は解消済み（#55） | 権限項目は拡張可能。`buildEnum()` 非経由 | 無 |
| `CategoryDisplayState`: `showing`, `hidden`, `members_only` | `Product\Category.displayState`（大・小カテゴリーへ継承） | `productCategory.display_state`: 同じ 3 値 | なし / なし | 商品機能に応じ拡張可能だが表示制御に影響 | 無 |
| `ContractPlan`: `unknown`, `regular`, `large`, `premium`, `free`, `economy`, `small`, `platinum`, `dormant`, `lolipop`, `heteml`, `goope` | `Shop\Shop.contractPlan` | `shop.contract_plan`: 同じ 12 値 | なし / なし（順序のみ相違） | 商用プランは拡張可能。`unknown` は仕様上の正規値 | 無 |
| `DeliveryChargeFreeType`: `not_free`, `free_to_limit`, `free` | `Delivery\Delivery.chargeFreeType` | `delivery.charge_free_type`: 同じ 3 値 | なし / なし | 料金規則は増え得るが計算上の意味が重要 | 無 |
| `DeliveryChargeType`: `fixed`, `by_price`, `by_area`, `by_weight` | `Delivery\Delivery.chargeType` | `delivery.charge_type`: 同じ 4 値 | なし / なし | 料金アルゴリズムは増え得るが計算上の意味が重要 | 無 |
| `DeliveryMethodType`: `other`, `yamato`, `yamato_pickup`, `sagawa`, `jp` | `Delivery\Delivery.methodType` | `delivery.method_type`: 同じ 5 値 | なし / なし | 配送会社は追加され得る。`other` は仕様上の正規値 | 無 |
| `DisplayState`: `showing`, `hidden` | `Delivery\Delivery.displayState` | `delivery.display_state`: 同じ 2 値 | なし / なし | 二値の表示状態 | 無 |
| `DomainPlan`: `cmsp_sub_domain`, `own_domain`, `own_sub_domain` | `Shop\Shop.domainPlan` | `shop.domain_plan`: 同じ 3 値 | なし / なし | サイト URL の意味に関係するプラン種別 | 無 |
| `ErrorCode`: `401010`, `404100`, `422210`（string） | `src/` から未使用。`Entities\Error.code` は string | `enum:` なし。`info.description` にコード表、エラースキーマの `code` は integer | 正式な enum 比較は不能。代表コードのみで網羅目的ではない | エラーコードは開集合。`buildEnum()` 非経由 | 無 |
| `ExternalAccountProvider`: `0`, 番兵 `-1` | `Customer\ExternalAccount.provider` | `customer.external_accounts[].provider`: `[0]` | なし / 番兵 `-1`（仕様値ではない） | 外部連携先は追加され得る | **有** |
| `KouzaType`: `saving`, `checking` | `Payment\Financial.kouzaType` | `payment.financial.kouza_type`: 同じ 2 値 | なし / なし | 普通・当座の口座分類はほぼ閉集合 | 無 |
| `MailState`: `not_yet`, `sent`, `pass` | `Sales\Sale` の 3 メール状態、`Sales\SearchParameters` | `sale` の 3 状態と `GET /v1/sales` 検索条件: 同じ 3 値 | なし / なし | 状態遷移。入力にも使用 | 無 |
| `MailType`: `accepted`, `paid`, `delivered` | `Sales::sendMail()` / `Client::sendMail()` の引数 | `POST /v1/sales/{sale_id}/mails` の `mail.type`: 同じ 3 値 | なし / なし | 送信操作の入力種別。`buildEnum()` 非経由 | 無 |
| `OpenState`: `opened`, `closed`, `prepare`, `paused` | `Shop\Shop.openState`, `.mobileOpenState` | `shop` の両フィールド: 同じ 4 値 | なし / なし | 開店状態の遷移 | 無 |
| `PaymentType`: 整数 `0`〜`45`（46 case） | `Payment\Payment.type` | `payment.type` に `enum:` なし。説明表に `0`〜`45` | 正式な enum 比較は不能。説明表の 46 値と一致 | 決済方法のカタログは追加され得る | 無 |
| `PointState`: `assumed`, `fixed`, `canceled` | `Sales\Sale` の 3 ポイント状態、`Sales\SaleUpdater.pointState` | `sale` レスポンス: 同じ 3 値。`PUT /v1/sales/{sale_id}` のリクエストだけ `cenceled` | レスポンス差分なし。リクエストは `cenceled` 不足 / `canceled` 余り（仕様 typo の疑い、未検証） | 状態遷移。更新入力にも使用 | 無 |
| `Prefecture`: 整数 `1`〜`48` | `Customer\Customer`、`Shop\Shop`、`Sales\SaleDelivery`、`Delivery\Area` の `prefId` と更新入力 | 各 `pref_id` に `enum:` なし。説明は多くが海外 `48` を含み、一部は `47` まで | 正式な enum 比較は不能。海外 `48` のフィールド別の扱いは未検証 | 地理的分類としてほぼ閉集合 | 無 |
| `ProductDisplayState`: `showing`, `hidden`, `showing_for_members`, `sale_for_members` | `Product\Group.displayState` | `productGroup` レスポンス: 同じ 4 値。作成・更新リクエスト: `showing`, `hidden`, `members_only` | レスポンス差分なし。リクエストは `members_only` 不足 / 会員向け 2 値余り。受理値は未検証 | 表示・購入可否に影響 | 無 |
| `Sex`: `male`, `female`, `not_applicable` | `Customer\Customer.sex`、`Customer\SearchParameters.sex`、`Sale.customer` | `customer.sex`、`sale.customer.sex`、顧客検索: 同じ 3 値。`POST /v1/sales` の customer 入力だけ前 2 値 | レスポンス・検索はなし / なし（#54 で解消）。受注作成 request では `not_applicable` 余り。受理値は未検証 | 顧客属性は仕様拡張され得る | 無 |
| `ShopState`: `enabled`, `suspended`, `unsigned` | `Shop\Shop.state` | `shop.state`: 同じ 3 値 | なし / なし | アカウント状態の遷移 | 無 |
| `TaxRoundingMethod`: `round_off`, `round_down`, `round_up` | `Shop\Shop.taxRoundingMethod` | `shop.tax_rounding_method`: 同じ 3 値 | なし / なし | 丸めの三方式でほぼ閉集合 | 無 |
| `TaxType`: `excluded`, `included` | `Shop\Shop.taxType` | `shop.tax_type`: 同じ 2 値 | なし / なし | 税込・税抜でほぼ閉集合 | 無 |

## 判定の限界と既知の不一致

OpenAPI の `enum:` は宣言時点の値を示すが、今後の追加頻度や実 API の挙動は示さない。
`PaymentType` と `Prefecture` の説明文の値は `enum:` 制約ではない。
`ErrorCode` は現行 `src/` から参照されず、`AuthScope` も独自解析であり、
これらに `FallbackEnum` を実装するだけでは Entity の変換経路に接続されない。
`CategoryDisplayState` と `ProductDisplayState` は別リソースの表示状態として比較した。

`PointState`、`ProductDisplayState`、`Prefecture` の仕様内の不一致は
[Entity フィールド監査](entity-field-audit.md)にも記録されている。
`PointState` の `cenceled` は typo の疑いにとどまり、リクエストで実際に受理される値は未検証。
`review.sex` の `no_answer` は顧客の `Sex` と別リソースの値である。
`ExternalAccountProvider` の `-1` は [ADR 0009](adr/0009-opt-in-enum-fallback.md) で定めた番兵である。

## 判断基準の選択肢

以下は比較のための案であり、この文書では採否を決めない。

| 案 | 対象の考え方 | 主な利点と制約 |
| --- | --- | --- |
| A: 現行維持 | 実 API の未知の有効値または仕様変更が確認された enum ごとに opt-in を再審査 | 既存の厳格な検証を保つが、新値でレスポンス構築が失敗し得る |
| B: 読み取りカタログを限定追加 | `Entity::buildEnum()` 経由のレスポンスで、外部要因により値が増えやすいものを個別に追加。元分析の具体案は `PaymentType` | 一覧取得の継続に有利。`unknown` / `other` などの正規値と番兵の区別が必要 |
| C: 閉集合のみ厳格 | 地理・税・口座などのほぼ固定の集合を除き、レスポンス enum に広く適用 | 拡張に強いが、状態遷移・料金・表示可否の不正値も隠し得る |

採否を検討するときは、応答側での使用、未知の**有効**値が生じる可能性、未知値での処理継続の利益、
仕様上の正規値と番兵の非衝突、入力への再送時の意味を各 enum で確認する必要がある。
既知の仕様値欠落は case 追加で直す対象である。
