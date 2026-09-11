<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Page;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Pagination;
use Shimoning\ColorMeShopApi\Exceptions\InvalidPaginationException;
use Shimoning\ColorMeShopApi\Exceptions\MissingPaginationException;
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
    public function test_buildは完全なmetaで旧生成処理と同じPageを生成する(
        array $data,
        string $key,
        string $metaKey,
    ): void {
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

    #[DataProvider('missingMetaProvider')]
    public function test_meta欠損時は要素を保持しページング取得で固有例外を投げる(array $data): void
    {
        $actual = Page::build(NestedEntity::class, $data, 'items');
        $getters = [
            'getTotal' => static fn(Page $page): int => $page->getTotal(),
            'getLimit' => static fn(Page $page): int => $page->getLimit(),
            'getOffset' => static fn(Page $page): int => $page->getOffset(),
        ];
        $iteratedLabels = [];
        foreach ($actual as $item) {
            $iteratedLabels[] = $item->getLabel();
        }

        $this->assertSame(['x'], \array_map(fn($item) => $item->getLabel(), $actual->all()));
        $this->assertCount(1, $actual);
        $this->assertSame(['x'], $iteratedLabels);
        $this->assertSame('x', $actual[0]->getLabel());

        // 生の Error を固定していた旧 characterization test を、欠損を明示する新仕様の契約へ更新する。
        foreach ($getters as $method => $getter) {
            $actualError = null;
            try {
                $getter($actual);
            } catch (MissingPaginationException $error) {
                $actualError = $error;
            }

            $this->assertInstanceOf(MissingPaginationException::class, $actualError, $method);
            $this->assertSame(
                'API レスポンスにページネーション情報「meta」がありません。ページング値を取得できません。',
                $actualError->getMessage(),
                $method,
            );
        }
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function missingMetaProvider(): array
    {
        return [
            'metaキー欠損' => [['items' => [['label' => 'x']]]],
        ];
    }

    public function test_metaがnullなら固有例外で早期に失敗する(): void
    {
        $this->expectException(InvalidPaginationException::class);
        $this->expectExceptionMessage(
            'API レスポンスのページネーション情報「meta」が不正です。array を期待しましたが null でした。',
        );

        Page::build(
            NestedEntity::class,
            ['items' => [['label' => 'x']], 'meta' => null],
            'items',
        );
    }

    #[DataProvider('incompleteMetaProvider')]
    public function test_metaが存在して不完全なら欠損値の取得時に固有例外を投げる(
        mixed $meta,
        string $getter,
        string $message,
    ): void {
        $page = Page::build(NestedEntity::class, ['items' => [['label' => 'x']], 'meta' => $meta], 'items');

        $this->assertSame(['x'], \array_map(fn($item) => $item->getLabel(), $page->all()));
        $this->expectException(MissingPaginationException::class);
        $this->expectExceptionMessage($message);

        $page->{$getter}();
    }

    /**
     * @return array<string, array{mixed, string, string}>
     */
    public static function incompleteMetaProvider(): array
    {
        return [
            '空配列' => [
                [],
                'getTotal',
                'API レスポンスにページネーション情報「meta.total」がありません。ページング値を取得できません。',
            ],
            '部分欠損' => [
                ['total' => 1, 'limit' => 10],
                'getOffset',
                'API レスポンスにページネーション情報「meta.offset」がありません。ページング値を取得できません。',
            ],
        ];
    }

    public function test_metaが完全で値がゼロなら従来どおりページング情報を返す(): void
    {
        $page = Page::build(
            NestedEntity::class,
            [
                'items' => [['label' => 'x']],
                'meta' => ['total' => 0, 'limit' => 0, 'offset' => 0],
            ],
            'items',
        );

        $this->assertSame(['x'], \array_map(fn($item) => $item->getLabel(), $page->all()));
        $this->assertSame(0, $page->getTotal());
        $this->assertSame(0, $page->getLimit());
        $this->assertSame(0, $page->getOffset());
    }
}
