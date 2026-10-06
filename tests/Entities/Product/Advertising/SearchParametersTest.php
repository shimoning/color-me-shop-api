<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product\Advertising;

use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Product\Advertising\SearchParameters;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class SearchParametersTest extends TestCase
{
    public function test_OpenAPIの全検索条件をクエリへ変換する(): void
    {
        $parameters = new SearchParameters([
            'product_ids' => [101, 102], 'display_state' => 'showing', 'limit' => 25, 'offset' => 50,
        ]);

        $this->assertInstanceOf(RequestEntity::class, $parameters);
        $this->assertSame(ProductDisplayState::SHOWING, $parameters->getDisplayState());
        $this->assertSame([
            'product_ids' => '101,102', 'display_state' => 'showing', 'limit' => 25, 'offset' => 50,
        ], $parameters->toArrayRecursive());
    }

    public function test_未指定と空のID配列は送らない(): void
    {
        $this->assertSame([], (new SearchParameters([]))->toArrayRecursive());
        $this->assertSame([], (new SearchParameters(['product_ids' => []]))->toArrayRecursive());
    }

    public function test_商品ID配列の不正型を拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        new SearchParameters(['product_ids' => [101, 'bad']]);
    }
}
