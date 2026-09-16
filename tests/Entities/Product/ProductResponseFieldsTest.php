<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product;

use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Entities\Product\Category;
use Shimoning\ColorMeShopApi\Entities\Product\Group;
use Shimoning\ColorMeShopApi\Entities\Product\MetaTag;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class ProductResponseFieldsTest extends TestCase
{
    public function test_カテゴリーのmeta_tagを共有Entityで取得する(): void
    {
        $category = new Category(self::fixtureData('category_with_values'));

        $metaTag = $category->getMetaTag();
        $this->assertInstanceOf(MetaTag::class, $metaTag);
        $this->assertSame('高品質なTシャツコレクション | ECショップ', $metaTag->getTitle());
        $this->assertSame('Tシャツ,ファッション,コットン', $metaTag->getKeywords());
        $this->assertSame('厳選素材を使用したTシャツを取り揃えています。', $metaTag->getDescription());
    }

    public function test_子カテゴリーのmeta_tagも再帰的に共有Entityへ変換する(): void
    {
        $category = new Category(self::fixtureData('category_with_values'));

        $children = $category->getChildren();
        $this->assertCount(1, $children);
        $this->assertContainsOnlyInstancesOf(Category::class, $children);

        $metaTag = $children[0]->getMetaTag();
        $this->assertInstanceOf(MetaTag::class, $metaTag);
        $this->assertSame('カジュアルTシャツ | ECショップ', $metaTag->getTitle());
        $this->assertSame('カジュアル,Tシャツ,日常着', $metaTag->getKeywords());
        $this->assertSame('日常使いに最適なカジュアルTシャツです。', $metaTag->getDescription());
    }

    public function test_meta_tag内部のnullableフィールドの明示的なnullを保持する(): void
    {
        $category = new Category(self::fixtureData('category_with_null_meta_tag_fields'));

        $metaTag = $category->getMetaTag();
        $this->assertNull($metaTag->getTitle());
        $this->assertNull($metaTag->getKeywords());
        $this->assertNull($metaTag->getDescription());
    }

    public function test_カテゴリーのmeta_tagが欠損していれば固有例外になる(): void
    {
        $category = new Category(self::fixtureData('category_with_missing_meta_tag'));

        $this->expectException(MissingFieldException::class);
        $this->expectExceptionMessage(
            Category::class . ' の API フィールド『meta_tag』が欠損しています。',
        );

        $category->getMetaTag();
    }

    public function test_非nullableなカテゴリーのmeta_tagがnullなら固有例外になる(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(
            Category::class . ' の API フィールド『meta_tag』が不正です。',
        );

        new Category(self::fixtureData('category_with_null_meta_tag'));
    }

    public function test_カテゴリーのmeta_tagが不正型なら固有例外になる(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(
            Category::class . ' の API フィールド『meta_tag』が不正です。',
        );

        new Category(self::fixtureData('category_with_invalid_meta_tag'));
    }

    public function test_グループのmeta_tagをカテゴリーと同じ共有Entityで取得する(): void
    {
        $group = new Group(self::fixtureData('group_with_values'));

        $metaTag = $group->getMetaTag();
        $this->assertInstanceOf(MetaTag::class, $metaTag);
        $this->assertSame('夏物特集', $metaTag->getTitle());
        $this->assertSame('夏物,涼しい,衣類', $metaTag->getKeywords());
        $this->assertSame('暑い夏を涼しく過ごすための商品を集めました。', $metaTag->getDescription());
    }

    public function test_nullableなグループのmeta_tagが欠損していればnullになる(): void
    {
        $group = new Group(self::fixtureData('group_with_missing_meta_tag'));

        $this->assertNull($group->getMetaTag());
    }

    public function test_nullableなグループのmeta_tagの明示的なnullを保持する(): void
    {
        $group = new Group(self::fixtureData('group_with_null_meta_tag'));

        $this->assertNull($group->getMetaTag());
    }

    public function test_グループのmeta_tagが不正型なら固有例外になる(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(
            Group::class . ' の API フィールド『meta_tag』が不正です。',
        );

        new Group(self::fixtureData('group_with_invalid_meta_tag'));
    }

    #[DataProvider('invalidMetaTagFieldProvider')]
    public function test_meta_tag内部フィールドの不正型は実際のフィールドを示す固有例外になる(
        string $field,
        mixed $value,
    ): void {
        try {
            new Category(['meta_tag' => [$field => $value]]);
            $this->fail('InvalidFieldException が送出されませんでした。');
        } catch (InvalidFieldException $exception) {
            $this->assertStringContainsString(
                Category::class . ' の API フィールド『meta_tag』が不正です。',
                $exception->getMessage(),
            );

            $cause = $exception->getPrevious();
            $this->assertInstanceOf(InvalidFieldException::class, $cause);
            $this->assertStringContainsString(
                MetaTag::class . ' の API フィールド『' . $field . '』が不正です。',
                $cause->getMessage(),
            );
        }
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidMetaTagFieldProvider(): array
    {
        $fields = self::fixtureData('invalid_meta_tag_fields');
        $cases = [];
        foreach ($fields as $field => $value) {
            $cases[$field] = [$field, $value];
        }

        return $cases;
    }

    /** @return array<string, mixed> */
    private static function fixtureData(string $key): array
    {
        $fixture = self::fixtureArray('product_response_fields.json');
        $data = $fixture[$key] ?? null;
        if (! \is_array($data)) {
            throw new \RuntimeException('Product fixture に配列データがありません: ' . $key);
        }

        return $data;
    }
}
