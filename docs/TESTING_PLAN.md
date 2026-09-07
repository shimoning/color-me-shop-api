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
| A | `src/Services/*.php` | 各メソッド内で `new Request(...)` を直接生成 | HTTP をモックできず Service 層をテスト不能 |
| B | `src/Communicator/Request.php:11` | コンストラクタ内で `new Client()`（Guzzle）を生成 | ハンドラを差し替えられない |
| C | `src/Client.php:181` `salesService()` | `static $service` がメソッドスコープ static 変数のためインスタンス間で共有される | 別トークンで2つ目の `Client` を生成しても最初の Service が再利用される既存バグ |

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
public function __construct(string $accessToken, ?Request $request = null)
```

- [ ] リファクタ前に Service 層の仕様化テストを設置
- [ ] `Request` に Guzzle `ClientInterface` を任意注入可能にする
- [ ] 各 `Services/*` に `Request` を任意注入可能にする
- [ ] `MockHandler` + History ミドルウェアによる送信内容の検証（メソッド / URL / クエリ / `Authorization` ヘッダ / ボディ）
- [ ] `Services/OAuth` — `getUrl()` のクエリ生成（RFC3986）、`exchangeCode2Token()`
- [ ] `Services/Shop`
- [ ] `Services/Sales` — `page` / `one` / `stat` / `update` / `cancel` / `sendMail`
- [ ] `Services/Payment`
- [ ] `Services/Delivery`
- [ ] `Services/Customer`
- [ ] `Services/Product`
- [ ] 4xx / 5xx 時に `Errors` が返ることの検証

### Phase 4: Client

- [ ] `Client::salesService()` の `static $service` バグ（課題 C）の修正とリグレッションテスト
- [ ] アクセストークン未指定時に `ParameterException` が投げられること
- [ ] 各メソッドが対応する Service に正しく委譲すること
- [ ] 引数で渡したアクセストークンがインスタンスの値を上書きすること

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
| 3 | HTTP 層（要リファクタ） | 未着手 |
| 4 | Client | 未着手 |
| 5 | CI・静的解析 | 未着手 |

## 7. 決定事項・保留事項

| 項目 | 決定 |
| --- | --- |
| テストフレームワーク | PHPUnit `^10.5`（導入済み） |
| Phase 3 の DI リファクタ | **保留** — 着手前に要承認 |
| Phase 5 の CI / 静的解析 | **保留** — 着手前に要承認 |
| 実 API への結合テスト | 行わない |

---

## 8. 発見事項（Phase 1-2 のテスト作成で判明した既存の不具合）

いずれも今回のスコープ外のため**修正していない**。対応方針の判断が必要。

### 8-1. `Constants/ErrorCode` がロード時に致命的エラーになる 🔴

```
Fatal error: Enum case type int does not match enum backing type string
  in src/Constants/ErrorCode.php on line 7
```

`enum ErrorCode: string` と宣言しているが、ケースの値が整数リテラル（`case UNAUTHORIZED = 401010;`）になっている。
PHP のコンパイル時エラーのため `try/catch` で捕捉できず、このクラスを参照した時点でプロセスが停止する。

- **現状の影響**: `src/` 内から一切参照されていないため実害は出ていない。ただし利用者が `ErrorCode` を参照した瞬間に落ちる。
- **テストでの扱い**: `tests/Constants/EnumContractTest.php` の `EXCLUDED` で除外している。
- **想定される修正**: 値を文字列リテラルにする（`case UNAUTHORIZED = '401010';`）。`Entities/Error::$code` が `string` のため、文字列に揃えるのが整合的。

### 8-2. `Entities/Error::getField()` が未初期化エラーを投げる 🔴

```
Error: Typed property Shimoning\ColorMeShopApi\Entities\Error::$field
       must not be accessed before initialization
```

`protected ?string $field;` にデフォルト値がなく、`Entity::__construct()` は**レスポンスに存在するキーしか代入しない**。
API のエラーレスポンスは `field` を含まないことがある（401 / 404 など）ため、`getField()` が実行時に落ちる。

- **現状の影響**: エラーハンドリングで `getField()` を呼ぶと、認証エラー時などに例外が発生する。
- **テストでの扱い**: `tests/Communicator/ErrorsTest.php` に仕様化テストとして現状の挙動を記録済み。修正時にこのテストを書き換える。
- **想定される修正**: `protected ?string $field = null;`

### 8-3. 未初期化 typed property は全エンティティに共通する構造的リスク 🟡

`src/Entities/` 配下の typed property のうち、**269 個がデフォルト値なし**（デフォルト値ありは 2 個のみ）。
8-2 と同じ問題が、API レスポンスに含まれないフィールドのゲッターすべてで起こりうる。

`toArray()` / `toArrayRecursive()` は `$values[$key] ?? null` で回避しているが、個別のゲッターは保護されていない。

- **想定される修正の選択肢**:
  1. nullable なプロパティすべてに `= null` を付ける（機械的・安全だが差分が大きい）
  2. `Entity::__construct()` 側で、未指定の nullable プロパティを `null` で初期化する（差分は小さいが基底クラスの挙動変更）
- **判断**: Phase 3 以降で方針を決める。

### 8-4. `Values/DateTime` の正規表現が末尾の改行を許容する 🟡

`/^\d{4}-\d{2}-\d{2}(\s\d{2}:\d{2}:\d{2})?$/` は `D` 修飾子がないため、`$` が末尾の改行にもマッチする。
`"2024-01-01\n"` がバリデーションを通過する。実害は小さいが、`\z` または `D` 修飾子を使うのが正しい。

### 8-5. `Entities/Collection` が `Countable` を実装していない 🟡

`count()` メソッドは持つが `Countable` インターフェースを実装していないため、
PHP の `count($collection)` は要素数ではなく常に `1` を返す。`$collection->count()` を使う必要がある。

---

## 9. Phase 1-2 の実施結果

- **テスト数**: 285 / **アサーション数**: 794 / **結果**: 全件パス
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
│   └── DomainEnumTest.php
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
