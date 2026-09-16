<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product;

use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Entities\Product\Category;
use Shimoning\ColorMeShopApi\Entities\Product\MetaTag;
use Shimoning\ColorMeShopApi\Constants\CategoryDisplayState;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class CategoryTest extends TestCase
{
    private function makeCategory(array $overrides = []): Category
    {
        return new Category($overrides + [
            'id_big' => 10,
            'id_small' => 20,
            'account_id' => 'my-shop',
            'name' => 'トップス',
            'display_state' => 'showing',
            'make_date' => 1700000000,
            'update_date' => 1700000000,
            'children' => [],
        ]);
    }

    public function test_大カテゴリーIDを取得する(): void
    {
        $this->assertSame(10, $this->makeCategory()->getIdBig());
    }

    public function test_小カテゴリーIDを取得する(): void
    {
        $this->assertSame(20, $this->makeCategory()->getIdSmall());
    }

    public function test_大カテゴリーと小カテゴリーのIDは別の値を返す(): void
    {
        $category = $this->makeCategory(['id_big' => 1, 'id_small' => 0]);

        $this->assertSame(1, $category->getIdBig());
        $this->assertSame(0, $category->getIdSmall());
    }

    public function test_その他の項目も取得できる(): void
    {
        $category = $this->makeCategory();

        $this->assertSame('my-shop', $category->getAccountId());
        $this->assertSame('トップス', $category->getName());
        $this->assertSame(CategoryDisplayState::SHOWING, $category->getDisplayState());
    }

    public function test_toArrayのキー順はmeta_tagがchildrenの後になる(): void
    {
        $this->assertSame([
            'id_big',
            'id_small',
            'account_id',
            'name',
            'image_url',
            'expl',
            'sort',
            'display_state',
            'make_date',
            'update_date',
            'children',
            'meta_tag',
        ], \array_keys($this->makeCategory()->toArray()));
    }

    public function test_実APIの大カテゴリーからmeta_tagを取得できる(): void
    {
        $data = self::actualCategories()[1];
        $category = new Category($data);

        $this->assertInstanceOf(MetaTag::class, $category->getMetaTag());
        $this->assertSame($data['meta_tag'], $category->getMetaTag()?->getRaw());
    }

    public function test_実APIの小カテゴリーからmeta_tagを取得できる(): void
    {
        $data = self::actualCategories()[0];
        $category = new Category($data);
        $child = $category->getChildren()[0];

        $this->assertInstanceOf(MetaTag::class, $child->getMetaTag());
        $this->assertSame($data['children'][0]['meta_tag'], $child->getMetaTag()?->getRaw());
    }

    public function test_実APIでmeta_tagがない大カテゴリーはnullを返す(): void
    {
        $category = new Category(self::actualCategories()[0]);

        $this->assertNull($category->getMetaTag());
        $this->assertArrayNotHasKey('meta_tag', $category->getRaw());
        $this->assertNull($category->toArray()['meta_tag']);
        $this->assertArrayNotHasKey('meta_tag', $category->toArrayRecursive());
    }

    #[DataProvider('invalidMetaTagProvider')]
    public function test_meta_tagがobjectでなければ固有例外になる(mixed $value): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(
            Category::class . " の API フィールド『meta_tag』が不正です。" . MetaTag::class
            . ' を期待しましたが',
        );

        $this->makeCategory(['meta_tag' => $value]);
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidMetaTagProvider(): array
    {
        return [
            'false' => [false],
            '整数0' => [0],
            '正の整数' => [123],
            '空文字' => [''],
            '文字列0' => ['0'],
        ];
    }

    public function test_meta_tagが空objectなら空のMetaTagを構築する(): void
    {
        $category = $this->makeCategory(['meta_tag' => []]);
        $metaTag = $category->getMetaTag();

        $this->assertInstanceOf(MetaTag::class, $metaTag);
        $this->assertNull($metaTag->getTitle());
        $this->assertNull($metaTag->getKeywords());
        $this->assertNull($metaTag->getDescription());
        $this->assertSame([], $metaTag->getRaw());
        $this->assertSame([
            'title' => null,
            'keywords' => null,
            'description' => null,
        ], $metaTag->toArray());
        $this->assertSame([], $metaTag->toArrayRecursive());
    }

    public function test_meta_tagを実APIと同じフィールド名で再帰的に配列化できる(): void
    {
        $data = self::actualCategories()[1];
        $category = new Category($data);

        $this->assertSame($data['meta_tag'], $category->toArrayRecursive()['meta_tag']);
        $this->assertSame(
            $data['children'][0]['meta_tag'],
            $category->toArrayRecursive()['children'][0]['meta_tag'],
        );
    }

    public function test_meta_tagの一部キー欠損はMetaTagの欠損契約に従う(): void
    {
        $data = self::actualCategories()[1];
        unset($data['meta_tag']['description']);
        $category = new Category($data);

        // 公式スキーマ上 nullable のため、従来の欠損例外ではなく Entity 共通契約の null を期待する。
        $this->assertNull($category->getMetaTag()?->getDescription());
    }

    public function test_meta_tag内のnullableな項目がnullでもカテゴリーを構築できる(): void
    {
        $data = self::actualCategories()[1];
        $data['meta_tag'] = [
            'title' => null,
            'keywords' => null,
            'description' => null,
        ];

        $category = new Category($data);

        $this->assertNull($category->getMetaTag()?->getTitle());
        $this->assertNull($category->getMetaTag()?->getKeywords());
        $this->assertNull($category->getMetaTag()?->getDescription());
        $this->assertSame($data['meta_tag'], $category->getMetaTag()?->getRaw());
        $this->assertSame($data['meta_tag'], $category->getMetaTag()?->toArray());
        $this->assertSame([], $category->getMetaTag()?->toArrayRecursive());
        $this->assertSame([], $category->toArrayRecursive()['meta_tag']);
    }

    /**
     * 2026-09-12 に保存した実 API レスポンスを、そのまま fixture 化して利用する。
     *
     * @return array<int, array<string, mixed>>
     */
    private static function actualCategories(): array
    {
        return self::fixtureArray('categories_with_meta_tags.json')['categories'];
    }
}
