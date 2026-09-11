<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Page;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Pagination;
use Shimoning\ColorMeShopApi\Tests\Doubles\NestedEntity;

class PageTest extends TestCase
{
    private function makePage(array $items = [], array $meta = []): Page
    {
        return new Page(
            Collection::cast(NestedEntity::class, $items),
            new Pagination($meta + ['total' => 0, 'limit' => 0, 'offset' => 0]),
        );
    }

    public function test_Collectionを継承している(): void
    {
        $this->assertInstanceOf(Collection::class, $this->makePage());
    }

    public function test_ページング情報を委譲して返す(): void
    {
        $page = $this->makePage([], ['total' => 250, 'limit' => 50, 'offset' => 100]);

        $this->assertSame(250, $page->getTotal());
        $this->assertSame(50, $page->getLimit());
        $this->assertSame(100, $page->getOffset());
    }

    public function test_要素を保持し走査できる(): void
    {
        $page = $this->makePage([['label' => 'x'], ['label' => 'y']]);

        $this->assertSame(2, $page->count());
        $this->assertSame(['x', 'y'], \array_map(fn($i) => $i->getLabel(), $page->all()));
    }

    public function test_count関数で要素数を取得できる(): void
    {
        $page = $this->makePage([['label' => 'x'], ['label' => 'y']]);

        $this->assertInstanceOf(\Countable::class, $page);
        $this->assertCount(2, $page);
    }

    public function test_Collectionを渡しても中身を引き継ぐ(): void
    {
        $page = new Page(new Collection(['a', 'b']), new Pagination(['total' => 2, 'limit' => 10, 'offset' => 0]));

        $this->assertSame(['a', 'b'], $page->all());
        $this->assertSame(2, $page->getTotal());
    }

    public function test_APIレスポンスからPageを生成する(): void
    {
        $page = Page::build(
            NestedEntity::class,
            [
                'items' => [['label' => 'x'], ['label' => 'y']],
                'meta' => ['total' => 2, 'limit' => 10, 'offset' => 0],
            ],
            'items',
        );

        $this->assertSame(Page::class, $page::class);
        $this->assertSame(['x', 'y'], \array_map(fn($item) => $item->getLabel(), $page->all()));
        $this->assertSame(2, $page->getTotal());
        $this->assertSame(10, $page->getLimit());
        $this->assertSame(0, $page->getOffset());
    }

    public function test_要素のキーが存在しない場合は空のPageを生成する(): void
    {
        $page = Page::build(
            NestedEntity::class,
            ['meta' => ['total' => 0, 'limit' => 10, 'offset' => 0]],
            'items',
        );

        $this->assertSame([], $page->all());
    }

    public function test_レスポンスがnullの場合は空のPageを生成する(): void
    {
        $page = Page::build(NestedEntity::class, null, 'items');

        $this->assertSame(Page::class, $page::class);
        $this->assertSame([], $page->all());
    }

    public function test_metaが存在しない場合もPageを生成する(): void
    {
        $page = Page::build(
            NestedEntity::class,
            ['items' => [['label' => 'x']]],
            'items',
        );

        $this->assertSame(['x'], \array_map(fn($item) => $item->getLabel(), $page->all()));
    }

    #[DataProvider('buildCompatibilityProvider')]
    public function test_buildは旧生成処理と同じPageを生成する(array $data, string $key, string $metaKey): void
    {
        $expected = new Page(
            Collection::cast(NestedEntity::class, $data[$key] ?? []),
            new Pagination($data[$metaKey] ?? []),
        );

        $actual = Page::build(NestedEntity::class, $data, $key, $metaKey);

        $this->assertSame(\serialize($expected), \serialize($actual));
    }

    /**
     * @return array<string, array{array<string, mixed>, string, string}>
     */
    public static function buildCompatibilityProvider(): array
    {
        return [
            'metaが存在しない' => [
                ['items' => [['label' => 'x']]],
                'items',
                'meta',
            ],
            '要素キーの値が空配列' => [
                [
                    'items' => [],
                    'meta' => ['total' => 0, 'limit' => 10, 'offset' => 0],
                ],
                'items',
                'meta',
            ],
            'metaキーをpaginationへ差し替える' => [
                [
                    'items' => [['label' => 'x']],
                    'pagination' => ['total' => 1, 'limit' => 20, 'offset' => 5],
                ],
                'items',
                'pagination',
            ],
        ];
    }

    public function test_meta欠損時のページング取得は旧生成処理と同じErrorになる(): void
    {
        $data = ['items' => [['label' => 'x']]];
        $expected = new Page(
            Collection::cast(NestedEntity::class, $data['items']),
            new Pagination([]),
        );
        $actual = Page::build(NestedEntity::class, $data, 'items');
        $getters = [
            'getTotal' => static fn(Page $page): int => $page->getTotal(),
            'getLimit' => static fn(Page $page): int => $page->getLimit(),
            'getOffset' => static fn(Page $page): int => $page->getOffset(),
        ];

        // Pagination に初期値を追加する場合は、この現状互換性テストも更新する。
        foreach ($getters as $method => $getter) {
            $expectedError = null;
            try {
                $getter($expected);
            } catch (\Error $error) {
                $expectedError = $error;
            }

            $actualError = null;
            try {
                $getter($actual);
            } catch (\Error $error) {
                $actualError = $error;
            }

            $this->assertInstanceOf(\Error::class, $expectedError, $method);
            $this->assertInstanceOf(\Error::class, $actualError, $method);
            $this->assertSame($expectedError::class, $actualError::class, $method);
            $this->assertSame($expectedError->getMessage(), $actualError->getMessage(), $method);
        }
    }
}
