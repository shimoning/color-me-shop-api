<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Exceptions;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Constants\MailState;
use Shimoning\ColorMeShopApi\Entities\Delivery\Charge;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Entities\Page;
use Shimoning\ColorMeShopApi\Entities\Pagination;
use Shimoning\ColorMeShopApi\Entities\Payment\Cod;
use Shimoning\ColorMeShopApi\Entities\Product\Category;
use Shimoning\ColorMeShopApi\Entities\Sales\Sale;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Exceptions\InvalidPaginationException;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;
use Shimoning\ColorMeShopApi\Exceptions\MissingPaginationException;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use Shimoning\ColorMeShopApi\Services\Product;
use Shimoning\ColorMeShopApi\Tests\Doubles\ComplexEntity;
use Shimoning\ColorMeShopApi\Tests\Doubles\HydratedTypeMismatchEntity;
use Shimoning\ColorMeShopApi\Tests\Doubles\InheritedPrivateFieldEntity;
use Shimoning\ColorMeShopApi\Tests\Doubles\NestedEntity;
use Shimoning\ColorMeShopApi\Tests\Doubles\PromotedReadonlyFieldEntity;
use Shimoning\ColorMeShopApi\Tests\Doubles\RequiredEntity;
use Shimoning\ColorMeShopApi\Tests\Support\ExceptionCallSiteScanner;
use Shimoning\ColorMeShopApi\Tests\Support\HttpMock;

class FieldExceptionMessageContractTest extends TestCase
{
    /*
     * 限界: 同一型検査と禁止句検査は、現行の日本語書式とブラックリストに依存する。
     * 句読点の変更や英語化などの書式変更、Undefined array key、Class not found、UNC パスは見逃しうる。
     * 恒久的には構造化したケースで expected / actual を直接比較し、公開メッセージを許可テンプレートと
     * 許可原因文言のホワイトリストで検証することが望ましい。
     */
    #[DataProvider('exceptionRouteProvider')]
    public function test_全生成経路の公開メッセージは安全で矛盾しない(
        string $callSiteId,
        \Closure $throwing,
        ?string $previousClass,
    ): void {
        $this->assertExceptionMessage($throwing, $previousClass, $callSiteId);
    }

    #[DataProvider('additionalMessageProvider')]
    public function test_追加の公開メッセージ契約を保持する(
        \Closure $throwing,
        ?string $previousClass,
    ): void {
        $this->assertExceptionMessage($throwing, $previousClass);
    }

    private function assertExceptionMessage(
        \Closure $throwing,
        ?string $previousClass,
        ?string $callSiteId = null,
    ): void {
        try {
            $throwing();
        } catch (InvalidFieldException | MissingFieldException $exception) {
            $message = $exception->getMessage();

            $this->assertDoesNotMatchRegularExpression(
                '~(?:[A-Za-z]:[\\\\/]|/)[^\r\n]*?\.php(?::\d+)?~u',
                $message,
                '絶対パスを公開してはいけません。',
            );
            $this->assertStringNotContainsString('::', $message, '内部メソッド名を公開してはいけません。');
            $this->assertDoesNotMatchRegularExpression(
                '~\b[A-Za-z_][A-Za-z0-9_]*\(\)~',
                $message,
                '関数名形式を公開してはいけません。',
            );
            $this->assertDoesNotMatchRegularExpression(
                '~(?:Uncaught|Stack trace:|Argument #\d+|must be of type|must be an instance of|'
                . '\bgiven\b|called in|expects (?:parameter|exactly)|Too (?:few|many) arguments)~i',
                $message,
                'PHP の定型エラー句を公開してはいけません。',
            );

            if (\preg_match('/。(?<expected>[^。]+) を期待しましたが (?<actual>[^。]+) でした。/u', $message, $matches)) {
                $this->assertNotSame(
                    $matches['expected'],
                    $matches['actual'],
                    'expected と actual が同じ表示のメッセージを生成してはいけません。',
                );
            }

            if ($previousClass === null) {
                $this->assertNull($exception->getPrevious());
            } else {
                $this->assertInstanceOf($previousClass, $exception->getPrevious());
            }

            if ($callSiteId !== null) {
                $this->assertExceptionOrigin($exception, $callSiteId);
            }

            return;
        }

        $this->fail('フィールド例外が投げられませんでした。');
    }

    public function test_公開factoryの追加時はメッセージ契約への経路追加を要求する(): void
    {
        $coveredFactories = [];
        foreach (\array_keys(self::exceptionRouteProvider()) as $route) {
            $coveredFactories[\strstr($route, '/', true)] = true;
        }

        $declaredFactories = [];
        foreach ([InvalidFieldException::class, MissingFieldException::class] as $class) {
            $reflection = new \ReflectionClass($class);
            foreach ($reflection->getMethods() as $method) {
                if (
                    $method->isPublic()
                    && $method->isStatic()
                    && $method->getDeclaringClass()->getName() === $class
                ) {
                    $declaredFactories[$reflection->getShortName() . '::' . $method->getName()] = true;
                }
            }
        }

        $coveredFactoryNames = \array_intersect_key($coveredFactories, $declaredFactories);
        \ksort($declaredFactories);
        \ksort($coveredFactoryNames);
        $this->assertSame(\array_keys($declaredFactories), \array_keys($coveredFactoryNames));
    }

    public function test_生成箇所の追加時は横断契約テストの更新を要求する(): void
    {
        $sourceSites = self::sourceCallSites();
        $providerIds = [];
        foreach (self::exceptionRouteProvider() as $case) {
            $providerIds[] = $case[0];
        }

        $duplicates = \array_keys(\array_filter(
            \array_count_values($providerIds),
            static fn(int $count): bool => $count !== 1,
        ));
        $sourceOnly = \array_diff(\array_keys($sourceSites), $providerIds);
        $providerOnly = \array_diff($providerIds, \array_keys($sourceSites));
        \sort($duplicates);
        \sort($sourceOnly);
        \sort($providerOnly);

        $this->assertSame(
            ['sourceOnly' => [], 'providerOnly' => [], 'duplicates' => []],
            ['sourceOnly' => $sourceOnly, 'providerOnly' => $providerOnly, 'duplicates' => $duplicates],
            "provider 未登録の生成箇所: " . \implode(', ', \array_map(
                static fn(string $id): string => $id . ' (' . $sourceSites[$id][0] . ')',
                $sourceOnly,
            ))
                . "\nsrc/ に存在しない provider ID: " . \implode(', ', $providerOnly)
                . "\n重複した provider ID: " . \implode(', ', $duplicates),
        );
    }

    /**
     * 行番号は編集で変わるため、provider は「相対パス + FQCN::method + 同一ファイル・経路内の出現順」を宣言する。
     * scanner は行番号付き位置を抽出し、この安定 ID に正規化してから双方向に照合する。
     */
    private static function site(string $path, string $route, int $ordinal): string
    {
        return $path . '@' . $route . '#' . $ordinal;
    }

    public function test_生成箇所のパスはスラッシュ区切りに正規化する(): void
    {
        $this->assertSame(
            'src/Entities/Product/Category.php',
            self::normalizePath('src\\Entities\\Product\\Category.php'),
        );
    }

    private static function normalizePath(string $path): string
    {
        return \str_replace('\\', '/', $path);
    }

    private static function normalizeAbsolutePath(string $path): string
    {
        return self::normalizePath(\realpath($path) ?: $path);
    }

    /** @return array<string, array{string, string, int}> 安定 ID => [相対パス:開始行, FQCN::method, 終了行] */
    private static function sourceCallSites(): array
    {
        $sourceDirectory = \dirname(__DIR__, 2) . '/src';
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sourceDirectory));
        $sites = [];

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $source = \file_get_contents($file->getPathname());
            if ($source === false) {
                throw new \RuntimeException('ソースを読めません: ' . $file->getPathname());
            }
            $path = self::normalizePath('src/' . \substr($file->getPathname(), \strlen($sourceDirectory) + 1));
            $ordinals = [];
            foreach (ExceptionCallSiteScanner::scan($source, $path) as $location => $callSite) {
                $route = $callSite['route'];
                $ordinals[$route] = ($ordinals[$route] ?? 0) + 1;
                $sites[self::site($path, $route, $ordinals[$route])] = [
                    $location,
                    $route,
                    $callSite['endLine'],
                ];
            }
        }

        return $sites;
    }

    private function assertExceptionOrigin(\Throwable $exception, string $callSiteId): void
    {
        $sourceSites = self::sourceCallSites();
        $site = $sourceSites[$callSiteId] ?? null;
        $this->assertNotNull($site, 'provider ID の生成箇所が見つかりません: ' . $callSiteId);
        [$location, $route, $endLine] = $site;
        $separator = \strrpos($location, ':');
        $this->assertNotFalse($separator);
        $path = \substr($location, 0, $separator);
        $line = (int) \substr($location, $separator + 1);
        $absolutePath = self::normalizeAbsolutePath(\dirname(__DIR__, 2) . '/' . $path);

        if (\str_ends_with($route, '::__construct')) {
            if (
                self::normalizeAbsolutePath($exception->getFile()) === $absolutePath
                && ExceptionCallSiteScanner::containsLine($line, $endLine, $exception->getLine())
            ) {
                return;
            }
            $actual = self::describeOrigin($exception->getFile(), $exception->getLine(), $route, $sourceSites);
            $this->fail(
                '指定した生成箇所を通っていません: ' . $callSiteId . ' (' . $location . '-' . $endLine
                . '); 実際: ' . $actual,
            );
        }

        $actualFrames = [];
        foreach ($exception->getTrace() as $frame) {
            $frameRoute = ($frame['class'] ?? null) . '::' . ($frame['function'] ?? null);
            if ($frameRoute === $route && isset($frame['file'], $frame['line'])) {
                $actualFrames[] = self::describeOrigin($frame['file'], $frame['line'], $route, $sourceSites);
            }
            if (
                isset($frame['file'])
                && self::normalizeAbsolutePath($frame['file']) === $absolutePath
                && isset($frame['line'])
                && ExceptionCallSiteScanner::containsLine($line, $endLine, $frame['line'])
                && $frameRoute === $route
            ) {
                return;
            }
        }

        if ($actualFrames === []) {
            $frame = $exception->getTrace()[0] ?? null;
            $actualFrames[] = $frame !== null && isset($frame['file'], $frame['line'])
                ? self::describeOrigin(
                    $frame['file'],
                    $frame['line'],
                    ($frame['class'] ?? null) . '::' . ($frame['function'] ?? null),
                    $sourceSites,
                )
                : $exception->getFile() . ':' . $exception->getLine();
        }

        $this->fail(
            '指定した生成箇所を通っていません: ' . $callSiteId . ' (' . $location . '-' . $endLine
            . '); 実際: ' . \implode(', ', $actualFrames),
        );
    }

    /**
     * @param array<string, array{string, string, int}> $sourceSites
     */
    private static function describeOrigin(string $file, int $line, string $route, array $sourceSites): string
    {
        $description = $file . ':' . $line;
        foreach ($sourceSites as $id => [$location, $siteRoute, $endLine]) {
            $separator = \strrpos($location, ':');
            if ($separator === false || $siteRoute !== $route) {
                continue;
            }
            $path = self::normalizeAbsolutePath(\dirname(__DIR__, 2) . '/' . \substr($location, 0, $separator));
            $startLine = (int) \substr($location, $separator + 1);
            if (self::normalizeAbsolutePath($file) === $path && ExceptionCallSiteScanner::containsLine($startLine, $endLine, $line)) {
                return $description . ' (' . $id . ')';
            }
        }

        return $description;
    }

    /** @return array<string, array{string, \Closure(): void, class-string<\Throwable>|null}> */
    public static function exceptionRouteProvider(): array
    {
        return [
            'InvalidFieldException::for/直接型不一致' => [
                self::site('src/Entities/Entity.php', InvalidFieldException::class . '::for', 2),
                static function (): void {
                    new RequiredEntity(['count' => '1']);
                },
                null,
            ],
            'InvalidFieldException::for/Codのリスト形状不一致' => [
                self::site('src/Entities/Payment/Cod.php', InvalidFieldException::class . '::for', 1),
                static function (): void {
                    new Cod(['changeable' => true, 'fees' => ['first' => [300, 100]]]);
                },
                null,
            ],
            'InvalidFieldException::for/単体enum変換失敗' => [
                self::site('src/Entities/Entity.php', InvalidFieldException::class . '::for', 1),
                static function (): void {
                    new ComplexEntity(['state' => 'unknown']);
                },
                \UnexpectedValueException::class,
            ],
            'InvalidFieldException::for/readonly再代入失敗' => [
                self::site('src/Entities/Entity.php', InvalidFieldException::class . '::for', 3),
                static function (): void {
                    new PromotedReadonlyFieldEntity(['name' => 'api']);
                },
                \Error::class,
            ],
            'InvalidFieldException::for/子カテゴリーのリスト形状不一致' => [
                self::site('src/Entities/Product/BigCategory.php', InvalidFieldException::class . '::for', 1),
                static function (): void {
                    Category::fromArray([
                        'id_small' => 0,
                        'children' => ['first' => ['id_small' => 1]],
                    ]);
                },
                null,
            ],
            'InvalidFieldException::for/カテゴリー識別子欠損' => [
                self::site('src/Entities/Product/Category.php', InvalidFieldException::class . '::for', 1),
                static function (): void {
                    Category::fromArray([]);
                },
                null,
            ],
            'InvalidFieldException::forArrayElement/未知enum' => [
                self::site('src/Entities/Entity.php', InvalidFieldException::class . '::forArrayElement', 1),
                static function (): void {
                    new ComplexEntity(['states' => ['sent', 'unknown']]);
                },
                \UnexpectedValueException::class,
            ],
            'InvalidFieldException::forArrayElement/Codのタプル不一致' => [
                self::site('src/Entities/Payment/Cod.php', InvalidFieldException::class . '::forArrayElement', 1),
                static function (): void {
                    new Cod(['changeable' => true, 'fees' => [[300, 'invalid']]]);
                },
                \UnexpectedValueException::class,
            ],
            'InvalidFieldException::forArrayElement/カテゴリー要素型不一致' => [
                self::site('src/Services/Product.php', InvalidFieldException::class . '::forArrayElement', 1),
                static function (): void {
                    $mock = HttpMock::json(200, '{"categories":[null]}');
                    (new Product('my-token', $mock->client()))->categories();
                },
                \TypeError::class,
            ],
            'InvalidFieldException::forArrayElement/子カテゴリー要素型不一致' => [
                self::site('src/Entities/Product/BigCategory.php', InvalidFieldException::class . '::forArrayElement', 1),
                static function (): void {
                    Category::fromArray(['id_small' => 0, 'children' => [null]]);
                },
                \TypeError::class,
            ],
            'InvalidFieldException::forArrayElement/重量別配送料要素型不一致' => [
                self::site('src/Entities/Delivery/Charge.php', InvalidFieldException::class . '::forArrayElement', 1),
                static function (): void {
                    new Charge(['charge_ranges_by_weight' => [null]]);
                },
                \UnexpectedValueException::class,
            ],
            'InvalidFieldException::forArrayElement/商品グループID' => [
                self::site('src/Entities/Product/Product.php', InvalidFieldException::class . '::forArrayElement', 1),
                static function (): void {
                    new \Shimoning\ColorMeShopApi\Entities\Product\Product(['group_ids' => ['bad']]);
                },
                \TypeError::class,
            ],
            'InvalidFieldException::forArrayElement/商品決済ID' => [
                self::site('src/Entities/Product/Product.php', InvalidFieldException::class . '::forArrayElement', 2),
                static function (): void {
                    new \Shimoning\ColorMeShopApi\Entities\Product\Product(['unavailable_payment_ids' => ['bad']]);
                },
                \TypeError::class,
            ],
            'InvalidFieldException::forArrayElement/商品配送ID' => [
                self::site('src/Entities/Product/Product.php', InvalidFieldException::class . '::forArrayElement', 3),
                static function (): void {
                    new \Shimoning\ColorMeShopApi\Entities\Product\Product(['unavailable_delivery_ids' => ['bad']]);
                },
                \TypeError::class,
            ],
            'InvalidFieldException::forArrayElement/オプション値' => [
                self::site('src/Entities/Product/Option.php', InvalidFieldException::class . '::forArrayElement', 1),
                static function (): void {
                    new \Shimoning\ColorMeShopApi\Entities\Product\Option(['values' => [1]]);
                },
                \TypeError::class,
            ],
            'InvalidFieldException::for/商品入力のgroup_idsのリスト形状不一致' => [
                self::site('src/Entities/Product/ProductInput.php', InvalidFieldException::class . '::for', 1),
                static function (): void {
                    new \Shimoning\ColorMeShopApi\Entities\Product\ProductInput(['group_ids' => ['a' => 301]]);
                },
                null,
            ],
            'InvalidFieldException::forArrayElement/商品入力のgroup_ids要素' => [
                self::site('src/Entities/Product/ProductInput.php', InvalidFieldException::class . '::forArrayElement', 1),
                static function (): void {
                    new \Shimoning\ColorMeShopApi\Entities\Product\ProductInput(['group_ids' => ['301']]);
                },
                \TypeError::class,
            ],
            'InvalidFieldException::for/商品入力のstocksのobject形状不一致' => [
                self::site('src/Entities/Product/ProductInput.php', InvalidFieldException::class . '::for', 2),
                static function (): void {
                    new \Shimoning\ColorMeShopApi\Entities\Product\ProductInput(['stocks' => ['incr' => 1]]);
                },
                null,
            ],
            'InvalidFieldException::for/商品入力のvariantsのリスト形状不一致' => [
                self::site('src/Entities/Product/ProductInput.php', InvalidFieldException::class . '::for', 3),
                static function (): void {
                    new \Shimoning\ColorMeShopApi\Entities\Product\ProductInput(['variants' => ['option1_value' => 'S']]);
                },
                null,
            ],
            'InvalidFieldException::forArrayElement/商品入力のvariants要素' => [
                self::site('src/Entities/Product/ProductInput.php', InvalidFieldException::class . '::forArrayElement', 2),
                static function (): void {
                    new \Shimoning\ColorMeShopApi\Entities\Product\ProductInput(['variants' => [['weight' => 1]]]);
                },
                \TypeError::class,
            ],
            'InvalidFieldException::for/オプション入力のvaluesのリスト形状不一致' => [
                self::site('src/Entities/Product/OptionCreateInput.php', InvalidFieldException::class . '::for', 1),
                static function (): void {
                    new \Shimoning\ColorMeShopApi\Entities\Product\OptionCreateInput(['values' => [1 => ['name' => 'S']]]);
                },
                null,
            ],
            'InvalidFieldException::forArrayElement/オプション入力のvaluesの要素形状不一致' => [
                self::site('src/Entities/Product/OptionCreateInput.php', InvalidFieldException::class . '::forArrayElement', 1),
                static function (): void {
                    new \Shimoning\ColorMeShopApi\Entities\Product\OptionCreateInput(['values' => [[]]]);
                },
                \TypeError::class,
            ],
            'InvalidFieldException::for/グループ入力のmeta_tagの形状不一致' => [
                self::site('src/Entities/Product/MetaTagInput.php', InvalidFieldException::class . '::for', 1),
                static function (): void {
                    new \Shimoning\ColorMeShopApi\Entities\Product\GroupInput(['meta_tag' => []]);
                },
                null,
            ],
            'InvalidFieldException::for/グループ入力のdisplay_stateが応答専用の値' => [
                self::site('src/Entities/Product/GroupInput.php', InvalidFieldException::class . '::for', 1),
                static function (): void {
                    new \Shimoning\ColorMeShopApi\Entities\Product\GroupInput(['display_state' => 'sale_for_members']);
                },
                null,
            ],
            'InvalidFieldException::for/カテゴリー書き込み応答のcategoryが配列以外' => [
                self::site('src/Services/Product.php', InvalidFieldException::class . '::for', 1),
                static function (): void {
                    $mock = HttpMock::json(200, '{"category":"x"}');
                    (new Product('my-token', $mock->client()))->updateCategory(
                        1,
                        new \Shimoning\ColorMeShopApi\Entities\Product\CategoryInput(['name' => 'x']),
                    );
                },
                null,
            ],
            'InvalidFieldException::for/カテゴリー書き込み応答の親子型不一致' => [
                self::site('src/Services/Product.php', InvalidFieldException::class . '::for', 2),
                static function (): void {
                    $mock = HttpMock::json(201, '{"category":{"id_big":1,"id_small":5}}');
                    (new Product('my-token', $mock->client()))->createCategory(
                        new \Shimoning\ColorMeShopApi\Entities\Product\CategoryInput(['name' => 'x']),
                    );
                },
                null,
            ],
            'InvalidFieldException::forArrayElement/広告の色' => [
                self::site('src/Entities/Product/Advertising.php', InvalidFieldException::class . '::forArrayElement', 1),
                static function (): void {
                    new \Shimoning\ColorMeShopApi\Entities\Product\Advertising(['colors' => [1]]);
                },
                \TypeError::class,
            ],
            'InvalidFieldException::forArrayElement/広告のサイズ' => [
                self::site('src/Entities/Product/Advertising.php', InvalidFieldException::class . '::forArrayElement', 2),
                static function (): void {
                    new \Shimoning\ColorMeShopApi\Entities\Product\Advertising(['sizes' => [1]]);
                },
                \TypeError::class,
            ],
            'InvalidFieldException::forArrayElement/商品検索ID' => [
                self::site('src/Entities/Product/SearchParameters.php', InvalidFieldException::class . '::forArrayElement', 1),
                static function (): void {
                    new \Shimoning\ColorMeShopApi\Entities\Product\SearchParameters(['ids' => ['bad']]);
                },
                \TypeError::class,
            ],
            'InvalidFieldException::forArrayElement/広告検索の商品ID' => [
                self::site('src/Entities/Product/AdvertisingSearchParameters.php', InvalidFieldException::class . '::forArrayElement', 1),
                static function (): void {
                    new \Shimoning\ColorMeShopApi\Entities\Product\AdvertisingSearchParameters(['product_ids' => ['bad']]);
                },
                \TypeError::class,
            ],
            'MissingFieldException::for/必須フィールド欠損' => [
                self::site('src/Entities/Entity.php', MissingFieldException::class . '::for', 1),
                static function (): void {
                    (new RequiredEntity([]))->getName();
                },
                null,
            ],
            'MissingFieldException::for/property解決失敗' => [
                self::site('src/Entities/Entity.php', MissingFieldException::class . '::for', 2),
                static function (): void {
                    // hydrateField() からは事前判定済みなので、この防御的分岐を直接検証する。
                    (new \ReflectionMethod(Entity::class, 'property'))
                        ->invoke(null, RequiredEntity::class, 'unknownField');
                },
                null,
            ],
            'MissingFieldException::for/受注顧客の後方互換経路' => [
                self::site('src/Entities/Sales/Sale.php', MissingFieldException::class . '::for', 1),
                static function (): void {
                    (new Sale([]))->getCustomer();
                },
                null,
            ],
            'InvalidPaginationException::__construct/meta型不一致' => [
                self::site('src/Entities/Pagination.php', InvalidPaginationException::class . '::__construct', 1),
                static function (): void {
                    new Pagination(null);
                },
                null,
            ],
            'InvalidPaginationException::__construct/ページング値型不一致' => [
                self::site('src/Entities/Pagination.php', InvalidPaginationException::class . '::__construct', 2),
                static function (): void {
                    new Pagination(['total' => '1', 'limit' => 10, 'offset' => 0]);
                },
                null,
            ],
            'MissingPaginationException::__construct/ページング値欠損' => [
                self::site('src/Entities/Pagination.php', MissingPaginationException::class . '::__construct', 1),
                static function (): void {
                    (new Pagination(['limit' => 10, 'offset' => 0]))->getTotal();
                },
                null,
            ],
            'MissingPaginationException::__construct/meta欠損' => [
                self::site('src/Entities/Page.php', MissingPaginationException::class . '::__construct', 1),
                static function (): void {
                    Page::build(NestedEntity::class, ['items' => []], 'items')->getTotal();
                },
                null,
            ],
        ];
    }

    /** @return array<string, array{\Closure(): void, class-string<\Throwable>|null}> */
    public static function additionalMessageProvider(): array
    {
        return [
            'MissingFieldException::for/宣言プロパティ不在' => [
                static function (): void {
                    (new InheritedPrivateFieldEntity([]))->assertUnknownField();
                },
                null,
            ],
            'InvalidFieldException::for/値オブジェクト変換失敗' => [
                static function (): void {
                    new ComplexEntity(['limit' => 0]);
                },
                ParameterException::class,
            ],
            'InvalidFieldException::for/変換後型不一致' => [
                static function (): void {
                    new HydratedTypeMismatchEntity(['child' => ['label' => 'child']]);
                },
                null,
            ],
            'InvalidFieldException::for/同一型表示の変換失敗' => [
                static function (): void {
                    throw InvalidFieldException::for(
                        self::class,
                        'items',
                        'array',
                        [],
                        new \RuntimeException('array_map(): Argument #2 must be of type array in /tmp/internal.php:123'),
                    );
                },
                \RuntimeException::class,
            ],
            'InvalidFieldException::for/10万要素配列' => [
                static function (): void {
                    throw InvalidFieldException::for(
                        self::class,
                        'items',
                        'string',
                        \array_fill(0, 100_000, 'value'),
                    );
                },
                null,
            ],
            'InvalidFieldException::for/循環参照配列' => [
                static function (): void {
                    $cyclic = [];
                    $cyclic['self'] = &$cyclic;

                    throw InvalidFieldException::for(self::class, 'items', 'string', $cyclic);
                },
                null,
            ],
            'InvalidFieldException::forArrayElement/要素型不一致' => [
                static function (): void {
                    new ComplexEntity(['states' => [1]]);
                },
                \TypeError::class,
            ],
            'InvalidFieldException::forArrayElement/分類不能な変換失敗' => [
                static function (): void {
                    throw InvalidFieldException::forArrayElement(
                        self::class,
                        'items',
                        MailState::class,
                        new \RuntimeException('internalFunction() called in /tmp/internal.php:123'),
                    );
                },
                \RuntimeException::class,
            ],
        ];
    }
}
