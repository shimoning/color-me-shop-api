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
            $annotation = self::annotation($getter->getDocComment(), 'return');
            if ($annotation === null || ! self::isListAnnotation($annotation)) {
                $violations[] = $class->getName() . '::' . $getter->getName() . '(): @return '
                    . ($annotation ?? '(なし)');
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
            $annotation = self::annotation($property->getDocComment(), 'var');
            if ($annotation === null || ! self::isListAnnotation($annotation)) {
                $violations[] = $class->getName() . '::$' . $property->getName() . ': @var '
                    . ($annotation ?? '(なし)');
            }
        }

        $this->assertGreaterThan(20, \count($properties), '配列フィールドを検出できませんでした。');
        $this->assertSame([], $violations, "配列フィールドに @var で要素型を宣言してください:\n" . \implode("\n", $violations));
    }

    public function test_list形式のアノテーションを厳密に判定する(): void
    {
        $this->assertTrue(self::isListAnnotation('list<int>'));
        $this->assertTrue(self::isListAnnotation('list<int>|null'));
        $this->assertTrue(self::isListAnnotation('list<array{int, int}>'));
        $this->assertTrue(self::isListAnnotation('list<list<int>>'));
        $this->assertFalse(self::isListAnnotation('array<int>'));
        $this->assertFalse(self::isListAnnotation('int[]'));
        $this->assertFalse(self::isListAnnotation('array'));
        $this->assertFalse(self::isListAnnotation('list<int>|array<string>'));
    }

    private static function annotation(string|false $document, string $tag): ?string
    {
        if (! \is_string($document)
            || \preg_match('/@' . \preg_quote($tag, '/') . '\s+([^\r\n]*)/', $document, $matches) !== 1
        ) {
            return null;
        }

        $annotation = \preg_replace('/\s*\*\/\s*$/', '', $matches[1]);
        return \is_string($annotation) ? \trim($annotation) : null;
    }

    private static function isListAnnotation(string $annotation): bool
    {
        if (! \str_starts_with($annotation, 'list<')) {
            return false;
        }

        $depth = 0;
        $outerClosingPosition = null;
        $length = \strlen($annotation);

        // 入れ子のジェネリクスも扱えるよう、山括弧の深さを数えて外側の終端を探す。
        for ($position = 4; $position < $length; $position++) {
            if ($annotation[$position] === '<') {
                $depth++;
                continue;
            }

            if ($annotation[$position] !== '>') {
                continue;
            }

            $depth--;
            if ($depth === 0) {
                $outerClosingPosition = $position;
                break;
            }

            if ($depth < 0) {
                return false;
            }
        }

        if ($outerClosingPosition === null || $outerClosingPosition === 5) {
            return false;
        }

        $remainder = \substr($annotation, $outerClosingPosition + 1);
        if (\str_starts_with($remainder, '|null')) {
            $remainder = \substr($remainder, 5);
        }

        if ($remainder === '') {
            return true;
        }

        // 型の後ろの説明文は許容するが、空白を挟んだ union は拒否する。
        if (! \ctype_space($remainder[0])) {
            return false;
        }

        return ! \str_starts_with(\ltrim($remainder), '|');
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
            $containsArray = false;
            foreach ($type->getTypes() as $member) {
                if ($member->getName() === 'array') {
                    $containsArray = true;
                    continue;
                }

                // 配列と null 以外の型を取るフィールドはリスト型フィールドではない。
                if ($member->getName() !== 'null') {
                    return false;
                }
            }

            return $containsArray;
        }

        return false;
    }
}
