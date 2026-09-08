<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities;

use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Tests\TestCase;

/**
 * API のフィールド名が往復すること (取り込み → 配列化) を検証する。
 *
 * Entity は取り込み時にアンダースコア区切りを camelCase へ、配列化時に大文字の前へ
 * アンダースコアを挿入する形で変換しているが、この2つは対称ではない。
 * 例えば shop_mail_1 は shopMail1 として取り込まれるが、配列化すると shop_mail1 になり
 * 元のキーに戻らない。
 *
 * 期待するフィールド名の一覧は公式の OpenAPI 仕様から生成している。
 *
 *   https://api.shop-pro.jp/v1/spec/open_api.json
 *
 * 更新するときは同じ仕様から tests/Fixtures/api_field_names.json を作り直すこと。
 */
class ApiFieldNameTest extends TestCase
{
    /**
     * @return array<string, array{class-string, array<string>}>
     */
    public static function entityFieldProvider(): array
    {
        $cases = [];
        foreach (self::fixtureArray('api_field_names.json') as $relative => $fields) {
            $cases[$relative] = ['Shimoning\\ColorMeShopApi\\Entities\\' . $relative, $fields];
        }
        return $cases;
    }

    public function test_検証対象のフィールドが読み込めている(): void
    {
        $fields = self::fixtureArray('api_field_names.json');

        $this->assertNotEmpty($fields);
        $this->assertGreaterThan(200, \array_sum(\array_map('count', $fields)));
    }

    /**
     * 配列化したときに、API と同じフィールド名で取り出せること。
     *
     * toArray() は宣言済みのプロパティをすべて列挙するため、
     * ここが通れば「API のフィールド名に対応するプロパティが存在し、
     * かつ同じ名前で取り出せる」ことが担保できる。
     */
    #[DataProvider('entityFieldProvider')]
    public function test_配列化するとAPIと同じフィールド名になる(string $class, array $fields): void
    {
        $keys = \array_keys((new $class([]))->toArray());
        $shortName = (new ReflectionClass($class))->getShortName();

        foreach ($fields as $field) {
            $this->assertContains(
                $field,
                $keys,
                \sprintf('%s: toArray() のキーに %s がない', $shortName, $field),
            );
        }
    }

    /**
     * API のフィールド名で渡した値が、対応するプロパティに取り込まれること。
     *
     * 型に応じた値を用意できるスカラーのプロパティだけを対象にする。
     * enum や値オブジェクトは変換に固有の形式が必要なため除外する。
     */
    #[DataProvider('entityFieldProvider')]
    public function test_APIのフィールド名で値を取り込める(string $class, array $fields): void
    {
        $reflection = new ReflectionClass($class);
        $shortName = $reflection->getShortName();
        $checked = 0;

        foreach ($fields as $field) {
            $property = self::propertyFor($class, $reflection, $field);
            if ($property === null) {
                $this->fail(\sprintf('%s: %s に対応するプロパティが見つからない', $shortName, $field));
            }

            $value = self::sampleValue($reflection->getProperty($property));
            if ($value === null) {
                continue;
            }

            $checked++;
            $entity = new $class([$field => $value]);
            $this->assertSame(
                $value,
                $entity->toArray()[$field] ?? null,
                \sprintf('%s: %s で渡した値が取り出せない', $shortName, $field),
            );
        }

        // スカラーのプロパティを持たないクラスもあるため、件数は要求しない
        $this->addToAssertionCount($checked === 0 ? 1 : 0);
    }

    /**
     * API のフィールド名に対応するプロパティ名を引く
     *
     * @param class-string<Entity> $class
     */
    private static function propertyFor(string $class, ReflectionClass $reflection, string $field): ?string
    {
        foreach ($reflection->getProperties() as $property) {
            if ($property->isStatic() || $property->isPrivate()) {
                continue;
            }
            if ($class::apiFieldName($property->getName()) === $field) {
                return $property->getName();
            }
        }
        return null;
    }

    /**
     * プロパティの型に応じた検証用の値。判断できない型は null を返す。
     */
    private static function sampleValue(\ReflectionProperty $property): int|string|bool|null
    {
        $type = $property->getType();
        if (! $type instanceof \ReflectionNamedType) {
            return null;
        }

        return match ($type->getName()) {
            'int' => 12345,
            'string' => 'テスト値',
            'bool' => true,
            default => null,
        };
    }

}
