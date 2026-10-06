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
  * [商品](#商品)
  * [商品グループ](#商品グループ)
  * [商品カテゴリー](#商品カテゴリー)
  * [決済](#決済)
  * [配送](#配送)
  * [在庫](#在庫)
  * [ギフト](#ギフト)
  * [ページネーション](#ページネーション)
* [0.14.0 の変更](#0140-の変更)
* [未実装](#未実装)
* [開発者向け](#開発者向け)
* [CLI](#cli)
* [サンプル](#サンプル)
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
use Shimoning\ColorMeShopApi\Communicator\NoContent;
use Shimoning\ColorMeShopApi\Constants\AuthScope;
use Shimoning\ColorMeShopApi\Constants\MailType;
use Shimoning\ColorMeShopApi\Constants\PickupType;
use Shimoning\ColorMeShopApi\Constants\PointState;
use Shimoning\ColorMeShopApi\Entities\Customer\SearchParameters as CustomerSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\Stock\SearchParameters as StockSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Customer\CustomerCreateInput;
use Shimoning\ColorMeShopApi\Entities\Customer\CustomerUpdateInput;
use Shimoning\ColorMeShopApi\Entities\Customer\Points\PointsInput;
use Shimoning\ColorMeShopApi\Entities\OAuth\ErrorResponse as OAuthErrorResponse;
use Shimoning\ColorMeShopApi\Entities\OAuth\Options as OAuthOptions;
use Shimoning\ColorMeShopApi\Entities\Product\Advertising\SearchParameters as AdvertisingSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\Category\BigCategory;
use Shimoning\ColorMeShopApi\Entities\Product\Category\ChildInput;
use Shimoning\ColorMeShopApi\Entities\Product\Category\CategoryInput;
use Shimoning\ColorMeShopApi\Entities\Product\Group\GroupInput;
use Shimoning\ColorMeShopApi\Entities\Product\Option\OptionCreateInput;
use Shimoning\ColorMeShopApi\Entities\Product\Option\Value\ValueCreateInput;
use Shimoning\ColorMeShopApi\Entities\Product\Pickup\PickupInput;
use Shimoning\ColorMeShopApi\Entities\Product\ProductCreateInput;
use Shimoning\ColorMeShopApi\Entities\Product\ProductUpdateInput;
use Shimoning\ColorMeShopApi\Entities\Product\StocksIncrementInput;
use Shimoning\ColorMeShopApi\Entities\Product\VariantInput;
use Shimoning\ColorMeShopApi\Entities\Product\SearchParameters as ProductSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\Variant\VariantUpdateInput;
use Shimoning\ColorMeShopApi\Entities\Product\Variant\SearchParameters as VariantSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Sale\SaleCreateInput;
use Shimoning\ColorMeShopApi\Entities\Sale\CustomerCreateInput as SaleCustomerCreateInput;
use Shimoning\ColorMeShopApi\Entities\Sale\SaleUpdateInput;
use Shimoning\ColorMeShopApi\Entities\Sale\SearchParameters as SaleSearchParameters;
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
OAuth を除き、API が 2xx 以外のレスポンスを返した場合、各 API メソッドは `Communicator\Errors` を返す。`Errors` は `Collection` を継承しているため、`foreach` で個々の `Entities\Error` を取得できる。

```php
$result = $client->getShop();

if ($result instanceof Errors) {
    $response = $result->getResponse();
    $response->getStatus();  // HTTP ステータス
    $response->getRawBody(); // API の生レスポンス

    foreach ($result as $error) {
        $raw = $error->getRaw();

        // code / message / status は API 応答で欠損する可能性がある。
        $code = array_key_exists('code', $raw) ? $error->getCode() : null;
        $errorCode = array_key_exists('code', $raw) ? $error->getErrorCode() : null; // 未知なら ErrorCode::UNKNOWN
        // getCode() と配列化では元のコード文字列を保持する。
        $message = array_key_exists('message', $raw) ? $error->getMessage() : null;
        $status = array_key_exists('status', $raw) ? $error->getStatus() : null;

        // field は optional で、欠損時も null を返す。
        $error->getField();
    }
}
```

API が object 形状のエラー要素を返した場合、既知フィールドが空または一部不正でも要素自体は保持される。
不正な既知フィールドは `getRaw()` から除かれ、追加プロパティは `getRaw()` だけに保持される。
大文字小文字や末尾のアンダースコアが異なる追加プロパティも getter の値には影響しない。
`code`、`message`、`status` の欠損時に getter を直接呼ぶと、他の Entity と同様に
`Exceptions\MissingFieldException` が投げられるため、上記のように `getRaw()` で利用可能なフィールドを
確認するか、`MissingFieldException` を捕捉して扱うこと。

一方、アクセストークンの未指定や値オブジェクトの不正な入力など、リクエスト送信前に検出できる問題では `Exceptions\ParameterException` が投げられる。空文字のアクセストークンは、各 `Services\Service` の直接生成時に拒否され、公開 Service メソッドの引数で上書きした場合も HTTP リクエストの生成時に拒否される。空文字の判定は厳密に `""` のみで、`"0"` は有効なアクセストークンとして扱われる。ページネーション情報の欠損や不正には、それぞれ `Exceptions\MissingPaginationException`、`Exceptions\InvalidPaginationException` が投げられる。これらはすべて `Exceptions\ColorMeApiException` を継承しているため、ライブラリの例外をまとめて捕捉する場合は親クラスを利用できる。

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
OAuth のトークンエンドポイントは API 本体と異なるエラー形式を返すため、
[認可コードをアクセストークンに交換](#認可コードをアクセストークンに交換)も参照すること。

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
* `Values\Furigana`: `ァ`〜`ヶ`、長音符、半角・全角スペースと空文字を受け付ける (`^[ァ-ヶー 　]*$`)。公式 OpenAPI が許容する `ヷヸヹヺ` は、2026-09-25 の実 API で422拒否を確認したため除外している

`Values\Furigana` は `Values\FallbackValue` を実装しており、**応答側では検証に通らない値でも生の文字列を保持する**。管理画面から `ヷヸヹヺ` を入力した顧客は、API の応答で HTML 数値文字参照 (`&#12535;` など) を含む文字列を返すためである。この場合も顧客の取得と一覧取得は失敗せず、`isValid()` で妥当性を判定できる。

```php
$furigana = $customerOrErrors->getFurigana();
$furigana->get();      // 'テストカナ&#12535;&#12536;&#12537;&#12538;'
$furigana->isValid();  // false

// 必要なら利用者側でデコードする (ライブラリは API が返した値を書き換えない)
\html_entity_decode($furigana->get(), \ENT_QUOTES | \ENT_HTML5, 'UTF-8'); // 'テストカナヷヸヹヺ'
```

要求側では従来どおり検証され、検証に通らない値は拒否される。例外の型は経路によって異なり、値オブジェクトを直接構築した場合は `ParameterException`、`CustomerCreateInput` などの要求 Entity を経由した場合は `InvalidFieldException` に包まれる。
* `Values\Scopes`: `Constants\AuthScope` または定義済みスコープ文字列の配列を、OAuth 用のスペース区切り文字列へ変換する
* `Values\Limit`: 1 以上 100 以下の取得件数を受け付ける

`SaleSearchParameters` や `CustomerSearchParameters` のコンストラクタへ文字列や整数を渡した場合も、対応する値オブジェクトへ内部で変換される。不正な値には `ParameterException` が投げられる。

各 API に必要なスコープは、`Services` と `Client` の対応するメソッドの PHPDoc に記載している。公式 OpenAPI でスコープの宣言が空の 6 操作 (ショップ・決済・配送の取得、商品グループ一覧・単体、商品カテゴリー一覧) には記載がない。その一覧と推測は [docs/auth-scope-audit.md](docs/auth-scope-audit.md) にまとめている。

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

CSRF 対策の `state` を渡せる (0.18.0 以降)。値の生成と保存はライブラリの責務ではないため、利用者がセッション等に保存し、コールバックで照合する。

```php
session_start(); // フレームワークが開始済みなら不要

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$oAuthUri = (new Client())->getOAuthUrl($oAuthOptions, $oAuthScopes, $state);
```

省略すると `state` をクエリに含めない。空文字を渡した場合は `Exceptions\ParameterException` を投げる。

#### 認可コードをアクセストークンに交換
上記で取得した URL を開くと、ショップへのログインと認可を行う画面に移動する。認可後は `リダイレクトURI` へ遷移し、クエリ文字列の `code` に認可コードが設定される。

`state` を渡した場合は、`code` を扱う前に照合する。認可を拒否した場合のエラー応答 (`error=access_denied`) にも `state` は付くため、成功・失敗のどちらも照合してから内容を扱う。

```php
session_start(); // 認可 URL を組み立てたときと同じセッションを復元する

$expectedState = (string) ($_SESSION['oauth_state'] ?? '');
unset($_SESSION['oauth_state']);
$givenState = (string) filter_input(INPUT_GET, 'state');

if ($expectedState === '' || ! hash_equals($expectedState, $givenState)) {
    throw new \RuntimeException('state が一致しません。');
}
```

カラーミーは `state` の値を一度デコードして再エンコードして返すため、クエリ文字列のバイト列は送信時と一致しない。照合はデコード後の値 (`filter_input()` や `$_GET`) で行うこと。実測の詳細は [認可応答の `state` の実測記録](docs/api-oauth-state-observation.md) にある。

動く例は [examples/oauth-callback/](examples/oauth-callback/) にある。`composer oauth:callback` で起動する。

```php
$code = filter_input(INPUT_GET, 'code');
if (! is_string($code)) {
    throw new \RuntimeException('認可コードを取得できませんでした。');
}

$result = (new Client())->exchangeCode2Token($oAuthOptions, $code);
if ($result instanceof OAuthErrorResponse) {
    $result->getError();            // OAuth エラーコード (必須)
    $result->getErrorDescription(); // 人間が読める補足説明
    $result->getErrorUri();         // エラーの説明ページ

    $response = $result->getResponse();
    $response->getStatus();  // HTTP ステータス
    $response->getRawBody(); // OAuth の生レスポンス

    $result->getRaw();  // 追加プロパティを含む受信データ
    $result->toArray(); // RFC 6749 §5.2 の3フィールド (error / error_description / error_uri)
} elseif ($result instanceof Errors) {
    // OAuth エンドポイントが ColorMe API 本体の errors 配列形式を返した場合、
    // OAuth フィールドの型が不正な場合、未知の非 2xx 応答だった場合、
    // または 2xx でも空・非配列の不正な成功応答だった場合
    $response = $result->getResponse();
} else {
    $token = $result->getAccessToken();
}
```

OAuth 2.0 のエラーは `{"error":"invalid_client","error_description":"..."}` 形式であり、
ColorMe API 本体の `{"errors":[{"code":...,"message":...,"status":...}]}` 形式とは異なる。
前者は `Entities\OAuth\ErrorResponse`、後者は従来どおり `Communicator\Errors` で判定する。
`error_description` と `error_uri` は省略されることがあり、その場合は各 getter が `null` を返す。
OAuth の必須・任意フィールドが不正な型の場合や、既知の形式に一致しない非 2xx 応答の場合も
`Errors` にフォールバックする。この場合の `Errors` は空コレクションになるため、
`getResponse()->getStatus()` で HTTP ステータス、`getResponse()->getRawBody()` で生ボディを参照して調査する。

トークンエンドポイントのエラー応答 (RFC 6749 §5.2) は `state` を定義しておらず、実 API も返さない (2026-09-30 の観測。[トークンエラー応答の実測記録](docs/api-oauth-token-error-observation.md))。認可リクエストと応答を対応付ける `state` は、上記のとおりコールバックのクエリで受け取って照合するものであり、`ErrorResponse` から取得する値ではない。

0.19.0 で `ErrorResponse` から `state` プロパティを削除し、`toArray()` と `toArrayRecursive()` の出力から `state` キーを外した ([ADR 0021](docs/adr/0021-drop-state-from-token-error-response.md))。`getState()` は非推奨として残しており、応答に文字列の `state` があればそれを返し、なければ `null` を返す。次のメジャーな変更で削除する。応答に含まれた `state` は `getRaw()` から取得できる。

0.9.0 では `Client::exchangeCode2Token()` と `Services\OAuth::exchangeCode2Token()` の戻り値が
`AccessToken|Errors` から `AccessToken|ErrorResponse|Errors` へ変わるため、OAuth エラーを
`Errors` だけで判定していたコードには破壊的変更となる。

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
$searchParameters = new SaleSearchParameters([
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
    $statOrErrors->getDate();          // DateTimeImmutable。基準日の 00:00 (JST)
    $statOrErrors->getAmountToday();
    $statOrErrors->getCountToday();
    $statOrErrors->getAmountLast7days();
    $statOrErrors->getCountThisMonth();
}
```

`getDate()` は `statSales()` に渡した基準日の 00:00 (JST) を `DateTimeImmutable` で返す。API は unixtime で返すため、元の整数が必要なら `getRaw()` から取得できる。

集計は基準日を起点とした 3 つの固定期間に限られ、任意の日数範囲には対応していない。

| メソッド | 期間 |
| --- | --- |
| `getAmountToday()` / `getCountToday()` | 基準日の当日 |
| `getAmountLast7days()` / `getCountLast7days()` | 基準日の 7 日前から基準日の終わりまで (基準日を含む 8 日間) |
| `getAmountThisMonth()` / `getCountThisMonth()` | 基準日が属する月 |

`last7days` は名前に反して基準日とその前 7 日間の合計 8 日間である。2026-09-29 の実測で、基準日が受注日の 7 日後までは集計に含まれ 8 日後で外れることを確認した (公式 OpenAPI の説明と一致)。

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

#### 受注データの作成
カラーミーショップの**プレミアムプランでのみ**利用できる。それ以外のプランでは `401` (code `401200`「現在契約中のプランではご利用いただけません。」) の `Errors` が返る。2026-10-01 に必須項目を欠いた 2 種類の body で観測し、いずれも body の検証より先にこの `401` が返った。成功時の応答の形は公式 OpenAPI に基づいており、実 API では未観測である ([受注作成 API の実測記録](docs/api-sale-create-observation.md))。

```php
$input = new SaleCreateInput([
    'payment_id' => $paymentId,
    'details' => [
        ['product_id' => $productId, 'product_num' => 2],
    ],
    'sale_deliveries' => [
        [
            'delivery_id' => $deliveryId,
            'name' => '山田太郎',
            'furigana' => 'ヤマダタロウ',
            'postal' => '1508512',
            'pref_id' => 13,
            'address1' => '渋谷区桜丘町26-1',
            'tel' => '03-1234-1234',
        ],
    ],
    // 既存の顧客の受注にする場合。ゲスト購入なら氏名や住所などを配列で渡す
    'customer' => SaleCustomerCreateInput::existing($customerId),
]);

$saleOrErrors = $client->createSale($input); // 在庫を引き当てる (API の既定)
```

第 2 引数に `false` を渡すと、在庫を引き当てずに受注を作成する (`$client->createSale($input, false)`)。省略または `null` の場合は `reserve_stocks` を送らず、API の既定に従う。

`payment_id` と `details`、`details[]` の `product_id` / `product_num`、`sale_deliveries[]` の `delivery_id` / `name` / `furigana` / `postal` / `pref_id` / `address1` / `tel` は必須で、欠けていると送信前に `Exceptions\ParameterException` を投げる。`sale_deliveries` 自体は、配送不要の商品だけの受注では省略できるため必須にしていない。

入れ子 (`customer` / `sale_deliveries[]` / `details[]`) には、配列のほかに組み立て済みの入力インスタンスも渡せる。インスタンスは複製されず、そのまま保持される。

`customer` に既存の顧客 ID を指定し、その顧客が会員登録済みであれば、`id` 以外の顧客情報は API に無視される。顧客の `sex` は公式 OpenAPI の定義どおり `male` / `female` だけを受け付ける。

公式 OpenAPI はこの操作に認証の宣言 (`security`) を持たないが、実 API は認証を要求する。説明文に従い、必要なスコープは `write_sales` としている (スコープの要否は未検証)。

#### 受注データの更新
既存の受注から更新用エンティティを生成すると、API が必要とする現在値を引き継げる。
要求側 Entity は明示したフィールドだけを送信する。JSON ボディの入力 Entity はコンストラクタ配列で
明示した `null` も送信し、指定しなかったフィールドは送信しない。各種検索条件 Entity も未指定フィールドを
除外するが、GET クエリでは `http_build_query()` の仕様により明示した `null` も送信されない。
要求側 Entity の配列フィールド (受注の `details` / `sale_deliveries`、検索条件の `ids` など) には、キーが 0 から始まる連番の配列を渡す。`unset()` や絞り込みでキーが飛んだ配列は JSON オブジェクトとして送られてしまうため、生成時や setter での設定時に `InvalidFieldException` になる。`array_values()` を通してから渡すこと (0.22.0 以降、[ADR 0025](docs/adr/0025-reject-non-list-arrays-in-requests.md))。ただし入れ子の入力の配列 (`details` / `sale_deliveries` など) に、すべてのキーが文字列の連想配列を 1 つコンストラクタで渡した場合は、後方互換のため要素 1 件として扱う。setter (`SaleUpdateInput::setSaleDeliveries()` など) ではこの扱いはなく、連番の配列でなければ例外になる。また、商品オプションの作成の `values` (`Product\Option\OptionCreateInput`) は、個別の検証により文字列キーの配列も受け付けない。

```php
$saleOrErrors = $client->getSale($saleId);
if (! $saleOrErrors instanceof Errors) {
    $updater = SaleUpdateInput::convert($saleOrErrors);
    $updater->setPaid(true);
    $updater->setPointState(PointState::FIXED);

    $updatedSaleOrErrors = $client->updateSale($saleId, $updater);
}
```

受注 ID は、他の更新 API と同じく第 1 引数で渡す。`SaleUpdateInput` は受注 ID を持たず、body にも
含めない (0.20.0 以降)。公式 OpenAPI の要求 body に `id` は定義されていない。実測では、body の `id` の
有無や値 (存在しない ID を含む) で応答と結果に差はなく、path の受注として処理された。ただし観測は値を
変えない更新に限られる ([受注更新の body に含めた `id` の実測記録](docs/api-sale-update-id-observation.md))。

0.19.0 までは `$client->updateSale($updater)` のように入力だけを渡し、`SaleUpdateInput` が持つ `id` を
path と body の両方に使っていた。`SaleUpdateInput::getId()` は削除したため、受注 ID は取得した受注の
`getId()` などから渡す ([ADR 0022](docs/adr/0022-take-sale-id-as-update-argument.md))。

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
```php
$customerOrErrors = $client->createCustomer(new CustomerCreateInput([
    'name' => 'カラーミー太郎',
    'mail' => 'taro@example.com',
    'pref_id' => 13,
    'postal' => '1508512',
    'address1' => '渋谷区桜丘町26-1',
    'tel' => '03-5456-2622',
    // 任意
    'furigana' => 'カラーミータロウ',
    'sex' => 'male',
    'add_member' => true,
]));
```

`name` / `mail` / `pref_id` / `postal` / `address1` / `tel` は必須で、いずれかを指定しないと送信前に `ParameterException` が投げられる。`add_member` に `true` を指定すると会員として登録される。

`sex` は公式 OpenAPI の作成 request にないが、実 API では作成時にも反映されることを確認している (2026-09-25)。`tel_mobile` は作成・更新とも書き込めないため入力に持たない。

`Client::createCustomer()` は、内部で `Services\Customer::create(CustomerCreateInput $input, ?string $accessToken = null)` を呼び出す。必要な scope は `write_sales` で、顧客専用の scope は存在しない。

#### 顧客データを更新
```php
$customerOrErrors = $client->updateCustomer($customerId, new CustomerUpdateInput([
    'name' => 'カラーミー花子',
    'address1' => '渋谷区桜丘町26-1',
    'sex' => 'female',
    'fax' => null, // 明示した null はクリア要求として送信される
]));
```

明示したフィールドだけを送る部分更新で、省略したフィールドは変更されない。明示した `null` はクリア要求として送信される。

公式 OpenAPI の更新 request に required 指定はないが、実 API は `name` と `address1` を必須とするため (2026-09-25 の観測)、いずれかを指定しないと送信前に `ParameterException` が投げられる。`name` / `mail` / `pref_id` / `postal` / `address1` / `tel` は nullable ではないため、明示した `null` は `InvalidFieldException` で拒否される。

`Client::updateCustomer()` は、内部で `Services\Customer::update(int|string $id, CustomerUpdateInput $input, ?string $accessToken = null)` を呼び出す。

#### ショップポイントを増減
```php
$pointsOrErrors = $client->changeCustomerPoints($customerId, new PointsInput([
    'points' => 100, // 負の値で減算
]));

if ($pointsOrErrors instanceof Errors) {
    // エラー処理
} else {
    $pointsOrErrors->getCustomerId();
    $pointsOrErrors->getPoints(); // 増減後の保有ポイント数
}
```

この API の応答は他の顧客 API と異なり `customer` などのキーで包まれないため、専用の `Entities\Customer\Points\Points` を返す。保有ポイントを超える減算は API 側で `422` になる。

`Client::changeCustomerPoints()` は、内部で `Services\Customer::changePoints(int|string $id, PointsInput $input, ?string $accessToken = null)` を呼び出す。

### 商品
#### 商品一覧を取得
```php
$parameters = new ProductSearchParameters([
    'ids' => [101, 102],
    'group_ids' => [301, 302],
    'display_state' => 'showing',
    'limit' => 50,
    'offset' => 0,
]);
$productsOrErrors = $client->getProducts($parameters);

if ($productsOrErrors instanceof Errors) {
    // エラー処理
} else {
    foreach ($productsOrErrors as $product) {
        $product->getId();
        $product->getName();
        $product->getCategory()->getIdBig();
        $product->getDigitalContent();
        $product->getUnlisted();
    }
    $productsOrErrors->getTotal();
    $productsOrErrors->getLimit();
    $productsOrErrors->getOffset();
}
```

`ids` と `group_ids` は整数配列で指定し、クエリではカンマ区切りになる。商品一覧の `limit` は API 側で最大 50 件。
`fields` を `id,name` のように絞ると、応答には指定した商品フィールドだけが含まれる。
省略された nullable フィールドの getter は `null` を返し、非 nullable フィールドの getter は `MissingFieldException` を投げる。

#### 商品単体を取得
```php
$productOrErrors = $client->getProduct(101);
if ($productOrErrors instanceof Errors) {
    // エラー処理
} else {
    $productOrErrors->getName();
    $productOrErrors->getImages(); // 商品本体の追加画像
    $productOrErrors->getMakeDate(); // DateTimeImmutable
}
```

#### バリエーション一覧と単体を取得
```php
$variantParameters = new VariantSearchParameters([
    'model_number' => 'TEST',
    'fields' => 'id,title,option1,option2',
    'limit' => 10,
    'offset' => 0,
]);
$variantsOrErrors = $client->getProductVariants(101, $variantParameters);
if (! $variantsOrErrors instanceof Errors) {
    foreach ($variantsOrErrors as $variant) {
        $variant->getTitle();
        $variant->getOption1();
        $variant->getOption2(); // 1軸の場合は null
    }
}

$variantOrErrors = $client->getProductVariant(101, 301);
if (! $variantOrErrors instanceof Errors) {
    $variantOrErrors->getId();
}
```

バリエーション一覧の既定 `limit` は 10 件。検索条件の `model_number` は型番の部分一致検索、`fields` は応答フィールドのカンマ区切り指定に使う。

#### 画像と商品広告を取得
```php
$imagesOrErrors = $client->getProductImages(101);
if (! $imagesOrErrors instanceof Errors) {
    foreach ($imagesOrErrors as $image) {
        $image->getUrl();
        $image->getPosition();
    }
}

$advertisingParameters = new AdvertisingSearchParameters([
    'product_ids' => [101, 102],
    'display_state' => 'showing',
    'limit' => 25,
    'offset' => 50,
]);
$advertisingsOrErrors = $client->getProductAdvertisings($advertisingParameters);
if (! $advertisingsOrErrors instanceof Errors) {
    foreach ($advertisingsOrErrors as $advertising) {
        $advertising->getProductId();
        $advertising->getColors();
    }
    $advertisingsOrErrors->getTotal();
    $advertisingsOrErrors->getLimit();
    $advertisingsOrErrors->getOffset();
}
```

`product_ids` は整数配列で指定し、クエリではカンマ区切りになる。広告一覧の既定 `limit` は 50 件、OpenAPI 上の最大値は 250 件。

画像専用 GET の要素は `url` と `position` を持つ `Product\Image\Image`。商品本体の `images` 要素 (`src` / `mobile` / `position`) とは別構造。

#### 商品グループ単体を取得
```php
$groupOrErrors = $client->getProductGroup(401);
if (! $groupOrErrors instanceof Errors) {
    $groupOrErrors->getId();
    $groupOrErrors->getName();
}
```

#### 商品を作成・更新
作成は `ProductCreateInput`、更新は `ProductUpdateInput` を使う。指定したフィールドだけを送信するため、更新は部分更新として動作する。
`display_state` は `showing` / `hidden` / `showing_for_members` / `sale_for_members` (`ProductDisplayState` の値、または同 enum のインスタンス) だけを受け付け、
`members_only` は生成時に `InvalidFieldException` になる。読み取り専用の `unlisted` は入力に含められない。

```php
$createdOrErrors = $client->createProduct(new ProductCreateInput([
    'name' => 'Tシャツ',
    'sales_price' => 1500,
    'display_state' => 'hidden',
    'stock_managed' => true,
]));
if (! $createdOrErrors instanceof Errors) {
    $productId = $createdOrErrors->getId();
}

// 明示した null は「未設定へ戻す」要求として送信される (例: 販売価格のクリア)
$updatedOrErrors = $client->updateProduct($productId, new ProductUpdateInput([
    'sales_price' => null,
    'stocks' => ['increment' => 5], // 整数の絶対値、または increment object
    'variants' => [
        ['option1_value' => 'S', 'option2_value' => '赤', 'stocks' => 3],
    ],
]));
if ($updatedOrErrors instanceof Errors) {
    // 存在しない商品は 404、不正な値は 422
}
```

`category_id_small` / `stocks` / `group_ids` / `variants` は更新専用のフィールドで、`ProductUpdateInput` だけが持つ。公式 OpenAPI の作成 request にはこの 4 項目がなく、実 API は作成時に送っても反映せず、エラーにもしない (2026-10-02 の観測、[ColorMe Shop API 商品応答構造の実測記録](docs/api-product-structure.md))。作成した商品の在庫数やグループは、作成後に `updateProduct()` で設定する。

`stocks` は整数 (在庫数の絶対値) か、`{"increment": n}` (増減) のどちらかを渡す。増減は `StocksIncrementInput`、`variants` の要素は `VariantInput` で表しており、配列のほかに組み立て済みのインスタンスも渡せる (例: `'stocks' => new StocksIncrementInput(['increment' => 5])`、`'variants' => [new VariantInput(['option1_value' => 'S', 'stocks' => 3])]`)。`increment` 以外のキーや、`variants` の要素の定義にないキー・空の要素・`stocks` の `null` は `InvalidFieldException` になる ([ADR 0027](docs/adr/0027-model-product-stocks-and-variants-as-entities.md))。

0.22.0 で、作成と更新で共用していた `ProductInput` を `ProductCreateInput` と `ProductUpdateInput` に分け、`ProductInput` は削除した ([ADR 0026](docs/adr/0026-split-product-input-into-create-and-update.md))。更新の呼び出しは `new ProductInput(` を `new ProductUpdateInput(` に、作成の呼び出しは `new ProductCreateInput(` に書き換える。作成で更新専用の 4 項目を指定していた場合、その指定はもともと反映されていなかった。
商品自体を削除する API は公式に存在しない。

#### バリエーションを更新
```php
$variantOrErrors = $client->updateProductVariant($productId, 301, new VariantUpdateInput([
    'stocks' => 10,
    'option_price' => 1600,
    'weight' => null, // 明示した null で未設定へ戻す
]));
if (! $variantOrErrors instanceof Errors) {
    $variantOrErrors->getStocks();
}
```

#### オプションとオプション値を作成・削除
オプションの `name` と `values` は必須で、`null` は受け付けない。削除は成功時に `204` を返すため、結果は `NoContent` になる。

```php
$optionOrErrors = $client->createProductOption($productId, new OptionCreateInput([
    'name' => 'サイズ',
    'values' => [['name' => 'S'], ['name' => 'M']],
]));
if (! $optionOrErrors instanceof Errors) {
    $optionId = $optionOrErrors->getId();
    $optionOrErrors->getValues(); // ['S', 'M']
}

$valueOrErrors = $client->createProductOptionValue($productId, $optionId, new ValueCreateInput(['name' => 'L']));
if (! $valueOrErrors instanceof Errors) {
    $valueId = $valueOrErrors->getValueId();
}

$deletedOrErrors = $client->deleteProductOptionValue($productId, $optionId, $valueId);
if ($deletedOrErrors instanceof NoContent) {
    $deletedOrErrors->getResponse()->getStatus(); // 204
}

$client->deleteProductOption($productId, $optionId); // NoContent|Errors
```

オプションを値 1 件で作成するとバリエーションが生成され、最後のオプション値を直接削除すると `422` になる。
その場合は親のオプションを削除する。

#### おすすめ商品情報 (ピックアップ) を作成・更新・削除
`pickup_type` は `PickupType` の値 (`0` おすすめ / `1` 売れ筋 / `3` 新着 / `4` イチオシ) または `PickupType` のインスタンスで指定する。
削除だけは他の DELETE と異なり `200` で削除済みの `Pickup` を返す。削除の種別に `PickupType` にない値を渡すと送信前に `ParameterException` になる。

```php
$pickupOrErrors = $client->createProductPickup($productId, new PickupInput([
    'pickup_type' => PickupType::NEW_ARRIVAL, // enum インスタンスは値 (3) として送信される
    'order_num' => 1,
]));
if (! $pickupOrErrors instanceof Errors) {
    $pickupOrErrors->getPickupType();
    $pickupOrErrors->getProductId(); // 書き込み応答にだけ含まれる
}

$client->updateProductPickup($productId, new PickupInput([
    'pickup_type' => PickupType::NEW_ARRIVAL->value,
    'order_num' => 2,
]));

$deletedPickupOrErrors = $client->deleteProductPickup($productId, PickupType::NEW_ARRIVAL);
if (! $deletedPickupOrErrors instanceof Errors) {
    $deletedPickupOrErrors->getPickupType(); // 削除した pickup の内容
}
```

#### 商品画像を作成・削除
画像は `multipart/form-data` で送信する。第 2 引数はファイルパスまたは読み取り可能なストリーム、第 3 引数の `position` は `0`〜`49`。
読み取れないファイルは送信前に `ParameterException` になる。
送信するファイル名は省略するとパスの末尾になる。ストリームでは `memory` のように拡張子が付かないため、第 5 引数で拡張子付きの名前を指定する。

```php
$imageOrErrors = $client->createProductImage($productId, '/path/to/image.png', 0);
if (! $imageOrErrors instanceof Errors) {
    $imageOrErrors->getUrl();
    $imageOrErrors->getPosition();
}

$client->createProductImage($productId, $stream, 1, null, 'image.png'); // ストリームにはファイル名を指定

$client->deleteProductImage($productId, 0); // NoContent|Errors
```

**注意**: 画像の作成・削除は契約プランの制限により実 API で成功を検証できていない (実測は `401`)。
成功時の `201` と応答形 (`position` / `url`) は公式 OpenAPI 定義に基づく。

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

#### 商品グループを作成・更新
作成と更新は同じ `GroupInput` を使う。指定したフィールドだけを送信するため、更新は部分更新として動作し、明示した `null` は「未設定へ戻す」要求として送信される。
`parent_group_id` は作成専用で、どの操作でどのフィールドが有効かは公式 API の契約に従う。`meta_tag` はネストした連想配列 (`title` / `keywords` / `description`) で指定し、これらのキーを1つも持たない配列や、これら以外のキーを含む配列は生成時に `InvalidFieldException` になる。

```php
$groupOrErrors = $client->createProductGroup(new GroupInput([
    'name' => '夏物',
    'expl' => '暑い夏を涼しく乗り切る夏物衣類',
    'display_state' => 'showing',
    'parent_group_id' => null, // 特定のグループ配下に作らない場合は null
    'meta_tag' => ['title' => '夏物特集', 'keywords' => '夏物,衣類'],
]));
if (! $groupOrErrors instanceof Errors) {
    $groupId = $groupOrErrors->getId();
}

$updatedOrErrors = $client->updateProductGroup($groupId, new GroupInput([
    'display_state' => 'hidden',
    'expl' => null, // 明示した null で説明をクリア
]));
if ($updatedOrErrors instanceof Errors) {
    // 存在しないグループは 404、不正な値は 422、空の入力は 422
}
```

実 API の観測 (2026-09-21) では、グループの `meta_tag` は初回設定だけが永続化され、以後の更新 (一部キーのみ、全キー `null`、`meta_tag` 自体の `null` を含む) は応答には反映されるが GET では初回設定の値のままだった (API 側の挙動と考えられ、未解決)。詳細は [商品 API 応答構造の実測記録](docs/api-product-structure.md#2026-09-21-の追加観測グループカテゴリーの書き込み-smoke-test)。

`display_state` は `showing` / `hidden` / `members_only` (`GroupInput::WRITABLE_DISPLAY_STATES`。`GroupDisplayState` の値、または同 enum のインスタンス) を受け付け、`showing_for_members` / `sale_for_members` は生成時に `InvalidFieldException` になる (実 API の PUT でも 422)。
応答の `Group::getDisplayState()` は `GroupDisplayState` を返す (0.13.0 で `ProductDisplayState` から変更。実 API が返す `members_only` を読めるようにするため)。`GroupDisplayState` は実測の 3 値に加え、公式 OpenAPI の `productGroup` response 定義にある `showing_for_members` / `sale_for_members` も応答の受理のみを目的として持つ (管理画面等で設定された既存グループが返す可能性を否定できないため。読み取りでは未観測)。

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
        $metaTag = $category->getMetaTag();
        if ($metaTag !== null) {
            $metaTag->getTitle();
            $metaTag->getKeywords();
            $metaTag->getDescription();
        }
        if ($category instanceof BigCategory) {
            foreach ($category->getChildren() as $child) {
                $child->getName();
            }
        }
    }
}
```

`Client::getProductCategories()` は、内部で `Services\Product::categories(?string $accessToken = null)` を呼び出す。
`meta_tag` が省略または `null` の場合、`Category::getMetaTag()` は `null` を返す。

#### 商品カテゴリーを作成・更新
大カテゴリーは `CategoryInput`、小カテゴリーは `ChildInput` を使い、それぞれ作成と更新で共用する。両者は同じフィールド (`name` / `expl` / `sort` / `display_state` / `meta_tag`) を持つが、応答の `BigCategory` / `SmallCategory` に合わせた別の型で互いに代入できない。
指定したフィールドだけを送信するため、更新は部分更新として動作し、明示した `null` はそのまま送信される。ただし実 API の観測 (2026-09-21) では、カテゴリーの `expl` は `null` を送っても旧値のまま残り、空文字 `''` は保存された (説明を消すには空文字を送る)。`meta_tag` の部分更新はマージではなく置換で、送らなかったキーは `null` になる。作成では公式 API が `name` を必須とするが、ライブラリは送信前に検証せず API の `422` に委ねる。
`display_state` は `showing` / `hidden` / `members_only` (`CategoryDisplayState` の値、または同 enum のインスタンス) を受け付ける。

```php
$categoryOrErrors = $client->createProductCategory(new CategoryInput([
    'name' => 'Tシャツ',
    'sort' => 1,
    'display_state' => 'showing',
    'meta_tag' => ['title' => 'Tシャツ一覧', 'description' => '高品質なTシャツを取り揃えています'],
]));
if (! $categoryOrErrors instanceof Errors) {
    $categoryId = $categoryOrErrors->getIdBig(); // BigCategory
}

$childOrErrors = $client->createProductCategoryChild($categoryId, new ChildInput([
    'name' => '半袖',
]));
if (! $childOrErrors instanceof Errors) {
    $childId = $childOrErrors->getIdSmall(); // SmallCategory
}

$client->updateProductCategory($categoryId, new CategoryInput(['expl' => ''])); // BigCategory|Errors。説明を消すには空文字を送る (null はクリアされない)
$client->updateProductCategoryChild($categoryId, $childId, new ChildInput(['display_state' => 'hidden'])); // SmallCategory|Errors
```

応答の `category` は `Category::fromArray()` で変換し、大カテゴリーの操作で `id_small` が `0` 以外の応答が返るなど期待した親子の型でない場合は `InvalidFieldException` になる。

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
```php
$settingOrErrors = $client->getDeliveryDateSetting();

if ($settingOrErrors instanceof Errors) {
    // エラー処理
} else {
    $days = $settingOrErrors->getDays();
    $days->getEnabled();   // 配送希望日選択が有効か
    $days->getMin();       // 選択できる最も早い配送日 (注文日から n 日後)
    $days->getMax();       // 選択できる最も遅い配送日 (注文日から n 日後)
    $days->getDefault();   // 既定で選択される希望日。未設定なら null
    $days->getComment();

    $times = $settingOrErrors->getTimes();
    $times->getEnabled();  // 配送時間帯選択が有効か
    $times->getPeriods();  // 時間帯の選択肢 (list<string>)
    $times->getComment();

    $settingOrErrors->getUpdateDate(); // ?DateTimeImmutable
}
```

`days` と `times` は `Entities\Delivery\Date\Days` と `Entities\Delivery\Date\Times` として返る。`enabled` / `default` / `comment` / `make_date` / `update_date` は公式 OpenAPI で nullable のため `null` を返しうる。

`Client::getDeliveryDateSetting()` は、内部で `Services\Delivery::dateSetting(?string $accessToken = null)` を呼び出す。

### 在庫
#### 在庫情報を検索
```php
$stocksOrErrors = $client->getStocks(new StockSearchParameters([
    'stocks' => 5,                 // 在庫数が 5 以下
    'recent_zero_stocks' => true,  // 過去 1 週間以内にオプションが更新された商品
    'display_state' => 'showing',
    'limit' => 50,
]));

// 検索条件を省略する場合
$stocksOrErrors = $client->getStocks();

if ($stocksOrErrors instanceof Errors) {
    // エラー処理
} else {
    foreach ($stocksOrErrors as $stock) {
        $stock->getProductId();
        $stock->getName();
        $stock->getOption1Value();  // オプションの値。公式仕様上はオプションごとの在庫が別行になるが、実測は未確認
        $stock->getStocks();        // 在庫数。未設定なら null
        $stock->getFewNum();        // 残りわずかとなる在庫数
        $stock->getCategory();      // Product\CategoryIds
        $stock->getDisplayState();  // ProductDisplayState
    }

    $stocksOrErrors->getTotal();
    $stocksOrErrors->getLimit();
    $stocksOrErrors->getOffset();
}
```

在庫 API は商品検索専用のパラメータや不正な `display_state` を渡しても**エラーにせず黙って無視する**ため、`Product\SearchParameters` は流用せず、在庫 API が受け付ける 11 パラメータ（`ids` / `category_id_big` / `category_id_small` / `model_number` / `name` / `display_state` / `stocks` / `recent_zero_stocks` / `fields` / `limit` / `offset`）だけを持つ `Product\Stock\SearchParameters` を使う。`limit` の上限は 50 で、超える値を指定すると API 側で 50 に丸められる。`fields` で応答のキーを絞った場合、除外した非 null フィールドの getter は商品 API と同じく `MissingFieldException` を投げる。

`category` と `images` は商品 API と同じ形のため、`Product\CategoryIds` と `Product\Image` を返す。実測の詳細は [docs/api-stock-structure.md](docs/api-stock-structure.md) にある。

`Client::getStocks()` は、内部で `Services\Product::stocks(SearchParameters $parameters, ?string $accessToken = null)` を呼び出す。`Services\Stock` は 0.25.0 で非推奨にした。`Services\Stock::page()` は `Services\Product::stocks()` に委譲しており、次のメジャーな変更で削除する ([ADR 0032](docs/adr/0032-merge-stock-service-into-product-service.md))。
### ギフト
#### ギフト設定を取得
```php
$giftOrErrors = $client->getGift();

if ($giftOrErrors instanceof Errors) {
    // エラー処理
} else {
    $giftOrErrors->getEnabled();          // ギフト機能が有効か。公式 OpenAPI では nullable

    $noshi = $giftOrErrors->getNoshi();   // Gift\Noshi
    $noshi->getEnabled();
    $noshi->getTextEnabled();            // 名入れの可否
    $noshi->getTextCharge();             // 名入れの料金
    foreach ($noshi->getTypes() as $type) {   // list<Gift\Type>
        $type->getName();
        $type->getCharge();
    }
    $noshi->getComment();

    $giftOrErrors->getCard();             // Gift\Card: enabled / textEnabled / types / comment
    $giftOrErrors->getWrapping();         // Gift\Wrapping: enabled / types / comment
    $giftOrErrors->getUpdateDate();       // ?DateTimeImmutable
}
```

熨斗・メッセージカード・ラッピングは公式 OpenAPI 上で持つフィールドが異なる（カードに `text_charge` はなく、ラッピングに `text_enabled` / `text_charge` はない）ため、`Noshi` / `Card` / `Wrapping` の別々の Entity で表す。`enabled` / `text_enabled` / `text_charge` / `comment` / `make_date` / `update_date` は公式 OpenAPI で nullable のため `null` を返しうる。実測の詳細は [docs/api-gift-structure.md](docs/api-gift-structure.md) にある。

`Client::getGift()` は、内部で `Services\Gift::get(?string $accessToken = null)` を呼び出す。

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

`meta` キーが欠損している場合も、レスポンスの要素は `Page` に保持され、`foreach`、`count()`、`all()`、配列アクセスで利用できる。ただしページング値を `0` などで代替はせず、`getTotal()`、`getLimit()`、`getOffset()` を呼ぶと `MissingPaginationException` が投げられる。例外メッセージには欠損したキーと対象エンドポイントが含まれる。

`meta` が存在する場合は配列であり、`total`、`limit`、`offset` の各値が PHP の `int` でなければならない。空配列や一部キーが欠損した `meta` も生成自体は可能だが、欠損値の getter を呼ぶと `MissingPaginationException` が投げられる。`meta` 自体が `null`、または存在する値が `null`、数値文字列、真偽値、浮動小数点数などの場合は、ページ生成時に `InvalidPaginationException` として早期に報告される。負数は API が返した整数値を改変せず保持する。

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

## 0.14.0 の変更

### 要求側入力クラスの改名

書き込み入力クラスの命名を `<対象><操作>Input` に統一した ([ADR 0016](docs/adr/0016-unify-request-input-entity-names.md))。旧クラス名は非推奨の別名として残していたが、0.24.0 で削除した ([ADR 0030](docs/adr/0030-place-entities-by-api-path.md))。旧名と新名の対応は[非推奨のクラス名の対応表](docs/class-aliases.md)にある。

### フリガナの検証

`Values\Furigana` の許容文字を `^[ァ-ヶー 　]*$` とし、空文字を受け付けるようにした。従来は空文字を拒否していた。公式 OpenAPI が許容する `ヷヸヹヺ` は実 API が 422 で拒否するため含めない (2026-09-25 の観測)。

### 応答側の値オブジェクトのフォールバック

`Values\FallbackValue` を導入し、`Values\Furigana` に実装した ([ADR 0017](docs/adr/0017-opt-in-value-fallback.md))。応答側では検証に通らない値でも生の文字列を保持するため、管理画面から入力された数値文字参照を含むフリガナを持つ顧客でも、単体取得と一覧取得が失敗しなくなる。0.13.0 以前は `InvalidFieldException` になっていた。

要求側の厳格さは変わらない。フォールバックは `FallbackValue` を実装した値オブジェクトにだけ適用され、`Values\DateTime` や `Values\Limit` の挙動は変わらない。

-----

## 未実装

* [ショップクーポン](https://developer.shop-pro.jp/docs/colorme-api#tag/shop_coupon)

-----

## 開発者向け

ブランチ名、コミット規約、プルリクエストの手順、リリース手順は [CONTRIBUTING.md](CONTRIBUTING.md) にまとめている。

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

# OAuth のコールバックを受け取るサンプルサーバーを起動
composer oauth:callback
```

## CLI

単体で `git clone` して `composer install` を実行した場合、プロジェクト直下で対話形式の PHP クライアントを利用できる。

```bash
composer client
```

クラスを直接参照する場合は名前空間に注意すること。

### 終了方法
終了する際は `exit` もしくは `Control + C` を入力する。

## サンプル

`examples/` に、手元で API の挙動を確認するためのサンプルを置いている。

### OAuth のコールバックを受け取る

`examples/oauth-callback/` は、認可コードを `localhost` で受け取ってトークン応答の構造を表示するサンプルである。認可コードは 1 度しか交換できず、ブラウザでの承認を挟むため、通常のスクリプトではトークン応答を観測しにくい。その確認を手元で行える。

```bash
composer oauth:callback
```

1. カラーミーのアプリ設定に `http://localhost:8765/callback` を登録する (完全一致)
2. プロジェクト直下の `.env` に `CLIENT_ID` と `CLIENT_SECRET` を設定する
3. 上記のコマンドを実行し、ブラウザで `http://localhost:8765/` を開いて承認する

要求スコープは環境変数 `OAUTH_CALLBACK_SCOPES` で変更できる (既定は `read_products,read_sales`)。値は `Constants\AuthScope` で検証し、未定義のスコープは起動時に知らせる。

待ち受けポートは `composer.json` の `oauth:callback` で 8765 に固定している。変更する場合は、そのスクリプトとカラーミーに登録するリダイレクト URI の両方を直すこと。

CSRF 対策として `state` を検証する。サンプルは `state` を生成してセッションに保存したうえで `Services\OAuth::getUrl()` に渡し、コールバックで照合してから認可コードを交換する。

`CLIENT_SECRET` は画面に出さず、`access_token` も先頭 4 文字と長さだけを表示する。発行したアクセストークンはカラーミー側で有効なまま残るため、不要であれば [許可済みアプリ一覧](https://admin.shop-pro.jp/?mode=app_use_lst) から失効させること。

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
