<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities;

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
}
