<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Pagination;

class PaginationTest extends TestCase
{
    public function test_APIのmetaからページング情報を組み立てる(): void
    {
        $pagination = new Pagination(['total' => 123, 'limit' => 50, 'offset' => 100]);

        $this->assertSame(123, $pagination->getTotal());
        $this->assertSame(50, $pagination->getLimit());
        $this->assertSame(100, $pagination->getOffset());
    }

    public function test_ゼロも保持する(): void
    {
        $pagination = new Pagination(['total' => 0, 'limit' => 0, 'offset' => 0]);

        $this->assertSame(0, $pagination->getTotal());
        $this->assertSame(0, $pagination->getLimit());
        $this->assertSame(0, $pagination->getOffset());
    }

    public function test_未定義のキーは無視される(): void
    {
        $pagination = new Pagination(['total' => 1, 'limit' => 1, 'offset' => 0, 'unknown' => 'x']);

        $this->assertArrayNotHasKey('unknown', $pagination->toArray());
    }
}
