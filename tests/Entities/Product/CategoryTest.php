<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Product\Category;
use Shimoning\ColorMeShopApi\Constants\CategoryDisplayState;

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
}
