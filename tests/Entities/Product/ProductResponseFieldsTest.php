<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product;

use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Entities\Product\Category;
use Shimoning\ColorMeShopApi\Entities\Product\BigCategory;
use Shimoning\ColorMeShopApi\Entities\Product\SmallCategory;
use Shimoning\ColorMeShopApi\Entities\Product\Group;
use Shimoning\ColorMeShopApi\Entities\Product\MetaTag;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class ProductResponseFieldsTest extends TestCase
{
    /** metaTag 追加前の空 Group を serialize() したペイロード。 */
    private const LEGACY_EMPTY_GROUP_PAYLOAD_BASE64 = 'Tzo0NzoiU2hpbW9uaW5nXENvbG9yTWVTaG9wQXBpXEVudGl0aWVzXFByb2R1Y3RcR3JvdXAiOjU6e3M6NDY6IgBTaGltb25pbmdcQ29sb3JNZVNob3BBcGlcRW50aXRpZXNcRW50aXR5AF9yYXciO2E6MDp7fXM6MTE6IgAqAGltYWdlVXJsIjtOO3M6NzoiACoAZXhwbCI7TjtzOjc6IgAqAHNvcnQiO047czoxNjoiACoAcGFyZW50R3JvdXBJZCI7Tjt9';

    /** metaTag 追加前の空 Category を serialize() したペイロード。 */
    private const LEGACY_EMPTY_CATEGORY_PAYLOAD_BASE64 = 'Tzo1MDoiU2hpbW9uaW5nXENvbG9yTWVTaG9wQXBpXEVudGl0aWVzXFByb2R1Y3RcQ2F0ZWdvcnkiOjQ6e3M6NDY6IgBTaGltb25pbmdcQ29sb3JNZVNob3BBcGlcRW50aXRpZXNcRW50aXR5AF9yYXciO2E6MDp7fXM6MTE6IgAqAGltYWdlVXJsIjtOO3M6NzoiACoAZXhwbCI7TjtzOjc6IgAqAHNvcnQiO047fQ==';

    public function test_カテゴリーのmeta_tagを共有Entityで取得する(): void
    {
        $category = Category::fromArray(self::fixtureData('category_with_values'));

        $metaTag = $category->getMetaTag();
        $this->assertInstanceOf(MetaTag::class, $metaTag);
        $this->assertSame('高品質なTシャツコレクション | ECショップ', $metaTag->getTitle());
        $this->assertSame('Tシャツ,ファッション,コットン', $metaTag->getKeywords());
        $this->assertSame('厳選素材を使用したTシャツを取り揃えています。', $metaTag->getDescription());
    }

    public function test_子カテゴリーのmeta_tagも再帰的に共有Entityへ変換する(): void
    {
        $category = Category::fromArray(self::fixtureData('category_with_values'));

        $children = $category->getChildren();
        $this->assertCount(1, $children);
        $this->assertContainsOnlyInstancesOf(SmallCategory::class, $children);

        $metaTag = $children[0]->getMetaTag();
        $this->assertInstanceOf(MetaTag::class, $metaTag);
        $this->assertSame('カジュアルTシャツ | ECショップ', $metaTag->getTitle());
        $this->assertSame('カジュアル,Tシャツ,日常着', $metaTag->getKeywords());
        $this->assertSame('日常使いに最適なカジュアルTシャツです。', $metaTag->getDescription());
    }

    public function test_meta_tag内部のnullableフィールドの明示的なnullを保持する(): void
    {
        $category = Category::fromArray(self::fixtureData('category_with_null_meta_tag_fields'));

        $metaTag = $category->getMetaTag();
        $this->assertNull($metaTag->getTitle());
        $this->assertNull($metaTag->getKeywords());
        $this->assertNull($metaTag->getDescription());
    }

    public function test_nullableなカテゴリーのmeta_tagが欠損していればnullになる(): void
    {
        $category = Category::fromArray(self::fixtureData('category_with_missing_meta_tag'));

        $this->assertNull($category->getMetaTag());
        $this->assertNull($category->toArray()['meta_tag']);
        $this->assertArrayHasKey('meta_tag', $category->toArrayRecursive(false));
        $this->assertNull($category->toArrayRecursive(false)['meta_tag']);
    }

    public function test_nullableなカテゴリーのmeta_tagの明示的なnullを保持する(): void
    {
        $category = Category::fromArray(self::fixtureData('category_with_null_meta_tag'));

        $this->assertNull($category->getMetaTag());
        $array = $category->toArray();
        $recursive = $category->toArrayRecursive();
        $recursiveWithNull = $category->toArrayRecursive(false);
        foreach (['image_url', 'expl', 'sort', 'meta_tag'] as $field) {
            $this->assertNull($array[$field]);
            $this->assertArrayNotHasKey($field, $recursive);
            $this->assertArrayHasKey($field, $recursiveWithNull);
            $this->assertNull($recursiveWithNull[$field]);
        }
        $this->assertSame(['id_small' => 0, 'meta_tag' => null], $category->getRaw());
    }

    public function test_カテゴリーのmeta_tagが不正型なら固有例外になる(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(
            BigCategory::class . ' の API フィールド『meta_tag』が不正です。',
        );

        Category::fromArray(self::fixtureData('category_with_invalid_meta_tag'));
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

    public function test_グループのmeta_tagが欠損していればnullになる(): void
    {
        $group = new Group(self::fixtureData('group_with_missing_meta_tag'));

        $this->assertNull($group->getMetaTag());
        $this->assertNull($group->toArray()['meta_tag']);
    }

    public function test_グループのmeta_tagの明示的なnullを保持する(): void
    {
        $group = new Group(self::fixtureData('group_with_null_meta_tag'));

        $this->assertNull($group->getMetaTag());
        $this->assertNull($group->toArray()['meta_tag']);
        $this->assertSame(['meta_tag' => null], $group->getRaw());
    }

    public function test_グループのmeta_tagが空オブジェクトなら空のMetaTagを保持する(): void
    {
        $group = new Group(['meta_tag' => []]);

        $metaTag = $group->getMetaTag();
        $this->assertInstanceOf(MetaTag::class, $metaTag);
        $this->assertNull($metaTag->getTitle());
        $this->assertNull($metaTag->getKeywords());
        $this->assertNull($metaTag->getDescription());
        $this->assertSame([], $metaTag->getRaw());
        $this->assertSame($metaTag, $group->toArray()['meta_tag']);
    }

    public function test_旧形式のGroupをunserializeするとmeta_tagはnullになる(): void
    {
        $group = \unserialize(self::legacyPayload(self::LEGACY_EMPTY_GROUP_PAYLOAD_BASE64));

        $this->assertInstanceOf(Group::class, $group);
        $this->assertNull($group->getMetaTag());
    }

    public function test_旧形式のCategoryペイロードをBigCategoryとして復元するとmeta_tagはnullになる(): void
    {
        $payload = self::legacyPayload(self::LEGACY_EMPTY_CATEGORY_PAYLOAD_BASE64);
        $oldHeader = 'O:' . \strlen(Category::class) . ':"' . Category::class . '"';
        $newHeader = 'O:' . \strlen(BigCategory::class) . ':"' . BigCategory::class . '"';
        $category = \unserialize(\str_replace($oldHeader, $newHeader, $payload));
        $this->assertInstanceOf(BigCategory::class, $category);

        $this->assertNull($category->getMetaTag());
    }

    #[DataProvider('invalidGroupMetaTagProvider')]
    public function test_グループのmeta_tagが不正型なら固有例外になる(mixed $value): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(
            Group::class . ' の API フィールド『meta_tag』が不正です。',
        );

        new Group(['meta_tag' => $value]);
    }

    /** @return array<string, array{mixed}> */
    public static function invalidGroupMetaTagProvider(): array
    {
        return [
            '文字列' => [self::fixtureData('group_with_invalid_meta_tag')['meta_tag']],
            '数値' => [0],
        ];
    }

    #[DataProvider('invalidMetaTagFieldProvider')]
    public function test_meta_tag内部フィールドの不正型は実際のフィールドを示す固有例外になる(
        string $field,
        mixed $value,
    ): void {
        try {
            Category::fromArray(['id_small' => 0, 'meta_tag' => [$field => $value]]);
            $this->fail('InvalidFieldException が送出されませんでした。');
        } catch (InvalidFieldException $exception) {
            $this->assertStringContainsString(
                BigCategory::class . ' の API フィールド『meta_tag』が不正です。',
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

    private static function legacyPayload(string $encoded): string
    {
        $payload = \base64_decode($encoded, true);
        if ($payload === false) {
            self::fail('旧形式の serialize ペイロードをデコードできません。');
        }

        return $payload;
    }
}
