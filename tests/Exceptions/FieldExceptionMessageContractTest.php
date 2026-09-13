<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Exceptions;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Constants\MailState;
use Shimoning\ColorMeShopApi\Entities\Page;
use Shimoning\ColorMeShopApi\Entities\Pagination;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use Shimoning\ColorMeShopApi\Tests\Doubles\ComplexEntity;
use Shimoning\ColorMeShopApi\Tests\Doubles\HydratedTypeMismatchEntity;
use Shimoning\ColorMeShopApi\Tests\Doubles\InheritedPrivateFieldEntity;
use Shimoning\ColorMeShopApi\Tests\Doubles\NestedEntity;
use Shimoning\ColorMeShopApi\Tests\Doubles\PromotedReadonlyFieldEntity;
use Shimoning\ColorMeShopApi\Tests\Doubles\RequiredEntity;

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
        \Closure $throwing,
        ?string $previousClass,
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
        /*
         * 限界: 生成箇所の検知は正規表現と経路別の件数に基づく。FQCN の直接 constructor 呼び出し、
         * alias・変数経由・サブクラス factory による生成、生成後に別の場所で throw するケースは見逃し、
         * コメントや文字列リテラルは誤検知しうる。件数だけ更新すれば provider を追加せずに通るため、
         * 将来の経路の自動的な対象化は保証しない。恒久的には token_get_all() 等でコメント・文字列を除外し、
         * 名前解決した call-site 一覧を抽出して call-site ID と provider を1対1で照合する必要がある。
         */
        $routeCounts = [];
        $sourceDirectory = \dirname(__DIR__, 2) . '/src';
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sourceDirectory));

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $source = \file_get_contents($file->getPathname());
            $this->assertIsString($source);

            \preg_match_all(
                '/\b(?:throw|return)\s+new\s+'
                . '(InvalidFieldException|MissingFieldException|InvalidPaginationException|MissingPaginationException)\s*\(/',
                $source,
                $constructors,
            );
            foreach ($constructors[1] as $class) {
                $route = $class . '::__construct';
                $routeCounts[$route] = ($routeCounts[$route] ?? 0) + 1;
            }

            \preg_match_all(
                '/\b(InvalidFieldException|MissingFieldException)::([A-Za-z_][A-Za-z0-9_]*)\s*\(/',
                $source,
                $factories,
            );
            foreach ($factories[1] as $index => $class) {
                $route = $class . '::' . $factories[2][$index];
                $routeCounts[$route] = ($routeCounts[$route] ?? 0) + 1;
            }
        }

        \ksort($routeCounts);
        $this->assertSame(
            [
                'InvalidFieldException::for' => 3,
                'InvalidFieldException::forArrayElement' => 1,
                'InvalidPaginationException::__construct' => 2,
                'MissingFieldException::for' => 2,
                'MissingPaginationException::__construct' => 2,
            ],
            $routeCounts,
        );
    }

    /**
     * @return array<string, array{\Closure(): void, class-string<\Throwable>|null}>
     */
    public static function exceptionRouteProvider(): array
    {
        return [
            'InvalidFieldException::for/直接型不一致' => [
                static function (): void {
                    new RequiredEntity(['count' => '1']);
                },
                null,
            ],
            'InvalidFieldException::for/単体enum変換失敗' => [
                static function (): void {
                    new ComplexEntity(['state' => 'unknown']);
                },
                \UnexpectedValueException::class,
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
            'InvalidFieldException::for/readonly再代入失敗' => [
                static function (): void {
                    new PromotedReadonlyFieldEntity(['name' => 'api']);
                },
                \Error::class,
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
            'InvalidFieldException::forArrayElement/未知enum' => [
                static function (): void {
                    new ComplexEntity(['states' => ['sent', 'unknown']]);
                },
                \UnexpectedValueException::class,
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
            'MissingFieldException::for/必須フィールド欠損' => [
                static function (): void {
                    (new RequiredEntity([]))->getName();
                },
                null,
            ],
            'MissingFieldException::for/宣言プロパティ不在' => [
                static function (): void {
                    (new InheritedPrivateFieldEntity([]))->assertUnknownField();
                },
                null,
            ],
            'InvalidPaginationException::__construct/meta型不一致' => [
                static function (): void {
                    new Pagination(null);
                },
                null,
            ],
            'InvalidPaginationException::__construct/ページング値型不一致' => [
                static function (): void {
                    new Pagination(['total' => '1', 'limit' => 10, 'offset' => 0]);
                },
                null,
            ],
            'MissingPaginationException::__construct/ページング値欠損' => [
                static function (): void {
                    (new Pagination(['limit' => 10, 'offset' => 0]))->getTotal();
                },
                null,
            ],
            'MissingPaginationException::__construct/meta欠損' => [
                static function (): void {
                    Page::build(NestedEntity::class, ['items' => []], 'items')->getTotal();
                },
                null,
            ],
        ];
    }
}
