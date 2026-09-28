<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities\Stock;

use DateTimeImmutable;
use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;
use Shimoning\ColorMeShopApi\Entities\Product\CategoryIds;
use Shimoning\ColorMeShopApi\Entities\Product\Image;
use Shimoning\ColorMeShopApi\Entities\Stock\Stock;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class StockTest extends TestCase
{
    public function test_在庫行の全フィールドと再利用型を取得できる(): void
    {
        $stock = new Stock(self::fixtureArray('stocks_page.json')['stocks'][0]);

        $this->assertCount(36, $stock->toArray());
        $this->assertSame('my-shop', $stock->getAccountId());
        $this->assertSame(101, $stock->getProductId());
        $this->assertInstanceOf(CategoryIds::class, $stock->getCategory());
        $this->assertSame(501, $stock->getCategory()->getIdBig());
        $this->assertContainsOnlyInstancesOf(Image::class, $stock->getImages());
        $this->assertSame(ProductDisplayState::SHOWING, $stock->getDisplayState());
        $this->assertInstanceOf(DateTimeImmutable::class, $stock->getMakeDate());
        $this->assertSame(1700000000, $stock->getMakeDate()->getTimestamp());
        $this->assertSame(1700000200, $stock->getUpdateDate()->getTimestamp());
        $this->assertSame(1700000100, $stock->getSaleStartDate()?->getTimestamp());
        $this->assertSame(1800000100, $stock->getSaleEndDate()?->getTimestamp());
    }

    public function test_nullableフィールドのnullと欠損をnullとして扱う(): void
    {
        $stock = new Stock([
            'account_id' => 'my-shop',
            'product_id' => 102,
            'name' => '架空の商品',
            'category' => null,
            'display_state' => 'hidden',
            'soldout_display' => false,
            'make_date' => 1700000300,
            'update_date' => 1700000400,
            'images' => [],
        ]);

        $this->assertNull($stock->getCategory());
        $this->assertNull($stock->getStocks());
        $this->assertNull($stock->getSaleStartDate());
        $this->assertNull($stock->getSaleEndDate());
    }

    public function test_非nullableフィールドの欠損は固有例外になる(): void
    {
        $stock = new Stock([]);

        $this->expectException(MissingFieldException::class);
        $this->expectExceptionMessage('product_id');
        $stock->getProductId();
    }
}
