<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use Shimoning\ColorMeShopApi\Aliases;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * src/Entities の型宣言が互換用の旧クラス名に依存していないことを検証する。
 */
class LegacyAliasReferenceTest extends TestCase
{
    public function test_プロパティ型に旧クラス名を使わない(): void
    {
        $violations = [];
        foreach (self::entityReflections() as $class) {
            foreach ($class->getProperties() as $property) {
                foreach (self::typeNames($property->getType()) as $type) {
                    if (isset(Aliases::MAP[$type])) {
                        $violations[] = $class->getName() . '::$' . $property->getName() . ': ' . $type;
                    }
                }
            }
        }

        $this->assertSame([], $violations, "プロパティ型に旧クラス名があります:\n" . \implode("\n", $violations));
    }

    public function test_FIELD_TYPESに旧クラス名を使わない(): void
    {
        $violations = [];
        foreach (self::entityReflections() as $class) {
            /** @var array<string, array<string, mixed>> $fieldTypes */
            $fieldTypes = $class->getConstant('FIELD_TYPES');
            foreach ($fieldTypes as $field => $definition) {
                foreach (['entity', 'value', 'enum'] as $kind) {
                    $type = $definition[$kind] ?? null;
                    if (\is_string($type) && isset(Aliases::MAP[$type])) {
                        $violations[] = $class->getName() . '::FIELD_TYPES[' . $field . '][' . $kind . ']: ' . $type;
                    }
                }
            }
        }

        $this->assertSame([], $violations, "FIELD_TYPES に旧クラス名があります:\n" . \implode("\n", $violations));
    }

    public function test_publicメソッドの戻り値型に旧クラス名を使わない(): void
    {
        $violations = [];
        foreach (self::entityReflections() as $class) {
            foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                foreach (self::typeNames($method->getReturnType()) as $type) {
                    if (isset(Aliases::MAP[$type])) {
                        $violations[] = $class->getName() . '::' . $method->getName() . '(): ' . $type;
                    }
                }
            }
        }

        $this->assertSame([], $violations, "戻り値型に旧クラス名があります:\n" . \implode("\n", $violations));
    }

    public function test_PHPDocのvarとreturnに旧クラス名を使わない(): void
    {
        $violations = [];
        foreach (self::entityReflections() as $class) {
            foreach ($class->getProperties() as $property) {
                self::collectDocViolations(
                    $violations,
                    $class,
                    $property->getDocComment(),
                    'var',
                    $class->getName() . '::$' . $property->getName(),
                );
            }
            foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                self::collectDocViolations(
                    $violations,
                    $class,
                    $method->getDocComment(),
                    'return',
                    $class->getName() . '::' . $method->getName() . '()',
                );
            }
        }

        $this->assertSame([], $violations, "PHPDoc に旧クラス名があります:\n" . \implode("\n", $violations));
    }

    /** @return list<ReflectionClass<Entity>> */
    private static function entityReflections(): array
    {
        $reflections = [];
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
            if ($reflection->getName() !== Entity::class && $reflection->isSubclassOf(Entity::class)) {
                $reflections[] = $reflection;
            }
        }

        return $reflections;
    }

    /** @return list<string> */
    private static function typeNames(?ReflectionType $type): array
    {
        if ($type instanceof ReflectionNamedType) {
            return [$type->getName()];
        }
        if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            return \array_merge(...\array_map(self::typeNames(...), $type->getTypes()));
        }
        return [];
    }

    /** @param list<string> $violations */
    private static function collectDocViolations(
        array &$violations,
        ReflectionClass $class,
        string|false $doc,
        string $tag,
        string $location,
    ): void {
        if (! \is_string($doc)
            || \preg_match('/@' . $tag . '\\s+([^\\s*]+)/', $doc, $matches) !== 1
        ) {
            return;
        }

        foreach (self::docClassNames($class, $matches[1]) as $type) {
            if (isset(Aliases::MAP[$type])) {
                $violations[] = $location . ' @' . $tag . ': ' . $type;
            }
        }
    }

    /** @return list<string> */
    private static function docClassNames(ReflectionClass $class, string $type): array
    {
        \preg_match_all('/\\??\\b[A-Z][A-Za-z0-9_\\\\]*/', $type, $matches);
        $imports = self::imports($class);
        $classes = [];
        foreach ($matches[0] as $name) {
            $absolute = \str_starts_with($name, '\\');
            $name = \ltrim($name, '\\');
            $first = \strstr($name, '\\', true) ?: $name;
            if ($absolute) {
                $classes[] = $name;
            } elseif (isset($imports[$first])) {
                $classes[] = $imports[$first] . \substr($name, \strlen($first));
            } else {
                $classes[] = $class->getNamespaceName() . '\\' . $name;
            }
        }
        return $classes;
    }

    /** @return array<string, string> */
    private static function imports(ReflectionClass $class): array
    {
        $file = $class->getFileName();
        if (! \is_string($file)) {
            return [];
        }
        $source = \file_get_contents($file);
        if (! \is_string($source)) {
            return [];
        }
        \preg_match_all('/^use\\s+([^;]+);/m', $source, $matches);
        $imports = [];
        foreach ($matches[1] as $import) {
            $parts = \preg_split('/\\s+as\\s+/i', $import);
            if (! \is_array($parts)) {
                continue;
            }
            $name = $parts[0];
            $alias = $parts[1] ?? \substr($name, (int) \strrpos($name, '\\') + 1);
            $imports[$alias] = $name;
        }
        return $imports;
    }
}
