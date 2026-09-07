# 自動テスト導入計画

Color Me Shop API クライアントライブラリへの自動テスト導入計画と進捗管理。

- **起票日**: 2026-09-07
- **対象ブランチ**: `shimonhaga/cownose`
- **ステータス凡例**: `[ ]` 未着手 / `[~]` 進行中 / `[x]` 完了 / `[-]` 見送り

---

## 1. 背景と現状

| 項目 | 状況 |
| --- | --- |
| テストケース | 0件（`tests/` ディレクトリなし） |
| ソース規模 | 70ファイル / 約5,900行 |
| ランタイム | PHP `^8.1` |
| 依存 | `guzzlehttp/guzzle ^7.2` |
| CI | なし |
| 静的解析 | なし |

## 2. テスト容易性における構造的課題

導入にあたって把握しておくべき既存実装の制約。

| # | 箇所 | 内容 | 影響 |
| --- | --- | --- | --- |
| A | `src/Services/*.php` | 各メソッド内で `new Request(...)` を直接生成 | HTTP をモックできず Service 層をテスト不能 → **Phase 3 で解消** |
| B | `src/Communicator/Request.php:11` | コンストラクタ内で `new Client()`（Guzzle）を生成 | ハンドラを差し替えられない → **Phase 3 で解消** |
| C | `src/Client.php:181` `salesService()` | `static $service` がメソッドスコープ static 変数のためインスタンス間で共有される | 別トークンで2つ目の `Client` を生成しても最初の Service が再利用される既存バグ → **Phase 4 で修正** |

一方、**Values / Entities / Collection / RequestOptions / Response 層はリファクタ不要でテスト可能**。
特に `Response` は `Psr\Http\Message\ResponseInterface` を受け取る設計のため、`GuzzleHttp\Psr7\Response` を直接渡せる。

## 3. 方針

- 既存の動作するコードは**理由なくリファクタしない**。Phase 3 のリファクタは「引数追加のみの後方互換な変更」に限定する。
- 既存コードに対しては**仕様化テスト（characterization test）を先に書いてからリファクタ**する（Martin Fowler の手順）。
- 新規に振る舞いを足す場合は Red-Green-Refactor（t_wada 推奨の進め方）に従う。
- テストは**実 API に接続しない**。`.env` も参照しない。レスポンスは `tests/Fixtures/*.json` に固定データとして置く。

---

## 4. フェーズ別計画

### Phase 0: テスト基盤の構築

リファクタなし。テストを書き始められる状態にする。

- [x] `phpunit/phpunit:^10.5` を `require-dev` に追加（PHP 8.1 対応の最新メジャー。11 系は PHP 8.2 以上が必要）
- [x] `phpunit.xml` を作成
- [x] `tests/` ディレクトリを作成
- [x] `composer.json` の `autoload-dev` に `Shimoning\ColorMeShopApi\Tests\` を PSR-4 登録
- [x] `composer test` / `composer test:coverage` スクリプトを追加
- [x] `.gitignore` に `.phpunit.cache/` / `coverage/` を追加
- [x] フィクスチャ読み込みヘルパの基底クラス `tests/TestCase.php` を追加

### Phase 1: 純粋ユニットテスト（リファクタ不要・最優先）

副作用ゼロで即座に書ける層。ここだけで全体カバレッジが大きく上がる。

- [x] **Values/DateTime** — `YYYY-MM-DD` / `YYYY-MM-DD hh:mm:ss` 形式の受理、不正形式で `ParameterException`、`DateTimeInterface` からの変換
- [x] **Values/Furigana** — カタカナ・長音・全角/半角スペースの受理、ひらがな/漢字/空文字の拒否
- [x] **Values/Limit** — 境界値 `0` / `1` / `100` / `101`
- [x] **Values/Scopes** — `AuthScope` と文字列の混在、スペース区切りでの連結、不正値で例外
- [x] **Entities/Entity** — snake_case→camelCase 変換、未定義プロパティの無視、`OBJECT_FIELDS` の `entity` / `value` / `enum` / `array` / `nullable` 各分岐、連想配列が渡された場合の単一要素化、`isHash()`、`toArray()`、`toArrayRecursive()` の `$ignoreNull`、`getRaw()`
- [x] **Entities/Collection** — `cast()`、`ArrayAccess`（get/set/isset/unset、null オフセットの push）、`IteratorAggregate`、`count()`、`Collection` を渡した場合のコピー
- [x] **Entities/Page / Pagination** — `getTotal()` / `getLimit()` / `getOffset()` の委譲
- [x] **Communicator/RequestOptions** — `Bearer ` の自動付与と二重付与の回避、`form` / `json` フラグ、タイムアウトのキャスト、デフォルト値
- [x] **Communicator/RequestMeta** — アクセサ
- [x] **Constants** — 全 enum 横断の契約テスト（`BackedEnum` であること・値の重複なし・`from`/`tryFrom` の往復・`name()` の `match` 網羅）と、API 仕様に直結する enum の値の固定
- [x] **Exceptions** — 継承関係（`ParameterException` が `ColorMeApiException` を継承し `\Exception` を継承）

### Phase 2: Response / Errors

PSR-7 の実オブジェクトを渡すだけでテストできるため、リファクタ不要。

- [x] **Communicator/Response** — JSON パース、不正 JSON 時に `getParsedBody()` が `null`、`isSuccess()` の境界（`199` / `200` / `299` / `300`）、生ヘッダ・生ボディ、`RequestMeta` の保持
- [x] **Communicator/Errors** — `build()` によるエラー配列→`Error` エンティティ変換、`errors` キー欠落時に空コレクション、`getResponse()`

### Phase 3: HTTP 層（要リファクタ）

課題 A / B を解消する。**公開シグネチャは壊さず、任意引数の追加のみ**。

```php
// Communicator/Request
public function __construct(?RequestOptions $options = null, ?ClientInterface $client = null)

// Services/*
public function __construct(string $accessToken, ?ClientInterface $httpClient = null)

// Services/OAuth
public function __construct(Options $options, ?ClientInterface $httpClient = null)
```

> **当初案からの変更**: 計画時は Service に `?Request $request = null` を注入する想定だったが、
> `RequestOptions`（`authorization` や `json` フラグ）はメソッド呼び出しごとに組み立てられるため、
> 単一の `Request` インスタンスを注入する設計は成立しない。
> 代わりに Guzzle の `ClientInterface` を Service に持たせ、各 `new Request(...)` に渡す形にした。
> 呼び出し箇所の差分は 1 引数の追加のみで済んでいる。

- [x] リファクタ前に後方互換性の契約を固定するテストを設置（`tests/BackwardCompatibilityTest.php`）
- [x] `Request` に Guzzle `ClientInterface` を任意注入可能にする
- [x] 各 `Services/*` に `ClientInterface` を任意注入可能にする
- [x] `MockHandler` + History ミドルウェアのテストヘルパーを追加（`tests/Support/HttpMock.php`）
- [x] `Communicator/Request` — メソッド / URL / クエリ / ヘッダ / ボディ形式（json・form）/ `RequestMeta`
- [x] `Services/OAuth` — `getUrl()` のクエリ生成（RFC3986）、`exchangeCode2Token()`
- [x] `Services/Shop`
- [x] `Services/Sales` — `page` / `one` / `stat` / `update` / `cancel` / `sendMail`
- [x] `Services/Payment`
- [x] `Services/Delivery`
- [x] `Services/Customer`
- [x] `Services/Product`
- [x] 4xx / 5xx 時に `Errors` が返ることの検証

### Phase 4: Client

Phase 3 と同じ方針で、`Client` にも `?ClientInterface $httpClient` を任意注入できるようにしてからテストする。

```php
// Client
public function __construct(?string $accessToken = null, ?ClientInterface $httpClient = null)
```

- [x] `Client` に `ClientInterface` を任意注入可能にし、生成する各 Service に伝播させる
- [x] `Client::salesService()` の `static $service` バグ（課題 C）の修正とリグレッションテスト
- [x] `Client::getShop()` にアクセストークンのガードが欠落していた不具合の修正
- [x] アクセストークン未指定時に `ParameterException` が投げられること
- [x] 各メソッドが対応する Service に正しく委譲すること（全15メソッド）
- [x] 引数で渡したアクセストークンがインスタンスの値を上書きし、以降の呼び出しにも引き継がれること
- [x] エラーレスポンスが `Errors` として返ること

### Phase 5: CI・静的解析

- [ ] GitHub Actions ワークフロー（PHP 8.1 / 8.2 / 8.3 / 8.4 マトリクス）
- [ ] `composer validate --strict`
- [ ] PHPStan 導入（level 5 から開始し段階的に引き上げ）
- [ ] カバレッジ計測（Xdebug または PCOV）

---

## 5. ディレクトリ構成

```
tests/
├── Fixtures/            # API レスポンスの固定 JSON
├── Communicator/
├── Constants/
├── Entities/
├── Exceptions/
├── Services/            # Phase 3 以降
└── Values/
```

## 6. 進捗サマリ

| Phase | 内容 | 状態 |
| --- | --- | --- |
| 0 | テスト基盤の構築 | **完了** (2026-09-07) |
| 1 | 純粋ユニットテスト | **完了** (2026-09-07) |
| 2 | Response / Errors | **完了** (2026-09-07) |
| 3 | HTTP 層（要リファクタ） | **完了** (2026-09-07) |
| 4 | Client | **完了** (2026-09-07) |
| 5 | CI・静的解析 | 未着手 |

## 7. 決定事項・保留事項

| 項目 | 決定 |
| --- | --- |
| テストフレームワーク | PHPUnit `^10.5`（導入済み） |
| Phase 3 の DI リファクタ | 承認のうえ実施済み（`ClientInterface` 注入方式） |
| Phase 5 の CI / 静的解析 | **保留** — 着手前に要承認 |
| 実 API への結合テスト | 行わない |

---

## 8. 発見事項（Phase 1-2 のテスト作成で判明した既存の不具合）

8-1・8-2・8-6・8-7・8-9・8-10 と課題 C は 2026-09-07 に対応済み。
残りの 8-3・8-4・8-5・8-8 は未対応で、対応方針の判断が必要。

### 8-1. `Constants/ErrorCode` がロード時に致命的エラーになる ✅ 対応済み (2026-09-07)

```
Fatal error: Enum case type int does not match enum backing type string
  in src/Constants/ErrorCode.php on line 7
```

`enum ErrorCode: string` と宣言しているが、ケースの値が整数リテラル（`case UNAUTHORIZED = 401010;`）になっていた。
PHP のコンパイル時エラーのため `try/catch` で捕捉できず、このクラスを参照した時点でプロセスが停止していた。

さらに修正の過程で、**同一クラスの `message()` にも独立した不具合**が判明した。
enum のケースは配列のキーにできないため、呼び出すと `TypeError: Illegal offset type` になる。

```php
// 修正前: enum ケースをキーにしているため実行時に TypeError
return [ self::UNAUTHORIZED => '...' ];
```

- **修正内容**:
  - ケースの値を文字列リテラルにした（`case UNAUTHORIZED = '401010';`）。`Entities/Error::$code` が `string` のため文字列に揃えている
  - `message()` の配列キーを `self::UNAUTHORIZED->value` に変更し、戻り値の型宣言 `: array` と PHPDoc を追加した
- **戻り値の形の変更**: `message()` はエラーコード文字列をキーとする連想配列を返す。呼び出しは `ErrorCode::message()[$code->value]` となる。
  修正前のコードは実行不能だったため、後方互換性への影響はない
- **テスト**: `tests/Constants/ErrorCodeTest.php`。`EnumContractTest` の `EXCLUDED` からも除外を解除済み

### 8-2. `Entities/Error::getField()` が未初期化エラーを投げる ✅ 対応済み (2026-09-07)

```
Error: Typed property Shimoning\ColorMeShopApi\Entities\Error::$field
       must not be accessed before initialization
```

`protected ?string $field;` にデフォルト値がなく、`Entity::__construct()` は**レスポンスに存在するキーしか代入しない**。
API のエラーレスポンスは `field` を含まないことがある（401 / 404 など）ため、`getField()` が実行時に落ちていた。

- **修正内容**: `protected ?string $field = null;`
- **テスト**: `tests/Communicator/ErrorsTest.php`。仕様化テストを本来あるべき挙動（`null` を返す）に書き換えている

### 8-3. 未初期化 typed property は全エンティティに共通する構造的リスク 🟡

`src/Entities/` 配下の typed property のうち、**269 個がデフォルト値なし**（デフォルト値ありは 2 個のみ）。
8-2 と同じ問題が、API レスポンスに含まれないフィールドのゲッターすべてで起こりうる。

`toArray()` / `toArrayRecursive()` は `$values[$key] ?? null` で回避しているが、個別のゲッターは保護されていない。

- **想定される修正の選択肢**:
  1. nullable なプロパティすべてに `= null` を付ける（機械的・安全だが差分が大きい）
  2. `Entity::__construct()` 側で、未指定の nullable プロパティを `null` で初期化する（差分は小さいが基底クラスの挙動変更）
- **判断**: Phase 3 以降で方針を決める。なお 8-2 は個別に対応済みだが、他のエンティティは未対応のまま。

### 8-4. `Values/DateTime` の正規表現が末尾の改行を許容する 🟡

`/^\d{4}-\d{2}-\d{2}(\s\d{2}:\d{2}:\d{2})?$/` は `D` 修飾子がないため、`$` が末尾の改行にもマッチする。
`"2024-01-01\n"` がバリデーションを通過する。実害は小さいが、`\z` または `D` 修飾子を使うのが正しい。

### 8-5. `Entities/Collection` が `Countable` を実装していない 🟡

`count()` メソッドは持つが `Countable` インターフェースを実装していないため、
PHP の `count($collection)` は要素数ではなく常に `1` を返す。`$collection->count()` を使う必要がある。

---

## 9. Phase 1-2 の実施結果

- **テスト数**: 298 / **アサーション数**: 827 / **結果**: 全件パス
  （Phase 1-2 完了時点では 285 テスト / 794 アサーション。発見事項 8-1・8-2 の修正で 13 テスト追加）
- **実行時間**: 約 0.04 秒（外部通信なし）

### 追加したファイル

```
phpunit.xml
docs/TESTING_PLAN.md
tests/
├── TestCase.php                        フィクスチャ読み込みの基底クラス
├── Doubles/                            Entity 検証用のテストダブル
│   ├── PlainEntity.php
│   ├── NestedEntity.php
│   └── ComplexEntity.php
├── Fixtures/
│   ├── errors_401.json
│   └── errors_422.json
├── Values/
│   ├── DateTimeTest.php
│   ├── FuriganaTest.php
│   ├── LimitTest.php
│   └── ScopesTest.php
├── Entities/
│   ├── EntityTest.php
│   ├── CollectionTest.php
│   ├── PageTest.php
│   └── PaginationTest.php
├── Communicator/
│   ├── RequestOptionsTest.php
│   ├── RequestMetaTest.php
│   ├── ResponseTest.php
│   └── ErrorsTest.php
├── Constants/
│   ├── EnumContractTest.php
│   ├── DomainEnumTest.php
│   └── ErrorCodeTest.php
└── Exceptions/
    └── ExceptionTest.php
```

### 実行方法

```bash
composer test                 # 全テストを実行
composer test:coverage        # カバレッジ HTML を coverage/ に出力 (Xdebug または PCOV が必要)
vendor/bin/phpunit tests/Values   # ディレクトリを指定して実行
```

### 補足

- テストメソッド名は日本語で記述している。`--testdox` オプションを付けると PHPUnit が `ucfirst()` でメソッド名を整形する都合上、先頭のマルチバイト文字が壊れて表示される。通常の実行および失敗時のメッセージには影響しないため、`--testdox` は使わない運用とする。
- カバレッジ計測には Xdebug または PCOV が必要。現在のローカル環境にはどちらも入っていない。

### 8-6. `Services/Sales::stat()` のエンドポイント URL に余分な `?` がある ✅ 対応済み (2026-09-07)

`src/Services/Sales.php:100` の URL が `https://api.shop-pro.jp/v1/sales/stat?` と末尾に `?` を含んでいる。
`Request::get()` はクエリがあるとさらに `'?' . http_build_query(...)` を連結するため、実際の送信先はこうなる。

```
https://api.shop-pro.jp/v1/sales/stat??make_date=2024-01-01
```

結果としてクエリのパラメータ名が `make_date` ではなく **`?make_date`** になり、API に日付が正しく渡らない。

- **影響**: `Client::statSales()` / `Sales::stat()` の日付指定が機能していなかった。
- **修正内容**: URL 末尾の `?` を削除した。
- **テスト**: `tests/Services/SalesTest.php`。仕様化テストを本来あるべき挙動に書き換えている。

### 8-7. `RequestOptions` のタイムアウト設定が効いていない ✅ 対応済み (2026-09-07)

`Request::headers()` が `timeout` / `connect_timeout` を **HTTP ヘッダの配列**に入れているため、
Guzzle のリクエストオプションとしてではなく、そのまま HTTP ヘッダとして送信されている。

```
timeout: 5
connect_timeout: 2
```

- **影響**: `RequestOptions` にタイムアウトを設定しても一切適用されず、加えて意味のないヘッダが毎回送信されていた。
- **修正内容**: `headers()` からタイムアウト系を外し、`sendRequest()` で Guzzle のリクエストオプション
  （`$options['timeout']` / `$options['connect_timeout']`）として渡すようにした。
  既定値の `0` は「未設定」を意味するため、クライアント側で設定されたタイムアウトを上書きしないよう、
  `0` より大きい場合のみオプションに含めている。
- **テスト**: `tests/Communicator/RequestTest.php`。Guzzle に実際に渡ったオプションを検証するため、
  `HttpMock::options()` を追加した。

### 8-8. `Request::sendRequest()` の Content-Type 設定がデッドコードになっている 🟡

`$options['headers']` を構築した**後**に `$headers` を書き換えているため、
この分岐で設定した Content-Type は送信内容に反映されない。

```php
$options = [ 'headers' => [ ...$this->headers(), ...$headers ] ];  // ここで確定
if (!isset($headers['Content-Type']) && ...) {
    $headers['Content-Type'] = 'application/json; charset=utf-8';  // 反映されない
}
```

- **現状の影響**: 実際の Content-Type は Guzzle が `json` / `form_params` オプションから自動付与しているため
  通信自体は成立しているが、意図された `charset=utf-8` は付いていない。
- **テストでの扱い**: `tests/Communicator/RequestTest.php` に仕様化テストとして記録済み。
- **想定される修正**: 分岐を `$options['headers']` の構築より前に移動する。

---

## 10. Phase 3 の実施結果

- **テスト数**: 394 / **アサーション数**: 1,020 / **結果**: 全件パス
- Phase 1-2 完了時点（298 テスト）から 96 テスト追加

### リファクタの内容

公開シグネチャは壊さず、任意引数の追加のみに限定した。既存の呼び出し方は一切変わらない。
差分は `src/` 8 ファイルで +81 / -25 行。

| ファイル | 変更 |
| --- | --- |
| `Communicator/Request.php` | 第2引数に `?ClientInterface $client` を追加。プロパティの型を `Client` → `ClientInterface` に変更。あわせて `$options` の既定値を `new RequestOptions`（初期化子内 `new`）から `null` + `??` に変更し、2つの引数で扱いを統一した |
| `Services/{Customer,Delivery,Payment,Product,Sales,Shop}.php` | 第2引数に `?ClientInterface $httpClient` を追加し、各 `new Request(...)` に渡す |
| `Services/OAuth.php` | 同上 |

後方互換性は `tests/BackwardCompatibilityTest.php` が保証している
（必須引数の数と名前、既存の生成方法をリフレクションで固定）。

なお `Request::__construct()` の `$options` は、当初 `?RequestOptions $options = new RequestOptions` と
初期化子内 `new` で既定値を与えていたが、型宣言が nullable であるにもかかわらず `null` を明示的に渡すと
`TypeError: Cannot assign null to property ... of type RequestOptions` になっていた。
`$client` 側と扱いを揃えて `= null` + `??` に統一し、この不整合も解消している。

### 追加したファイル

```
tests/
├── BackwardCompatibilityTest.php       公開シグネチャの契約を固定
├── Support/
│   └── HttpMock.php                    MockHandler + History のテストヘルパー
├── Communicator/
│   └── RequestTest.php
├── Services/
│   ├── CustomerTest.php
│   ├── DeliveryTest.php
│   ├── OAuthTest.php
│   ├── PaymentTest.php
│   ├── ProductTest.php
│   ├── SalesTest.php
│   └── ShopTest.php
└── Fixtures/
    ├── categories.json      ├── payments.json
    ├── customer.json        ├── sale.json
    ├── customers_page.json  ├── sales_page.json
    ├── deliveries.json      ├── sales_stat.json
    ├── groups.json          └── shop.json
    ├── oauth_token.json
```

外部への通信は一切発生しない（すべて `MockHandler` 経由）。

---

## 11. Phase 4 の実施結果

- **テスト数**: 431 / **アサーション数**: 1,092 / **結果**: 全件パス
- Phase 3 完了時点（401 テスト）から 30 テスト追加

### リファクタの内容

Phase 3 と同じく、公開シグネチャは壊さず任意引数の追加のみ。

| ファイル | 変更 |
| --- | --- |
| `Client.php` | 第2引数に `?ClientInterface $httpClient` を追加し、生成する各 Service（Shop / Sales / Payment / Delivery / Customer / Product / OAuth）に伝播させる |

### 修正した不具合

#### C. `Client::salesService()` の `static $service` がインスタンス間で共有される ✅ 対応済み (2026-09-07)

`static $service` はメソッドスコープの static 変数のため、**PHP プロセス全体で1つ**しか存在しない。
最初に生成された `Sales` サービスが以降ずっと再利用され、別のアクセストークンで `Client` を作っても
最初のトークンで通信してしまう。

```php
$a = new Client('token-A');
$b = new Client('token-B');
$b->getSale(1001);   // token-A で通信していた
```

- **修正内容**: `static` キャッシュを廃止し、他の全メソッドと同様に呼び出しごとに `Sales` を生成するようにした。
  `Sales` はアクセストークン以外に状態を持たず生成コストも小さいため、キャッシュの利点がない。
  他の6メソッドはもともと都度生成しており、これで実装が統一される
- **テスト**: `tests/ClientTest.php` にリグレッションテストを追加

#### 8-9. `Client::getShop()` にアクセストークンのガードが欠落している ✅ 対応済み (2026-09-07)

他のメソッドが持つ `empty($this->accessToken)` のチェックが `getShop()` にだけなく、
アクセストークンを渡さずに呼ぶと `ParameterException` ではなく未初期化プロパティの `Error` になっていた。

```
Error: Typed property Shimoning\ColorMeShopApi\Client::$accessToken
       must not be accessed before initialization
```

- **修正内容**: 他メソッドと同じガードを追加した
- **テスト**: `tests/ClientTest.php`

#### 8-10. `Client` のアクセストークン判定が真偽値で行われている ✅ 対応済み (2026-09-07)

`Client` の9箇所（コンストラクタ含む）が `if ($accessToken)` と真偽値で「トークンが渡されたか」を
判定していたため、空文字や `"0"` が「未指定」と誤認され、**黙って以前のトークンにフォールバック**していた。

```php
$client = new Client($tenantA->token);
$client->getShop($tenantB->token ?? '');   // '' → テナントAのトークンで通信していた
```

`Client` はインスタンスにトークンを保持し続ける設計のため、マルチテナントで使い回すと
別テナントの認証情報で通信してしまう危険があった。

- **修正内容**: 9箇所すべてを `if ($accessToken !== null)` に統一した。
  空文字は代入されたうえで `empty()` のガードに掛かり `ParameterException` になる。
  黙って誤ったトークンを使うより明示的に失敗する方が安全という判断。
  Service 側は元から `$accessToken ?? $this->_accessToken` と null 基準で解決しており、これで判定基準が揃う
- **挙動の変更**: `getShop('')` が「以前のトークンで成功」から `ParameterException` に変わる。
  依存すべきでない未文書の挙動であり、修正が妥当と判断した
- **テスト**: `tests/ClientTest.php`

### 追加したファイル

```
tests/ClientTest.php     Client ファサードの全メソッドのテスト
```

`tests/BackwardCompatibilityTest.php` には `Client` のコンストラクタ契約に加え、
`ClientInterface` の注入が公開 API の一部になったことを踏まえて、
各 Service / `Request` / `Client` の第2引数の存在・名前・nullable であることも固定している。

---

## 12. 発見事項 8-6 / 8-7 の修正結果

- **テスト数**: 448 / **アサーション数**: 1,134 / **結果**: 全件パス
  （Phase 4 のマージ後の `master` を取り込んだ時点の数値。この修正自体で追加したのは 5 テスト）

いずれも仕様化テストを本来あるべき挙動に書き換えて Red を確認してから修正している。

| ファイル | 変更 |
| --- | --- |
| `Services/Sales.php` | `stat()` のエンドポイント URL 末尾の `?` を削除 |
| `Communicator/Request.php` | `headers()` からタイムアウト系を外し、`sendRequest()` で Guzzle のリクエストオプションとして渡す |
| `tests/Support/HttpMock.php` | Guzzle に実際に渡ったオプションを検証する `options()` を追加 |

修正後に実際の送信内容を確認した結果。

```
stat URI : https://api.shop-pro.jp/v1/sales/stat?make_date=2024-01-01
ヘッダ    : Host, User-Agent
timeout  : 5.0 / connect_timeout: 2.0
```

なお 8-8（`Content-Type` 設定のデッドコード）は、修正すると送信される `Content-Type` が
`application/json` から `application/json; charset=utf-8` に変わり、通信内容が変化する。
今回のスコープ外として据え置いている。
