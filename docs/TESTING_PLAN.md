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

- [x] GitHub Actions ワークフロー（PHP 8.1 / 8.2 / 8.3 / 8.4 マトリクス）
- [x] `composer validate --strict`（`--no-check-version` 付き。後述）
- [x] PHPStan 導入（**level 3** で無警告。計画時の level 5 からの変更理由は後述）
- [x] カバレッジ計測（PCOV。CI でテキスト出力し clover をアーティファクトとして保存）
- [x] `composer analyse` / `composer check` スクリプトの追加

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
| 5 | CI・静的解析 | **完了** (2026-09-08) |

## 7. 決定事項・保留事項

| 項目 | 決定 |
| --- | --- |
| テストフレームワーク | PHPUnit `^10.5`（導入済み） |
| Phase 3 の DI リファクタ | 承認のうえ実施済み（`ClientInterface` 注入方式） |
| Phase 5 の CI / 静的解析 | 承認のうえ実施済み（GitHub Actions + PHPStan level 3） |
| 実 API への結合テスト | 行わない |

---

## 8. 発見事項（Phase 1-2 のテスト作成で判明した既存の不具合）

8-1・8-2・8-6・8-7・8-8・8-9・8-10 と課題 C は 2026-09-07 に対応済み。
8-11 から 8-14 は Phase 5 の静的解析で発見し、2026-09-08 に対応済み。
8-4・8-5 も 2026-09-08 に対応済み。
8-3 は第1〜第3段階すべてを 2026-09-08 に対応済み。
8-15 も 2026-09-08 に対応済み。

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

### 8-3. 未初期化 typed property は全エンティティに共通する構造的リスク ✅ 対応済み (2026-09-08)

型付きプロパティは初期化しないまま参照すると `Error` になるため、API レスポンスに
含まれなかった項目のゲッターが実行時に落ちる。`Entities\Error::getField()`（8-2）、
`Payment\Financial::getKouzaType()`（8-12）が実際にこれで落ちていた。

#### 実測した内訳

当初「デフォルト値なしの typed property が 269 個」とだけ記録していたが、
対応方針を立てるにあたって内訳を測り直した。

| プロパティの種類 | 件数 | `= null` で救えるか |
| --- | --- | --- |
| デフォルト値なし・null 許容（`?T`） | 122 | ✅ 可能 |
| デフォルト値なし・非 null 許容（`T`） | 168 | ❌ 不可（型変更が必要） |
| デフォルト値あり | 3 | — |

**`= null` を付けて解決できるのは全体の 42% に留まる。** 残りは `string` や `int` として
宣言されており、null を代入できない。当初の「nullable なプロパティすべてに `= null` を付ける」
という案は、この半分以上をカバーできない点で不十分だった。

なお null 許容プロパティに対応するゲッター 87 個は、戻り値の型もすべて null 許容だった。
型の不整合はないため、null を返すようにすれば端から端まで整合する。

#### 第1段階（対応済み）: 基底クラスで null 許容プロパティを初期化する

`Entity::__construct()` の最後で、レスポンスに含まれなかった null 許容プロパティを
`null` で初期化するようにした。リフレクションの結果はクラス単位でキャッシュしている。

- **対象**: 122 個すべて。122 ファイルに個別に `= null` を書く案もあったが、
  差分が大きく追加漏れも起きるため基底クラス方式を選んだ。今後追加されるプロパティも自動的に守られる
- **後方互換性**: 影響なし。従来 `Error` を投げていたゲッターが `null` を返すようになるだけ
- **生成コスト**: `Sale` 20,000 件の生成で 33.5 ms（1 件あたり 0.0017 ms）。キャッシュにより実質無視できる
- **効果の例**: `Shop` を実フィクスチャで生成したとき、失敗するゲッターが 25/37 件から 17/37 件に減少

あわせて、リフレクション結果のキャッシュ用プロパティが `toArray()` に混ざらないよう、
アンダースコアで始まる内部プロパティを配列化の対象外とする規約にした。

#### 第2段階（対応済み 2026-09-08）: 非 null 許容プロパティの棚卸し

公式の OpenAPI 仕様と突き合わせて、非 null 許容で宣言されているプロパティを検証した。

##### 仕様の在り処

ドキュメントページ（`https://developer.shop-pro.jp/docs/colorme-api`）は Nuxt + Redoc の
SPA で本文を取得できないが、JS バンドルに仕様の URL が埋め込まれている。

```
https://api.shop-pro.jp/v1/spec/open_api.json   (OpenAPI 3.0.2 / 36 スキーマ)
```

##### 前提の訂正

**全スキーマで `required` が空**だった。仕様上どのフィールドも必須と宣言されていないため、
「非 null 許容 168 個のうちどれが本当はオプショナルか」を `required` から判定することはできない。

代わりに **`nullable: true`** が明示されているフィールドを軸に、PHP 側の型と突き合わせた。
`Entity` と同じ camelCase 変換を使い、ネストされた `payment.cod` / `card` / `financial`、
`delivery.charge` も対象に含めている。

##### 結果

227 件を検査し、**要修正は 3 件**だった。

| クラス | プロパティ | 修正前 | 仕様 |
| --- | --- | --- | --- |
| `Shop\Shop` | `$hojin` | `string` | nullable（法人名） |
| `Shop\Shop` | `$hojinKana` | `string` | nullable（法人名カナ） |
| `Product\Group` | `$parentGroupId` | `int` | nullable「親グループが存在しない場合は null になります」 |

3 件とも**構築時の `TypeError`** で、ゲッター単体ではなくエンドポイント全体が失敗していた。

```
TypeError: Cannot assign null to property ...\Group::$parentGroupId of type int
TypeError: Cannot assign null to property ...\Shop::$hojin of type string
```

`parent_group_id` は「親グループが存在しない場合は null」と仕様に明記されており、
**トップレベルの商品グループが 1 つでもあれば `getProductGroups()` が丸ごと落ちる**状態だった。
`hojin` も法人でないショップで `getShop()` が落ちる。

- **後方互換性**: `Shop` のゲッターは元から `?string` を返す宣言だったためシグネチャの変更はない。
  `Group::getParentGroupId()` は `int` → `?int` の**破壊的変更**にあたるが、
  修正前は該当ケースで必ず落ちていたため実質的な影響は小さい。
- **あわせて修正**: `getParentGroupId()` の PHPDoc の説明文が「配送希望日を指定可能か」と
  別項目からのコピーになっていた。
- **テスト**: `tests/Entities/Shop/ShopTest.php`、`tests/Entities/Product/GroupTest.php`

##### 対象外とした項目

- `Sales\Sale::$memo` は仕様に存在しない。API から削除されたフィールドの残骸だが
  （`8fc25a1 Backward-Compatibility: Sales->getMemo() removed` と整合）、
  後方互換性のため残す判断とした。
- 残る 224 件は仕様と型が整合していた。`required` が空である以上、
  仕様からこれ以上の判定はできない。

#### 第3段階（対応済み）: 再発防止の契約テスト

`tests/Entities/EntityContractTest.php` を追加した。全エンティティに対して横断的に検証する。

- 宣言されていないプロパティを参照しているゲッターがないこと
  （`Category` や `Financial` で起きたタイポの再発防止）
- null 許容プロパティのゲッターが、空のレスポンスでも例外を投げず `null` を返すこと
  （第1段階の効果を固定）

### 8-4. `Values/DateTime` の正規表現が末尾の改行を許容する ✅ 対応済み (2026-09-08)

`/^\d{4}-\d{2}-\d{2}(\s\d{2}:\d{2}:\d{2})?$/` は `D` 修飾子がないため、`$` が末尾の改行にもマッチしていた。
`"2024-01-01\n"` がバリデーションを通過し、そのまま API のクエリに渡る。

- **修正内容**: `^`/`$` を文字列の先頭・終端を表す `\A`/`\z` に変更した。
- **あわせて修正した点**: 日付と時刻の区切りが `\s` になっており、半角スペースだけでなく
  改行・タブ・復帰・垂直タブも受理していた（`"2024-01-01\n12:34:56"` が通る）。
  エラーメッセージが示す形式は `YYYY-MM-DD hh:mm:ss` と半角スペース区切りであり不整合だったため、
  半角スペースのみに限定した。
- **テスト**: `tests/Values/DateTimeTest.php`。末尾の改行に加え、区切り文字の各種バリエーションを検証している。

### 8-5. `Entities/Collection` が `Countable` を実装していない ✅ 対応済み (2026-09-08)

`count()` メソッドは持つが `Countable` インターフェースを実装していなかった。

当初この項目は「PHP の `count($collection)` は要素数ではなく常に `1` を返す」と記載していたが、
これは PHP 7 以前の挙動で**誤り**だった。実際に確認したところ、PHP 8 では `TypeError` になる。

```
TypeError: count(): Argument #1 ($value) must be of type Countable|array,
           Shimoning\ColorMeShopApi\Entities\Collection given
```

- **影響**: `Collection` を返す API（`getPayments()` / `getDeliveries()` / `getProductGroups()` /
  `getProductCategories()`）の結果に `count()` を使うと落ちる。`Page` と `Errors` も `Collection` を
  継承しているため同様。
- **修正内容**: `Countable` を実装した。`count()` メソッド自体は元からあるため、追加した実装はない。
- **後方互換性**: `count()` は従来 `TypeError` だったため、動いていたコードへの影響はない。
- **テスト**: `tests/Entities/CollectionTest.php` と `tests/Entities/PageTest.php`。

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

### 8-8. `Request::sendRequest()` の Content-Type 設定がデッドコードになっている ✅ 対応済み (2026-09-07)

`$options['headers']` を構築した**後**に `$headers` を書き換えているため、
この分岐で設定した Content-Type は送信内容に反映されない。

```php
$options = [ 'headers' => [ ...$this->headers(), ...$headers ] ];  // ここで確定
if (!isset($headers['Content-Type']) && ...) {
    $headers['Content-Type'] = 'application/json; charset=utf-8';  // 反映されない
}
```

- **影響**: 実際の Content-Type は Guzzle が `json` / `form_params` オプションから自動付与していたため
  通信自体は成立していたが、意図された `charset=utf-8` は付いていなかった。
- **修正内容**: 分岐を `$options['headers']` の構築より前に移動した。
  呼び出し側が `Content-Type` を明示している場合はそちらを優先する既存の意図もそのまま機能するようになった。
- **あわせて修正した点**: 有無の判定が `isset($headers['Content-Type'])` と大小文字を区別していた。
  HTTP ヘッダ名は大小文字を区別しないため、呼び出し側が `content-type` などの表記で指定すると
  取りこぼして既定値を追加してしまい、値が2つ並んだ不正なヘッダになる。

  ```
  Content-Type: application/vnd.api+json, application/json; charset=utf-8
  ```

  この分岐はもともとデッドコードだったため到達しなかったが、上記の修正で有効になったことで
  顕在化する。`array_change_key_case()` を使い、大小文字を無視して判定するようにした
- **テスト**: `tests/Communicator/RequestTest.php`。json / form それぞれの Content-Type、
  呼び出し側指定の優先（ヘッダ名の表記違いを含む）、値が1つだけになること、
  GET には付与しないことを検証している。

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

なお 8-8（`Content-Type` 設定のデッドコード）は、送信される `Content-Type` が変化するため
この修正のスコープ外とし、別途対応した。

---

## 13. Phase 5 の実施結果

- **テスト数**: 472 / **アサーション数**: 1,167 / **結果**: 全件パス
- **PHPStan**: level 3 で無警告（baseline / ignoreErrors は不使用）
- ローカルの PHP 8.1 と 8.4 の双方でテストと静的解析の通過を確認済み

### CI（`.github/workflows/test.yml`）

| ジョブ | 内容 |
| --- | --- |
| `test` | PHP 8.1 / 8.2 / 8.3 / 8.4 のマトリクス。`composer validate` → 依存インストール → PHPUnit |
| `static-analysis` | 対応最小バージョンの PHP 8.1 で PHPStan |
| `coverage` | PCOV でカバレッジを計測し、clover をアーティファクトとして保存 |

`fail-fast: false` にしているため、特定バージョンだけで失敗した場合も他の結果が得られる。

#### `composer validate` について

`--strict` のみでは `composer.json` の `version` フィールドに対する警告で失敗する。
このフィールドを置くかどうかはパッケージの管理方針であり自動テストの都合で変更すべきではないため、
`--no-check-version` を付けて当該警告のみ除外している。

### PHPStan のレベル

計画時は level 5 を想定していたが、実際に走らせた結果を踏まえて **level 3** から始めることにした。

| level | 指摘件数（導入前） | 内容 |
| --- | --- | --- |
| 0-1 | 5 | 実行時エラーになる不具合 3件（8-11 が 2件・8-12 が 1件）、PHPDoc の誤り 1件（8-13）、誤検出 1件。すべて対応済み |
| 2 | 11 | PHPDoc の誤り。すべて修正済み。**うち 1件を調べる過程で実行時エラー（8-14）を発見** |
| 3 | 6 | PHPDoc の誤り。すべて修正済み |
| 4 | 17 | 「常に真/偽」の判定。テスト側の意図的なアサーションが多く含まれる |
| 5 | 1 | 引数の型の厳密化 |
| 6 | 110 | 配列・イテラブルのジェネリクス未指定 |

level 4 以降を無理に通すと、防御的なコードやテストの意図を損なう。
`baseline` や `ignoreErrors` での抑制は行わない方針のため、level 3 を到達点とし、
引き上げに必要な作業を `phpstan.neon` にコメントとして記録した。

とくに level 4 は、テストが契約を固定するために意図的に書いている
「常に真のアサーション」を指摘してくる。引き上げるなら `src` と `tests` で
level を分ける構成が必要になる。

---

## 14. 静的解析で発見した不具合（Phase 5）

PHPStan の導入により、**実行時エラーになる不具合が 3件**（8-11・8-12・8-14）と、
PHPDoc の誤り 1件（8-13）が見つかった。
いずれもテストを先に書いて Red を確認してから修正している。

| 発見事項 | 検出したレベル | 実行時の影響 |
| --- | --- | --- |
| 8-11 `Category` の ID ゲッター | level 0 | 🔴 参照すると必ず `TypeError` |
| 8-12 `Financial` の口座種別 | level 0 | 🔴 参照すると必ず `TypeError`。データも欠落 |
| 8-13 `setTotalCharge()` の PHPDoc | level 0 | なし（戻り値の型宣言がないため） |
| 8-14 `Options::setRedirectUri()` | level 2 の PHPDoc 指摘を調べる過程で発見 | 🔴 enum を渡すと必ず `TypeError` |

なお level 0 の指摘 5件のうち 1件は `Entity::OBJECT_FIELDS` に対する誤検出だった。
`defined()` で存在を確認してから参照していたため実行時の問題はなく、
基底クラスに空の既定値を宣言することで解消している。

### 8-11. `Product/Category` の ID ゲッターが存在しないプロパティを参照している 🔴 ✅ 対応済み

`getIdBig()` と `getIdSmall()` が、どちらも宣言されていない `$this->id` を返していた。

```
Warning: Undefined property: ...\Category::$id
TypeError: ...\Category::getIdBig(): Return value must be of type int, null returned
```

- **影響**: 商品カテゴリー一覧を取得したあと、ID を参照すると必ず落ちる。
- **修正内容**: それぞれ `$this->idBig` / `$this->idSmall` を返すようにした。
- **テスト**: `tests/Entities/Product/CategoryTest.php`

### 8-12. `Payment/Financial` の口座種別が取得できない 🔴 ✅ 対応済み

3つの誤りが重なっていた。

1. プロパティ名が `$kouzaTyp` と綴り誤り（`e` が欠落）
2. そのためレスポンスの `kouza_type` がどのプロパティにも一致せず、値が取り込まれない
3. `getKouzaType()` は存在しない `$this->kouzaType` を返していた
4. `OBJECT_FIELDS` のキーが `brands`（別エンティティの項目名）になっており、enum 変換も効いていない

```
TypeError: ...\Financial::getKouzaType(): Return value must be of type KouzaType, null returned
```

- **影響**: 銀行振込の決済設定で口座種別を参照すると必ず落ちる。データ自体も欠落していた。
- **修正内容**: プロパティ名を `$kouzaType` に修正し、`OBJECT_FIELDS` のキーも `kouzaType` にした。
- **テスト**: `tests/Entities/Payment/FinancialTest.php`

### 8-13. `SaleDeliveryUpdater::setTotalCharge()` の PHPDoc が誤っている 🟡 ✅ 対応済み

セッターなのに `@return int` になっていた（ゲッターからのコピー由来と思われる）。
戻り値の型宣言はないため実行時の影響はないが、静的解析では戻り値の欠落として扱われる。

- **修正内容**: `@param int $totalCharge` に修正した。

### 8-14. `OAuth/Options::setRedirectUri()` に enum を渡すと TypeError になる 🔴 ✅ 対応済み

引数は `AuthRedirectUri|string` を受け付けるが、戻り値の型宣言が `string` で、
かつ代入式をそのまま返していたため、enum を渡すと戻り値の型検査で落ちていた。

```php
return $this->redirectUri = $uri;   // $uri が enum のとき TypeError
```

```
TypeError: ...\Options::setRedirectUri(): Return value must be of type string,
           ...\Constants\AuthRedirectUri returned
```

- **影響**: `AuthRedirectUri::NO_REDIRECT` を渡すと必ず落ちる。引数の型宣言が受け付ける値の半分が使えない状態だった。
- **修正内容**: 代入と戻り値を分離し、`getRedirectUri()` の結果（文字列化済み）を返すようにした。
  シグネチャは変えていないため後方互換性への影響はない。
- **あわせて修正した点**: コンストラクタの `$redirectUri` が `mixed` のままで、
  PHPDoc の `AuthRedirectUri|string` という型契約がコード上で保証されていなかった。
  不正な値を渡してもエラーは引数ではなくプロパティ代入時のものになり、呼び出し側から原因が分かりにくい。
  さらに整数を渡した場合は型強制で文字列になり、エラーにすらならず不正なリダイレクト URI が
  そのまま設定されていた（`new Options('id', 'secret', 123)` → `getRedirectUri()` が `'123'`）。
  シグネチャを `AuthRedirectUri|string` に揃えた。

  **後方互換性への影響**（実測で確認）

  | 呼び出し側 | 渡した値 | 変更前 | 変更後 |
  | --- | --- | --- | --- |
  | 非 strict（PHP の既定） | `int` / `float` / `bool` | 受理（文字列化） | 受理（文字列化） |
  | 非 strict（PHP の既定） | `null` / 配列 | `TypeError` | `TypeError` |
  | `declare(strict_types=1)` | `int` / `float` / `bool` | 受理（文字列化） | **`TypeError`** |
  | `declare(strict_types=1)` | `null` / 配列 | `TypeError` | `TypeError` |

  PHP の既定である非 strict な呼び出し側では挙動が完全に一致し、影響はない。
  変化するのは `declare(strict_types=1)` の呼び出し側が、リダイレクト URI として
  文字列以外のスカラー値を渡していた場合のみ。これは意味的に不正な使い方であり、
  従来は黙って文字列に変換されていた（`123` → `'123'`）。
  本ライブラリは 0.6 系であることも踏まえ、型を明確化する側を選んだ
- **テスト**: `tests/Entities/OAuth/OptionsTest.php`

### あわせて修正した PHPDoc の誤り

実行時の影響はないが、型情報として誤っていたもの。

| 箇所 | 内容 |
| --- | --- |
| `Communicator/Request::sendRequest()` | `@param` に変数名がない |
| `Entities/Customer::isMember()` | `@return string|null` → 実際は `bool` |
| `Entities/Delivery::getChargeType()` | `@return sDeliveryChargeType` という存在しない型名（説明文も別項目からのコピー） |
| `Entities/Error::getStatus()` | `@return string` → 実際は `int` |
| `Entities/OAuth/Options::__construct()` | `@param` の型と並び順がシグネチャと不一致 |
| `Entities/Sales/SaleUpdater::setSaleDeliveries()` | `@param` に変数名がない |
| `Services/Sales::stat()` | `DateTimeInterface` が名前空間解決されない／`@param` の重複 |
| `Constants/ErrorCode::message()` | 数値のみのキーは PHP の仕様で int になるため `array<int, string>` が正しい |
| `Entities/Entity::build()` | `@return array|object` → 実際は `mixed` |
| `Entities/Sales/Sale::getPaymentId()` | 戻り値の型宣言が `string` だがプロパティは `int`。**戻り値を `int` に変更した**（後述） |

### `Sale::getPaymentId()` の戻り値を int に変更 ⚠️ 破壊的変更

PHPStan が「戻り値の型宣言は `string` だがプロパティは `int`」と指摘した箇所。
当初は暗黙の型変換に頼らないよう明示的にキャストする形で揃えたが、
**正しいのは `int` を返すこと**であるため、戻り値の型宣言側を変更した。

このライブラリの数値 ID のゲッターは他がすべて `int` を返しており、
`getPaymentId()` だけが `string` になっていた。

| ゲッター | 戻り値 |
| --- | --- |
| `Sale::getId()` | `int` |
| `Payment::getId()`（対応する決済方法そのもの） | `int` |
| `SaleDelivery::getDeliveryId()` | `int` |
| `SaleDetail::getProductId()` | `int` |
| `Sale::getPaymentId()` | ~~`string`~~ → **`int`** |

`string` を返していたのは `getAccountId()` や `Shop::getId()` など、
本来文字列である ID に限られる。

- **後方互換性への影響**: 戻り値が `'42'` から `42` に変わる。
  緩やかな比較（`==`）や文字列連結では差が出ないが、厳密比較（`===`）や
  `is_string()` による判定を行っている場合は影響を受ける
- **テスト**: `tests/Entities/Sales/SaleTest.php`

### 8-15. `toArray()` が API のフィールド名を復元できない項目がある ✅ 対応済み (2026-09-08)

`Entity` は取り込み時に「アンダースコア区切り → camelCase」、配列化時に「大文字の前に
アンダースコアを挿入」という変換を行うが、この2つは対称ではない。
数字の前にはアンダースコアが入らないため、`_1` のような区切りが失われる。

```
shop_mail_1  →（取り込み）→  shopMail1  →（配列化）→  shop_mail1
```

`toArray()` / `toArrayRecursive()` から `shop_mail_1` のキーで取り出せず、
`shop_mail1` という API に存在しないキーになっていた。

#### 精査した範囲

公式の OpenAPI 仕様の全フィールド **769 件**について往復変換を検査したところ、
戻らないのは `shop_mail_1` と `shop_mail_2` の **2 件のみ**だった。

#### 修正内容

`Entity::FIELD_NAMES` を追加した。プロパティ名をキー、API のフィールド名を値として、
自動変換では表現できないものだけを定義する。取り込みと配列化の両方でこの対応表を優先する。

```php
// Shop\Shop
const FIELD_NAMES = [
    'shopMail1' => 'shop_mail_1',
    'shopMail2' => 'shop_mail_2',
];
```

変換ロジック自体を変更する案もあったが、数字の前に一律でアンダースコアを入れると
`name1` や `option1_value` のように**もともと区切りがない**フィールドが `name_1` に
化けて壊れるため採用しなかった。

- **後方互換性**: `toArray()` のキーが `shop_mail1` から `shop_mail_1` に変わる。
  従来のキーは API に存在しない誤った名前だったため、修正が妥当と判断した
- **テスト**: `tests/Entities/ApiFieldNameTest.php`。
  仕様から生成した `tests/Fixtures/api_field_names.json`（13 クラス / 226 フィールド）を使い、
  全フィールドが往復することを検証している。今後フィールドを追加したときも、
  同じ仕様から生成し直せば検査対象に入る

#### 併せて把握した既存の制約（未対応）

`toArray()` の出力をそのままコンストラクタに渡すことはできない。
レスポンスに含まれなかった項目が `null` として出力されるため、
非 null 許容のプロパティで `TypeError` になる。これは今回の変更以前からの挙動で、
`toArray()` は「現在の値を配列で見る」ためのもの、という位置づけになっている。
