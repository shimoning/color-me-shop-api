<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities\Stock;

use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Stock\SearchParameters;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class SearchParametersTest extends TestCase
{
    public function test_在庫APIの11条件だけをクエリ形式へ変換する(): void
    {
        $parameters = new SearchParameters([
            'ids' => [101, 102],
            'category_id_big' => 1,
            'category_id_small' => 2,
            'model_number' => 'MUG',
            'name' => '架空',
            'display_state' => 'showing',
            'stocks' => 5,
            'recent_zero_stocks' => true,
            'fields' => 'product_id,name,stocks',
            'limit' => 50,
            'offset' => 10,
        ]);

        $this->assertInstanceOf(RequestEntity::class, $parameters);
        $this->assertSame([
            'ids' => '101,102',
            'category_id_big' => 1,
            'category_id_small' => 2,
            'model_number' => 'MUG',
            'name' => '架空',
            'display_state' => 'showing',
            'stocks' => 5,
            'recent_zero_stocks' => true,
            'fields' => 'product_id,name,stocks',
            'limit' => 50,
            'offset' => 10,
        ], $parameters->toArrayRecursive());
        $this->assertSame(ProductDisplayState::SHOWING, $parameters->getDisplayState());
    }

    public function test_未指定と空のidsは送らない(): void
    {
        $this->assertSame([], (new SearchParameters([]))->toArrayRecursive());
        $this->assertSame([], (new SearchParameters(['ids' => []]))->toArrayRecursive());
    }

    public function test_falseの真偽値とAPIが受理する上限超過limitも明示値として送る(): void
    {
        $parameters = new SearchParameters([
            'recent_zero_stocks' => false,
            'limit' => 100,
        ]);

        $this->assertSame([
            'recent_zero_stocks' => false,
            'limit' => 100,
        ], $parameters->toArrayRecursive());
    }

    public function test_商品検索専用と未知の条件は保持も送信もしない(): void
    {
        $parameters = new SearchParameters([
            'group_ids' => [301],
            'stock_managed' => true,
            'sort' => '-make_date',
            'sales_price_min' => 100,
            'make_date_min' => '2026-01-01',
            'unknown_param' => 'ignored',
            'name' => '架空',
        ]);

        $this->assertSame(['name' => '架空'], $parameters->toArrayRecursive());
        $this->assertArrayNotHasKey('group_ids', $parameters->toArray());
        $this->assertArrayNotHasKey('sort', $parameters->toArray());
    }

    public function test_idsの不正な要素型を拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        new SearchParameters(['ids' => [101, '102']]);
    }

    public function test_不正な掲載状態を拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        new SearchParameters(['display_state' => 'members_only']);
    }
}
