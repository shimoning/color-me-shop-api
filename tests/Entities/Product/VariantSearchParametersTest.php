<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product;

use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Product\Variant\SearchParameters;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class VariantSearchParametersTest extends TestCase
{
    public function test_全検索条件をクエリ形式に変換する(): void
    {
        $parameters = new SearchParameters([
            'model_number' => 'TEST', 'fields' => 'id,model_number', 'limit' => 100, 'offset' => 10,
        ]);

        $this->assertInstanceOf(RequestEntity::class, $parameters);
        $this->assertSame([
            'model_number' => 'TEST', 'fields' => 'id,model_number', 'limit' => 100, 'offset' => 10,
        ], $parameters->toArrayRecursive());
    }

    public function test_未指定の条件はクエリに出さない(): void
    {
        $this->assertSame([], (new SearchParameters([]))->toArrayRecursive());
    }

    public function test_不正な条件の型を拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        new SearchParameters(['limit' => '100']);
    }
}
