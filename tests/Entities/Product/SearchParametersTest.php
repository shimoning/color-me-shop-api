<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product;

use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Product\SearchParameters;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class SearchParametersTest extends TestCase
{
    public function test_全25条件をOpenAPIのクエリ形式に変換する(): void
    {
        $input = [
            'ids' => [101, 102], 'category_id_big' => 1, 'category_id_small' => 2,
            'group_ids' => [301, 302], 'model_number' => 'TEST', 'name' => '商品',
            'display_state' => 'showing', 'stocks' => 3, 'stock_managed' => true,
            'recent_zero_stocks' => false, 'make_date_min' => '2024-01-01',
            'make_date_max' => '2024-12-31', 'update_date_min' => '2024-01-01',
            'update_date_max' => '2024-12-31', 'sales_price_min' => 1,
            'sales_price_max' => 2, 'price_min' => 3, 'price_max' => 4,
            'members_price_min' => 5, 'members_price_max' => 6, 'jan_code' => '123',
            'sort' => '-make_date', 'fields' => 'id,name', 'limit' => 50, 'offset' => 10,
        ];
        $parameters = new SearchParameters($input);

        $this->assertInstanceOf(RequestEntity::class, $parameters);
        $actual = $parameters->toArrayRecursive();
        $this->assertCount(25, $actual);
        $this->assertSame('101,102', $actual['ids']);
        $this->assertSame('301,302', $actual['group_ids']);
        $this->assertSame(ProductDisplayState::SHOWING, $parameters->getDisplayState());
        $this->assertSame('2024-01-01', $actual['make_date_min']);
        $this->assertSame('id,name', $actual['fields']);
        $this->assertSame(50, $actual['limit']);
    }

    public function test_未指定はクエリに出さない(): void
    {
        $this->assertSame([], (new SearchParameters([]))->toArrayRecursive());
    }

    public function test_配列の不正型を拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        new SearchParameters(['ids' => [101, 'bad']]);
    }
}
