<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product;

use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Product\AdvertisingSearchParameters;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class AdvertisingSearchParametersTest extends TestCase
{
    public function test_OpenAPIの全検索条件をクエリへ変換する(): void
    {
        $parameters = new AdvertisingSearchParameters([
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
        $this->assertSame([], (new AdvertisingSearchParameters([]))->toArrayRecursive());
        $this->assertSame([], (new AdvertisingSearchParameters(['product_ids' => []]))->toArrayRecursive());
    }

    public function test_商品ID配列の不正型を拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        new AdvertisingSearchParameters(['product_ids' => [101, 'bad']]);
    }
}
