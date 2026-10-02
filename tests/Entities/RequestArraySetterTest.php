<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

class RequestArraySetterTest extends TestCase
{
    #[DataProvider('requestArraySetterProvider')]
    public function test_FIELD_TYPESの配列フィールドを更新するsetterは非リストを拒否する(
        string $class,
        string $setter,
        string $apiField,
        string $elementType,
    ): void {
        $entity = new $class([]);
        $element = new $elementType([]);

        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(
            $class . " の API フィールド『{$apiField}』が不正です。"
            . "list<{$elementType}> を期待しましたが array でした。",
        );

        $entity->{$setter}([3 => $element]);
    }

    /** @return array<string, array{class-string<Entity>, string, string, class-string<Entity>}> */
    public static function requestArraySetterProvider(): array
    {
        $cases = [];
        foreach (self::sourceEntityClasses() as $class) {
            if (! \is_subclass_of($class, RequestEntity::class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);
            foreach ($class::FIELD_TYPES as $property => $definition) {
                if (! \is_array($definition) || empty($definition['array'])) {
                    continue;
                }

                $setter = 'set' . \ucfirst($property);
                if (! $reflection->hasMethod($setter) || ! $reflection->getMethod($setter)->isPublic()) {
                    continue;
                }

                $elementType = $definition['entity'] ?? null;
                self::assertIsString($elementType, "{$class}::{$setter}() のテスト要素を生成できません。");
                $cases[$class . '::' . $setter] = [
                    $class,
                    $setter,
                    $class::apiFieldName($property),
                    $elementType,
                ];
            }
        }

        self::assertNotEmpty($cases, '配列フィールドを更新する RequestEntity setter を検出できませんでした。');
        return $cases;
    }

    /** @return list<class-string<Entity>> */
    private static function sourceEntityClasses(): array
    {
        $classes = [];
        $base = \realpath(__DIR__ . '/../../src/Entities');
        self::assertIsString($base);
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base));

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relative = \substr($file->getPathname(), \strlen($base) + 1, -4);
            $class = 'Shimoning\\ColorMeShopApi\\Entities\\'
                . \str_replace(\DIRECTORY_SEPARATOR, '\\', $relative);
            if (\class_exists($class) && \is_subclass_of($class, Entity::class)) {
                $classes[] = $class;
            }
        }

        \sort($classes);
        return $classes;
    }
}
