<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product;

use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Shimoning\ColorMeShopApi\Entities\Product\BigCategory;
use Shimoning\ColorMeShopApi\Entities\Product\Category;
use Shimoning\ColorMeShopApi\Entities\Product\MetaTag;
use Shimoning\ColorMeShopApi\Entities\Product\SmallCategory;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class CategoryStructureTest extends TestCase
{
    public function test_実測構造を親子の具象型に変換する(): void
    {
        $data = self::fixtureArray('category_structure.json');
        $category = Category::fromArray($data);

        $this->assertTrue((new ReflectionClass(Category::class))->isAbstract());
        $this->assertInstanceOf(BigCategory::class, $category);
        $this->assertSame($data['id_big'], $category->getIdBig());
        $this->assertSame(0, $category->getIdSmall());
        $this->assertSame($data['meta_tag'], $category->getMetaTag()?->getRaw());

        $children = $category->getChildren();
        $this->assertCount(1, $children);
        $this->assertContainsOnlyInstancesOf(SmallCategory::class, $children);
        $this->assertSame($data['children'][0]['meta_tag'], $children[0]->getMetaTag()?->getRaw());
        $this->assertFalse(\method_exists($children[0], 'getChildren'));
        $this->assertArrayNotHasKey('children', $children[0]->toArray());
        $this->assertArrayNotHasKey('children', $children[0]->toArrayRecursive());

        $this->assertSame($children, $category->toArray()['children']);
        $expected = $data;
        unset($expected['children'][0]['image_url'], $expected['children'][0]['sort']);
        $this->assertEquals($expected, $category->toArrayRecursive(false));
        $this->assertSame($data['children'][0], $children[0]->toArrayRecursive(false));
    }

    public function test_子を持たない親も空配列を保持する(): void
    {
        $data = self::fixtureArray('category_structure.json');
        $data['children'] = [];

        $category = Category::fromArray($data);

        $this->assertInstanceOf(BigCategory::class, $category);
        $this->assertSame([], $category->getChildren());
        $this->assertSame([], $category->toArrayRecursive()['children']);
    }

    public function test_ゼロ以外の整数は小カテゴリーになる(): void
    {
        $category = Category::fromArray(['id_small' => -1]);

        $this->assertInstanceOf(SmallCategory::class, $category);
        $this->assertSame(-1, $category->getIdSmall());
    }

    public function test_親のchildrenが欠損すればgetterで固有例外になる(): void
    {
        $data = self::fixtureArray('category_structure.json');
        unset($data['children']);
        $category = Category::fromArray($data);

        $this->expectException(MissingFieldException::class);
        $this->expectExceptionMessage('children');
        $category->getChildren();
    }

    #[DataProvider('invalidIdSmallProvider')]
    public function test_id_smallが不正なら分類せず固有例外になる(array $data): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('id_small');
        Category::fromArray($data);
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function invalidIdSmallProvider(): array
    {
        return [
            '欠損' => [[]],
            '文字列0' => [['id_small' => '0']],
            'null' => [['id_small' => null]],
            '配列' => [['id_small' => []]],
        ];
    }

    #[DataProvider('invalidChildProvider')]
    public function test_不正な子要素は配列位置を示す(mixed $child): void
    {
        $data = self::fixtureArray('category_structure.json');
        $data['children'][] = $child;

        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('children[1]');
        Category::fromArray($data);
    }

    /** @return array<string, array{mixed}> */
    public static function invalidChildProvider(): array
    {
        return [
            'id_small欠損' => [[]],
            '親型' => [['id_small' => 0]],
            '文字列ID' => [['id_small' => '1']],
            '文字列要素' => ['invalid'],
        ];
    }

    public function test_子への不正なchildren入力も公開フィールドにはならない(): void
    {
        $data = self::fixtureArray('category_structure.json')['children'][0];
        $data['children'] = [['id_small' => 1]];

        $category = Category::fromArray($data);

        $this->assertInstanceOf(SmallCategory::class, $category);
        $this->assertArrayNotHasKey('children', $category->toArray());
        $this->assertArrayNotHasKey('children', $category->toArrayRecursive());
    }

    public function test_親子のmeta_tag欠損とnullは共にnullとして扱う(): void
    {
        $data = self::fixtureArray('category_structure.json');
        unset($data['meta_tag']);
        $data['children'][0]['meta_tag'] = null;

        $category = Category::fromArray($data);
        $this->assertInstanceOf(BigCategory::class, $category);
        $this->assertNull($category->getMetaTag());
        $this->assertNull($category->getChildren()[0]->getMetaTag());
        $this->assertNull($category->toArray()['meta_tag']);
        $this->assertArrayNotHasKey('meta_tag', $category->toArrayRecursive());
        $this->assertArrayNotHasKey('meta_tag', $category->getChildren()[0]->toArrayRecursive());
    }

    public function test_親子のmeta_tag空配列はMetaTagを保持する(): void
    {
        $data = self::fixtureArray('category_structure.json');
        $data['meta_tag'] = [];
        $data['children'][0]['meta_tag'] = [];

        $category = Category::fromArray($data);
        $this->assertInstanceOf(BigCategory::class, $category);
        $this->assertInstanceOf(MetaTag::class, $category->getMetaTag());
        $this->assertInstanceOf(MetaTag::class, $category->getChildren()[0]->getMetaTag());
        $this->assertSame([], $category->toArrayRecursive()['meta_tag']);
        $this->assertSame([], $category->toArrayRecursive()['children'][0]['meta_tag']);
    }
}
