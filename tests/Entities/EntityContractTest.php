<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;

/**
 * src/Entities 配下の全エンティティが満たすべき共通契約を検証する。
 *
 * API レスポンスに含まれないフィールドを参照したときの挙動は、
 * このライブラリで繰り返し不具合の原因になっているため横断的に固定する。
 */
class EntityContractTest extends TestCase
{
    /**
     * PR2 以降で MissingFieldException へ移行するまで暫定許容する未初期化フィールド。
     *
     * @var array<class-string, array<string>>
     */
    private const TEMPORARILY_UNINITIALIZED_FIELDS = [
        \Shimoning\ColorMeShopApi\Entities\Delivery\Area::class => [
            'prefId', 'prefName', 'charge',
        ],
        \Shimoning\ColorMeShopApi\Entities\Delivery\Weight::class => [
            'weight', 'areas',
        ],
        \Shimoning\ColorMeShopApi\Entities\Error::class => [
            'code', 'message', 'status',
        ],
        \Shimoning\ColorMeShopApi\Entities\OAuth\AccessToken::class => [
            'accessToken', 'tokenType', 'createdAt',
        ],
        \Shimoning\ColorMeShopApi\Entities\Payment\Brand::class => [
            'id', 'name',
        ],
        \Shimoning\ColorMeShopApi\Entities\Payment\Card::class => [
            'brands',
        ],
        \Shimoning\ColorMeShopApi\Entities\Payment\Cod::class => [
            'changeable', 'fees', 'feeMax', 'changeableByTotal',
        ],
        \Shimoning\ColorMeShopApi\Entities\Payment\Financial::class => [
            'name', 'branchName', 'kouzaType', 'kouzaNumber', 'kouzaName',
        ],
        \Shimoning\ColorMeShopApi\Entities\Payment\Payment::class => [
            'id', 'accountId', 'name', 'type', 'display', 'useMobile', 'makeDate', 'updateDate',
        ],
        \Shimoning\ColorMeShopApi\Entities\Product\Group::class => [
            'id', 'accountId', 'name', 'displayState',
        ],
    ];

    /**
     * PR2 以降で MissingFieldException へ移行するまで暫定許容する null 戻り値フィールド。
     *
     * @var array<class-string, array<string>>
     */
    private const TEMPORARILY_NULL_RETURN_FIELDS = [];

    /**
     * @return array<string, array{class-string}>
     */
    public static function entityProvider(): array
    {
        $cases = [];
        foreach (self::entityClasses() as $class) {
            // Sales\SearchParameters と Customer\SearchParameters のように短縮名が衝突する
            // クラスがあるため、名前空間を含めた一意なキーにする。
            // 短縮名をキーにすると後勝ちで上書きされ、検査対象から黙って漏れてしまう
            $key = \str_replace('Shimoning\\ColorMeShopApi\\Entities\\', '', $class);
            $cases[$key] = [$class];
        }
        return $cases;
    }

    /**
     * 配列だけで生成できる Entity のサブクラスを集める
     *
     * @return array<class-string>
     */
    private static function entityClasses(): array
    {
        $classes = [];
        $base = \realpath(__DIR__ . '/../../src/Entities');
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base));

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $relative = \substr($file->getPathname(), \strlen($base) + 1);
            $class = 'Shimoning\\ColorMeShopApi\\Entities\\'
                . \str_replace([\DIRECTORY_SEPARATOR, '.php'], ['\\', ''], $relative);

            if (! \class_exists($class)) {
                continue;
            }
            $reflection = new ReflectionClass($class);
            if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Entity::class)) {
                continue;
            }
            // Page など、追加の必須引数を取るものは対象外
            $constructor = $reflection->getConstructor();
            if ($constructor && $constructor->getNumberOfRequiredParameters() > 1) {
                continue;
            }
            $classes[] = $class;
        }

        \sort($classes);
        return $classes;
    }

    /**
     * 引数なしで呼べる public なゲッター
     *
     * @return array<ReflectionMethod>
     */
    private static function getters(ReflectionClass $reflection): array
    {
        $excluded = ['__construct', 'toArray', 'toArrayRecursive', 'getRaw', 'parse', 'isHash'];

        return \array_values(\array_filter(
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
            fn(ReflectionMethod $m) => ! $m->isStatic()
                && $m->getNumberOfRequiredParameters() === 0
                && ! \in_array($m->getName(), $excluded, true),
        ));
    }

    public function test_対象のエンティティが検出できている(): void
    {
        $this->assertGreaterThan(15, \count(self::entityClasses()));
    }

    /**
     * データプロバイダのキー衝突で検査対象が漏れていないこと。
     *
     * 短縮名をキーにしていたときは Customer\SearchParameters が
     * Sales\SearchParameters に上書きされ、黙って検査されなくなっていた。
     */
    public function test_検出した全エンティティがデータプロバイダに含まれる(): void
    {
        $provided = \array_map(static fn(array $case) => $case[0], self::entityProvider());

        $this->assertCount(\count(self::entityClasses()), $provided);
        $this->assertEqualsCanonicalizing(self::entityClasses(), \array_values($provided));
    }

    /**
     * 宣言されていないプロパティを参照しているゲッターがないこと。
     *
     * Product\Category が $idBig ではなく存在しない $id を返していた、
     * Payment\Financial がプロパティ名の綴りを誤っていた、といった不具合の再発防止。
     * これらは実行時に必ず失敗するため、テストがなければ気づけない。
     */
    #[DataProvider('entityProvider')]
    public function test_存在しないプロパティを参照しているゲッターがない(string $class): void
    {
        $reflection = new ReflectionClass($class);
        $entity = new $class([]);

        $undefined = [];
        $uninitialized = [];
        $nullReturns = [];
        // 「未宣言プロパティの参照」だけを捕捉する。それ以外は false を返して
        // 通常のエラー処理に委ね、想定外の警告が握りつぶされないようにする
        \set_error_handler(static function (int $severity, string $message) use (&$undefined): bool {
            if (! \str_contains($message, 'Undefined property')) {
                return false;
            }
            $undefined[] = $message;
            return true;
        });

        try {
            foreach (self::getters($reflection) as $getter) {
                try {
                    $getter->invoke($entity);
                } catch (MissingFieldException) {
                    // 基底の欠損検証を適用済みの getter は汎用例外を正常系として扱う。
                } catch (\Error $error) {
                    // 個別 Entity への適用は PR2〜PR4 のため、既知のケースだけ暫定許容する。
                    if (\preg_match(
                        '/::\$([A-Za-z0-9_]+) must not be accessed before initialization/',
                        $error->getMessage(),
                        $matches,
                    ) === 1) {
                        $uninitialized[] = $matches[1];
                        continue;
                    }

                    if ($error instanceof \TypeError && \str_contains($error->getMessage(), 'null returned')) {
                        $nullReturns[] = self::propertyNameFor($getter);
                        continue;
                    }

                    throw $error;
                }
            }
        } finally {
            \restore_error_handler();
        }

        $this->assertSame([], $undefined, $reflection->getShortName() . ' に未宣言のプロパティ参照がある');
        $this->assertAllowedFailures(
            $class,
            '未初期化 Error',
            self::TEMPORARILY_UNINITIALIZED_FIELDS[$class] ?? [],
            $uninitialized,
        );
        $this->assertAllowedFailures(
            $class,
            'null return TypeError',
            self::TEMPORARILY_NULL_RETURN_FIELDS[$class] ?? [],
            $nullReturns,
        );
    }

    private static function propertyNameFor(ReflectionMethod $getter): string
    {
        return \lcfirst((string) \preg_replace('/^(get|is)/', '', $getter->getName()));
    }

    /**
     * @param array<string> $expected
     * @param array<string> $actual
     */
    private function assertAllowedFailures(
        string $class,
        string $failure,
        array $expected,
        array $actual,
    ): void {
        \sort($expected);
        \sort($actual);

        $this->assertSame(
            $expected,
            $actual,
            $class . ' の暫定許容していない ' . $failure . '、または解消済みの許容があります',
        );
    }

    public function test_暫定許容件数が固定されている(): void
    {
        $uninitialized = \array_sum(\array_map('count', self::TEMPORARILY_UNINITIALIZED_FIELDS));
        $nullReturns = \array_sum(\array_map('count', self::TEMPORARILY_NULL_RETURN_FIELDS));

        $this->assertSame(35, $uninitialized);
        $this->assertSame(0, $nullReturns);
    }

    /**
     * null 許容のプロパティは、レスポンスに含まれていなくても
     * ゲッターが例外を投げず null を返すこと。
     *
     * Entities\Error::$field が 401 / 404 のレスポンスで未初期化エラーになっていた
     * 不具合と同種の問題を、全エンティティに対して防ぐ。
     */
    #[DataProvider('entityProvider')]
    public function test_null許容のプロパティは空のレスポンスでもnullを返す(string $class): void
    {
        $reflection = new ReflectionClass($class);
        $entity = new $class([]);

        $checked = 0;
        foreach ($reflection->getProperties() as $property) {
            if (! self::isOptional($property)) {
                continue;
            }
            $getter = self::findGetter($reflection, $property);
            if ($getter === null) {
                continue;
            }

            $checked++;
            $this->assertNull(
                $getter->invoke($entity),
                \sprintf('%s::%s() が null を返さない', $reflection->getShortName(), $getter->getName()),
            );
        }

        $this->addToAssertionCount($checked === 0 ? 1 : 0);
    }

    private static function isOptional(ReflectionProperty $property): bool
    {
        if ($property->isStatic() || $property->isPrivate()) {
            return false;
        }
        $type = $property->getType();

        return $type !== null && $type->allowsNull();
    }

    private static function findGetter(ReflectionClass $reflection, ReflectionProperty $property): ?ReflectionMethod
    {
        foreach (['get' . \ucfirst($property->getName()), 'is' . \ucfirst($property->getName())] as $name) {
            if ($reflection->hasMethod($name)) {
                $method = $reflection->getMethod($name);
                if ($method->isPublic() && $method->getNumberOfRequiredParameters() === 0) {
                    return $method;
                }
            }
        }
        return null;
    }
}
