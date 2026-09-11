# ColorMeShopApi API client

GMOペパボが提供しているカラーミーショップの API を PHP から利用するためのライブラリです。

現在一部のみ実装済みです。対応状況は [未実装](#未実装) を参照してください。

## 目次

* [Environment](#environment)
* [Installation](#installation)
* [Preparation](#preparation)
* [How to Use](#how-to-use)
  * [コードサンプルの前提](#コードサンプルの前提)
  * [エラーハンドリング](#エラーハンドリング)
  * [値オブジェクト](#値オブジェクト)
  * [OAuth](#oauth)
  * [ショップ](#ショップ)
  * [受注](#受注)
  * [顧客](#顧客)
  * [商品グループ](#商品グループ)
  * [商品カテゴリー](#商品カテゴリー)
  * [決済](#決済)
  * [配送](#配送)
  * [ページネーション](#ページネーション)
* [未実装](#未実装)
* [開発者向け](#開発者向け)
* [CLI](#cli)
* [ライセンスについて](#ライセンスについて)
* [サポート](#サポート)

## Environment

* PHP 8.1 以上
* Composer

## Installation

利用したいプロジェクトのディレクトリに移動して、以下のコマンドを実行する。

```bash
composer config repositories.shimoning/color-me-shop-api vcs git@github.com:shimoning/color-me-shop-api.git
```

その後、以下のコマンドでインストールが行われる。

```bash
composer require shimoning/color-me-shop-api
```

### Update

下記のコマンドでアップデートが可能。

```bash
composer update shimoning/color-me-shop-api
```

## Preparation

API を利用するためには利用登録が必要。

### デベロッパー登録

1. [開発者サイト](https://developer.shop-pro.jp/) の「デベロッパー登録」から登録
2. アプリを作成
3. `クライアントID` と `クライアントシークレット` を控える
4. `リダイレクトURI` を設定し、疎通を確認しておく

### ショップ登録

[カラーミーショップ](https://shop-pro.jp/) から、API の動作確認に使うショップを登録しておく。

## How to Use

利用方法。

### コードサンプルの前提

以降のコードサンプルでは、必要に応じて以下のクラスをインポートする。

```php
require __DIR__ . '/vendor/autoload.php';

use Shimoning\ColorMeShopApi\Client;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Constants\AuthScope;
use Shimoning\ColorMeShopApi\Constants\MailType;
use Shimoning\ColorMeShopApi\Constants\PointState;
use Shimoning\ColorMeShopApi\Entities\Customer\SearchParameters as CustomerSearchParameters;
use Shimoning\ColorMeShopApi\Entities\OAuth\Options as OAuthOptions;
use Shimoning\ColorMeShopApi\Entities\Sales\SaleUpdater;
use Shimoning\ColorMeShopApi\Entities\Sales\SearchParameters as SalesSearchParameters;
use Shimoning\ColorMeShopApi\Exceptions\ColorMeApiException;
use Shimoning\ColorMeShopApi\Exceptions\InvalidPaginationException;
use Shimoning\ColorMeShopApi\Exceptions\MissingPaginationException;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use Shimoning\ColorMeShopApi\Values\DateTime as ApiDateTime;
use Shimoning\ColorMeShopApi\Values\Furigana;
use Shimoning\ColorMeShopApi\Values\Limit;
use Shimoning\ColorMeShopApi\Values\Scopes;
```

OAuth で取得したアクセストークンを `$token` として扱う。API を複数回呼び出す場合は、最初に `Client` へ設定しておくと各メソッドで省略できる。

```php
$client = new Client($token);
```

### エラーハンドリング
API が 2xx 以外のレスポンスを返した場合、各 API メソッドは `Communicator\Errors` を返す。`Errors` は `Collection` を継承しているため、`foreach` で個々の `Entities\Error` を取得できる。

```php
$result = $client->getShop();

if ($result instanceof Errors) {
    $response = $result->getResponse();
    $response->getStatus();  // HTTP ステータス
    $response->getRawBody(); // API の生レスポンス

    foreach ($result as $error) {
        $error->getCode();
        $error->getMessage();
        $error->getField();
        $error->getStatus();
    }
}
```

一方、アクセストークンの未指定や値オブジェクトの不正な入力など、リクエスト送信前に検出できる問題では `Exceptions\ParameterException` が投げられる。ページネーション情報の欠損や不正には、それぞれ `Exceptions\MissingPaginationException`、`Exceptions\InvalidPaginationException` が投げられる。これらはすべて `Exceptions\ColorMeApiException` を継承しているため、ライブラリの例外をまとめて捕捉する場合は親クラスを利用できる。

```php
try {
    $client->getShop();
} catch (ParameterException $e) {
    // アクセストークンや引数を確認する
} catch (ColorMeApiException $e) {
    // その他のライブラリ例外
}
```

API 固有のエラーコードや内容は [エラー仕様書](https://developer.shop-pro.jp/docs/colorme-api#section/API/%E3%82%A8%E3%83%A9%E3%83%BC) も参照すること。

### 値オブジェクト
検索条件や OAuth スコープの値を検証し、API 用の文字列や整数へ変換するクラス。

```php
$date = new ApiDateTime('2024-01-01 12:34:56');
$date->get(); // 2024-01-01 12:34:56

$dateFromObject = new ApiDateTime(new \DateTimeImmutable('2024-01-01'));
$dateFromObject->get(); // 2024-01-01 00:00:00

$furigana = new Furigana('ヤマダ タロウ');
$furigana->get(); // ヤマダ タロウ

$scopes = new Scopes([
    AuthScope::READ_PRODUCTS,
    AuthScope::READ_SALES,
]);
$scopes->get(); // read_products read_sales

$limit = new Limit(50);
$limit->get(); // 50
```

* `Values\DateTime`: `YYYY-MM-DD` または `YYYY-MM-DD hh:mm:ss` 形式の文字列、もしくは `DateTimeInterface` を受け付ける
* `Values\Furigana`: 全角カタカナ、長音符、半角・全角スペースを受け付ける
* `Values\Scopes`: `Constants\AuthScope` または定義済みスコープ文字列の配列を、OAuth 用のスペース区切り文字列へ変換する
* `Values\Limit`: 1 以上 100 以下の取得件数を受け付ける

`SalesSearchParameters` や `CustomerSearchParameters` のコンストラクタへ文字列や整数を渡した場合も、対応する値オブジェクトへ内部で変換される。不正な値には `ParameterException` が投げられる。

### OAuth
#### 認証情報
以下のものを PHP で扱えるようにしておく。

* `クライアントID`
* `クライアントシークレット`
* `リダイレクトURI`

以降のコードサンプルでは、それぞれ `$clientId`, `$clientSecret`, `$redirectUri` として表現する。

#### OAuth に使う共通情報
```php
$oAuthOptions = new OAuthOptions($clientId, $clientSecret, $redirectUri);
```

#### OAuth アプリケーションの登録用 URL の取得
```php
$oAuthScopes = new Scopes([
    AuthScope::READ_PRODUCTS,
    AuthScope::READ_SALES,
]);
$oAuthUri = (new Client())->getOAuthUrl($oAuthOptions, $oAuthScopes);
```

#### 認可コードをアクセストークンに交換
上記で取得した URL を開くと、ショップへのログインと認可を行う画面に移動する。認可後は `リダイレクトURI` へ遷移し、クエリ文字列の `code` に認可コードが設定される。

```php
$code = filter_input(INPUT_GET, 'code');
if (! is_string($code)) {
    throw new \RuntimeException('認可コードを取得できませんでした。');
}

$tokenOrErrors = (new Client())->exchangeCode2Token($oAuthOptions, $code);
if ($tokenOrErrors instanceof Errors) {
    // エラー処理
} else {
    $token = $tokenOrErrors->getAccessToken();
}
```

ここで取得した `$token` は、安全な方法で保存する。アクセストークンの有効期限については公式ドキュメントを確認すること。

### ショップ
#### ショップ情報の取得
```php
// インスタンス作成時にアクセストークンを設定する
$client = new Client($token);
$shopOrErrors = $client->getShop();

// または、メソッドの引数として設定する
$shopOrErrors = (new Client())->getShop($token);

if ($shopOrErrors instanceof Errors) {
    // エラー処理
} else {
    $shopOrErrors->getId();
    $shopOrErrors->getName1();
}
```

### 受注
#### 受注データのリストを取得
検索条件とアクセストークンはどちらも省略可能。ただし、`Client` にアクセストークンを設定していない場合は、メソッドの第2引数へ指定する必要がある。

```php
$searchParameters = new SalesSearchParameters([
    'make_date_min' => '2024-01-01',
    'make_date_max' => '2024-01-31 23:59:59',
    'accepted_mail_state' => 'not_yet',
    'limit' => 50,
    'offset' => 0,
]);
$salesOrErrors = $client->getSales($searchParameters);

// 検索条件を省略し、メソッドの引数でアクセストークンを設定する場合
$salesOrErrors = (new Client())->getSales(null, $token);

if ($salesOrErrors instanceof Errors) {
    // エラー処理
} else {
    foreach ($salesOrErrors as $sale) {
        $sale->getId();
    }

    $salesOrErrors->count();
    $salesOrErrors->getTotal();
    $salesOrErrors->getLimit();
    $salesOrErrors->getOffset();
}
```

#### 売上集計の取得
```php
$statOrErrors = $client->statSales(new \DateTimeImmutable('2024-01-01'));

if ($statOrErrors instanceof Errors) {
    // エラー処理
} else {
    $statOrErrors->getAmountToday();
    $statOrErrors->getCountToday();
}
```

#### 受注データの取得
```php
$saleId = 1001;
$saleOrErrors = $client->getSale($saleId);

if ($saleOrErrors instanceof Errors) {
    // エラー処理
} else {
    $saleOrErrors->getId();
    $saleOrErrors->getSaleDeliveries();
}
```

#### 受注データの更新
既存の受注から更新用エンティティを生成すると、API が必要とする現在値を引き継げる。

```php
$saleOrErrors = $client->getSale($saleId);
if (! $saleOrErrors instanceof Errors) {
    $updater = SaleUpdater::convert($saleOrErrors);
    $updater->setPaid(true);
    $updater->setPointState(PointState::FIXED);

    $updatedSaleOrErrors = $client->updateSale($updater);
}
```

#### 受注のキャンセル
第2引数の `$restock` を `true` にすると、キャンセルした商品の在庫を戻す。

```php
$canceledSaleOrErrors = $client->cancelSale($saleId, true);

if ($canceledSaleOrErrors instanceof Errors) {
    // エラー処理
}
```

#### メールの送信
送信できるメール種別は `MailType::ACCEPTED`、`MailType::PAID`、`MailType::DELIVERED`。

```php
$sentOrErrors = $client->sendSalesMail($saleId, MailType::DELIVERED);

if ($sentOrErrors instanceof Errors) {
    // エラー処理
} else {
    // 成功時は true
}
```

### 顧客
#### 顧客データの一覧を取得
```php
$searchParameters = new CustomerSearchParameters([
    'name' => '山田太郎',
    'furigana' => 'ヤマダ タロウ',
    'member' => true,
    'limit' => 50,
    'offset' => 0,
]);
$customersOrErrors = $client->getCustomers($searchParameters);

// 検索条件を省略する場合
$customersOrErrors = $client->getCustomers();

if ($customersOrErrors instanceof Errors) {
    // エラー処理
} else {
    foreach ($customersOrErrors as $customer) {
        $customer->getId();
        $customer->getName();
        $customer->getMail();
    }

    $customersOrErrors->getTotal();
    $customersOrErrors->getLimit();
    $customersOrErrors->getOffset();
}
```

`Client::getCustomers()` は、内部で `Services\Customer::page(SearchParameters $searchParameters, ?string $accessToken = null)` を呼び出す。

#### 顧客データの取得
```php
$customerId = 501;
$customerOrErrors = $client->getCustomer($customerId);

if ($customerOrErrors instanceof Errors) {
    // エラー処理
} else {
    $customerOrErrors->getId();
    $customerOrErrors->getName();
    $customerOrErrors->getFurigana();
}
```

`Client::getCustomer()` は、内部で `Services\Customer::one(int|string $id, ?string $accessToken = null)` を呼び出す。

#### 顧客データを追加
現在は未実装。`Services\Customer` に追加用のメソッドはまだ存在しない。

### 商品グループ
#### 商品グループ一覧を取得
```php
$groupsOrErrors = $client->getProductGroups();

if ($groupsOrErrors instanceof Errors) {
    // エラー処理
} else {
    foreach ($groupsOrErrors as $group) {
        $group->getId();
        $group->getName();
        $group->getParentGroupId();
    }
}
```

`Client::getProductGroups()` は、内部で `Services\Product::groups(?string $accessToken = null)` を呼び出す。

### 商品カテゴリー
#### 商品カテゴリー一覧を取得
```php
$categoriesOrErrors = $client->getProductCategories();

if ($categoriesOrErrors instanceof Errors) {
    // エラー処理
} else {
    foreach ($categoriesOrErrors as $category) {
        $category->getIdBig();
        $category->getIdSmall();
        $category->getName();
        $category->getChildren();
    }
}
```

`Client::getProductCategories()` は、内部で `Services\Product::categories(?string $accessToken = null)` を呼び出す。

### 決済
#### 決済設定の一覧を取得
```php
$paymentsOrErrors = $client->getPayments();

if ($paymentsOrErrors instanceof Errors) {
    // エラー処理
} else {
    foreach ($paymentsOrErrors as $payment) {
        $payment->getId();
        $payment->getName();
        $payment->getType();
        $payment->getFee();
    }
}
```

`Client::getPayments()` は、内部で `Services\Payment::all(?string $accessToken = null)` を呼び出す。

### 配送
#### 配送方法一覧を取得
```php
$deliveriesOrErrors = $client->getDeliveries();

if ($deliveriesOrErrors instanceof Errors) {
    // エラー処理
} else {
    foreach ($deliveriesOrErrors as $delivery) {
        $delivery->getId();
        $delivery->getName();
        $delivery->getMethodType();
        $delivery->getPreferredDateUse();
        $delivery->getPreferredPeriodUse();
    }
}
```

`Client::getDeliveries()` は、内部で `Services\Delivery::all(?string $accessToken = null)` を呼び出す。

#### 配送日時設定を取得
現在は未実装。`Services\Delivery` に配送日時設定取得用のメソッドはまだ存在しない。

### ページネーション
受注一覧と顧客一覧は `Entities\Page` を返す。`Page` は `Entities\Collection` を継承しているため、`foreach`、`count()`、`all()`、配列アクセスが利用できる。

* `getTotal()`: 合計数
* `getLimit()`: 取得件数
* `getOffset()`: 取得開始位置

```php
$customersOrErrors = $client->getCustomers(new CustomerSearchParameters([
    'limit' => 50,
    'offset' => 100,
]));

if (! $customersOrErrors instanceof Errors) {
    count($customersOrErrors);
    $customersOrErrors->all();
    $customersOrErrors[0] ?? null;
    $customersOrErrors->getTotal();
    $customersOrErrors->getLimit();
    $customersOrErrors->getOffset();

    $nextOffset = $customersOrErrors->getOffset() + $customersOrErrors->getLimit();
}
```

`Services\Sales::page()` と `Services\Customer::page()` は API レスポンスの `meta` から `Pagination` を生成し、`Page` に保持する。`meta` が完全な整数値で揃っている場合、`Page` の各 getter は従来どおり内部の `Pagination` に処理を委譲する。

`meta` キーが欠損しているか `null` の場合も、レスポンスの要素は `Page` に保持され、`foreach`、`count()`、`all()` で利用できる。ただしページング値を `0` などで代替はせず、`getTotal()`、`getLimit()`、`getOffset()` を呼ぶと `MissingPaginationException` が投げられる。例外メッセージには欠損したキーと対象エンドポイントが含まれる。

`meta` が存在する場合は `total`、`limit`、`offset` の各値が PHP の `int` でなければならない。空配列や一部キーが欠損した `meta` も生成自体は可能だが、欠損値の getter を呼ぶと `MissingPaginationException` が投げられる。存在する値が数値文字列、真偽値、浮動小数点数などの場合は、ページ生成時に `InvalidPaginationException` として早期に報告される。負数は API が返した整数値を改変せず保持する。

`Pagination` を直接生成する場合は `total`、`limit`、`offset` を指定する。

```php
use Shimoning\ColorMeShopApi\Entities\Pagination;

$pagination = new Pagination([
    'total' => 250,
    'limit' => 50,
    'offset' => 100,
]);

$pagination->getTotal();
$pagination->getLimit();
$pagination->getOffset();
```

-----

## 未実装

* [顧客データの追加](https://developer.shop-pro.jp/docs/colorme-api#tag/customer/operation/postCustomers)
* [商品](https://developer.shop-pro.jp/docs/colorme-api#tag/product)
* [在庫](https://developer.shop-pro.jp/docs/colorme-api#tag/stock)
* [ギフト](https://developer.shop-pro.jp/docs/colorme-api#tag/gift)
* [ショップクーポン](https://developer.shop-pro.jp/docs/colorme-api#tag/shop_coupon)
* [配送日時設定の取得](https://developer.shop-pro.jp/docs/colorme-api#tag/delivery/operation/getDeliveryDateSetting)

-----

## 開発者向け

リポジトリを `git clone` し、`composer install` を実行した後に以下のコマンドを利用できる。

```bash
# PHPUnit を実行
composer test

# PHPStan を実行
composer analyse

# composer.json の検証、PHPStan、PHPUnit をまとめて実行
composer check

# HTML のカバレッジレポートを coverage/ に出力
composer test:coverage

# 対話形式の API クライアントを起動
composer client
```

## CLI

単体で `git clone` して `composer install` を実行した場合、プロジェクト直下で対話形式の PHP クライアントを利用できる。

```bash
composer client
```

クラスを直接参照する場合は名前空間に注意すること。

### 終了方法
終了する際は `exit` もしくは `Control + C` を入力する。

### .env
プロジェクト直下に `.env` を作成すると、CLI 上で一部の変数が自動生成される。`.env.example` を参考に設定すること。これらの環境変数は CLI のみに利用される。

#### OAuth 用の環境変数

* `CLIENT_ID`
* `CLIENT_SECRET`
* `REDIRECT_URI`

設定すると `$oAuthOptions` を利用できる。`$oAuthScopes` はすべての権限を指定した状態で自動生成される。

#### アクセストークン

* `TOKEN`

設定すると `$token` と、アクセストークンを設定済みの `$client` が生成される。そのまま `$client->getShop()` のように利用できる。

-----

## ライセンスについて

当ライブラリは *MITライセンス* です。
[ライセンス](LICENSE) を読んでいただき、範囲内でご自由にご利用ください。

## サポート

### 有償サポート
サイトへの導入や込み入った組み込みなどでお困りの際は、有償にてサポートを承っております。

### 有償カスタマイズ
当ライブラリはオープンソースですが、有償にてカスタマイズを承っております。

カスタマイズしたコードは、同様にオープンソースとして公開されます。

### お問い合わせ
[GoogleForm](https://forms.gle/DK3DWstBCKdPS86X6) に必要事項をご記入の上送信してください。

件名につきましては「公開ライブラリに関するお問い合わせ」を選択してください。
