<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;
use Shimoning\ColorMeShopApi\Exceptions\MissingPaginationException;

/**
 * src/Entities 配下の全エンティティが満たすべき共通契約を検証する。
 *
 * API レスポンスに含まれないフィールドを参照したときの挙動は、
 * このライブラリで繰り返し不具合の原因になっているため横断的に固定する。
 */
class EntityContractTest extends TestCase
{
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

    public function test_privateインスタンスプロパティも必須とnullableの判定対象に含める(): void
    {
        $entity = new class([]) extends Entity {
            private string $requiredValue;
            private ?string $optionalValue;
        };
        $reflection = new ReflectionClass($entity);

        $this->assertTrue(self::isRequired($reflection->getProperty('requiredValue')));
        $this->assertTrue(self::isOptional($reflection->getProperty('optionalValue')));
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
                    // 欠損契約を適用済みの getter は固有例外を正常系として扱う。
                }
            }
        } finally {
            \restore_error_handler();
        }

        $this->assertSame([], $undefined, $reflection->getShortName() . ' に未宣言のプロパティ参照がある');
    }

    /**
     * 既定値のない非 null 許容プロパティは、空のレスポンスで getter を呼ぶと
     * PHP の未初期化 Error ではなく MissingFieldException を投げること。
     */
    #[DataProvider('entityProvider')]
    public function test_非nullableのプロパティは空のレスポンスで固有例外を投げる(string $class): void
    {
        $reflection = new ReflectionClass($class);
        $entity = new $class([]);

        $checked = 0;
        foreach ($reflection->getProperties() as $property) {
            if (! self::isRequired($property)) {
                continue;
            }
            $getter = self::findGetter($reflection, $property);
            if ($getter === null) {
                continue;
            }

            $checked++;
            try {
                $getter->invoke($entity);
            } catch (MissingFieldException $error) {
                $apiField = $class::apiFieldName($property->getName());
                $expectedMessage = $error instanceof MissingPaginationException
                    ? \sprintf(
                        'API レスポンスにページネーション情報「meta.%s」がありません。ページング値を取得できません。',
                        $apiField,
                    )
                    : MissingFieldException::for($class, $apiField)->getMessage();
                $this->assertSame(
                    $expectedMessage,
                    $error->getMessage(),
                    \sprintf(
                        '%s::%s() が期待する API フィールド『%s』を報告しない',
                        $reflection->getShortName(),
                        $getter->getName(),
                        $apiField,
                    ),
                );
                continue;
            } catch (\Throwable $error) {
                $this->fail(\sprintf(
                    '%s::%s() が %s を投げた: %s',
                    $reflection->getShortName(),
                    $getter->getName(),
                    $error::class,
                    $error->getMessage(),
                ));
            }

            $this->fail(\sprintf(
                '%s::%s() が MissingFieldException を投げない',
                $reflection->getShortName(),
                $getter->getName(),
            ));
        }

        $this->addToAssertionCount($checked === 0 ? 1 : $checked);
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
        if ($property->isStatic()) {
            return false;
        }
        $type = $property->getType();

        return $type !== null && $type->allowsNull();
    }

    private static function isRequired(ReflectionProperty $property): bool
    {
        if ($property->isStatic() || $property->hasDefaultValue()) {
            return false;
        }
        $type = $property->getType();

        return $type !== null && ! $type->allowsNull();
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
