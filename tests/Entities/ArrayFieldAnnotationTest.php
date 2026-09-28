<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * src/Entities 配下の配列フィールドに要素型が記載されていることを横断的に検証する。
 */
class ArrayFieldAnnotationTest extends TestCase
{
    public function test_配列フィールドのgetterはlist形式の戻り値を宣言する(): void
    {
        $violations = [];
        $checked = 0;

        foreach (self::arrayProperties() as [$class, $property]) {
            $getterName = 'get' . \ucfirst($property->getName());
            if (! $class->hasMethod($getterName)) {
                continue;
            }

            $getter = $class->getMethod($getterName);
            if (! $getter->isPublic() || ! self::containsArray($getter->getReturnType())) {
                continue;
            }

            $checked++;
            $document = $getter->getDocComment();
            if (! \is_string($document)
                || \preg_match('/@return\s+list<.+>(?:\|null)?(?:\s|\*|$)/', $document) !== 1
            ) {
                $violations[] = $class->getName() . '::' . $getter->getName() . '()';
            }
        }

        // Entity の配列フィールドに対応する get<Property>() だけを対象にすることで、
        // 連想配列を返す Entity::toArray()/toArrayRecursive()/getRaw() と
        // Entity ではない Collection::all() は判定基準から除外される。
        $this->assertGreaterThan(15, $checked, '配列フィールドの getter を検出できませんでした。');
        $this->assertSame([], $violations, "@return を list<...> 形式にしてください:\n" . \implode("\n", $violations));
    }

    public function test_配列フィールドのプロパティはvarで要素型を宣言する(): void
    {
        $violations = [];
        $properties = self::arrayProperties();

        foreach ($properties as [$class, $property]) {
            $document = $property->getDocComment();
            if (! \is_string($document) || \preg_match('/@var\s+\S+/', $document) !== 1) {
                $violations[] = $class->getName() . '::$' . $property->getName();
            }
        }

        $this->assertGreaterThan(20, \count($properties), '配列フィールドを検出できませんでした。');
        $this->assertSame([], $violations, "配列フィールドに @var で要素型を宣言してください:\n" . \implode("\n", $violations));
    }

    /**
     * ソースツリーから Entity の具象・抽象サブクラスを集め、各クラス自身が宣言した
     * 配列型プロパティを返す。基底 Entity の内部管理用マップは Entity フィールドではない。
     *
     * @return list<array{ReflectionClass<Entity>, ReflectionProperty}>
     */
    private static function arrayProperties(): array
    {
        $properties = [];
        foreach (self::sourceEntityClasses() as $class) {
            $reflection = new ReflectionClass($class);
            foreach ($reflection->getProperties() as $property) {
                if ($property->getDeclaringClass()->getName() !== $class
                    || $property->isStatic()
                    || ! self::containsArray($property->getType())
                ) {
                    continue;
                }

                $properties[] = [$reflection, $property];
            }
        }

        return $properties;
    }

    /** @return list<class-string<Entity>> */
    private static function sourceEntityClasses(): array
    {
        $classes = [];
        $base = \realpath(__DIR__ . '/../../src/Entities');
        self::assertIsString($base);
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
            if ($reflection->isSubclassOf(Entity::class)) {
                $classes[] = $class;
            }
        }

        \sort($classes);
        return $classes;
    }

    private static function containsArray(?ReflectionType $type): bool
    {
        if ($type instanceof ReflectionNamedType) {
            return $type->getName() === 'array';
        }

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $member) {
                if ($member->getName() === 'array') {
                    return true;
                }
            }
        }

        return false;
    }
}
