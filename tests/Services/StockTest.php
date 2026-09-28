<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Services;

use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Page;
use Shimoning\ColorMeShopApi\Entities\Stock\SearchParameters;
use Shimoning\ColorMeShopApi\Entities\Stock\Stock as StockEntity;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use Shimoning\ColorMeShopApi\Services\Stock;
use Shimoning\ColorMeShopApi\Tests\Support\HttpMock;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class StockTest extends TestCase
{
    public function test_在庫一覧は検索クエリとmetaを持つPageを返す(): void
    {
        $mock = HttpMock::json(200, self::fixture('stocks_page.json'));

        $page = (new Stock('token', $mock->client()))->page(new SearchParameters([
            'ids' => [101, 102],
            'display_state' => 'showing',
            'recent_zero_stocks' => true,
            'limit' => 50,
            'offset' => 10,
        ]));

        $this->assertInstanceOf(Page::class, $page);
        $this->assertCount(2, $page);
        $this->assertContainsOnlyInstancesOf(StockEntity::class, $page->all());
        $this->assertSame(2, $page->getTotal());
        $this->assertSame(10, $page->getLimit());
        $this->assertSame(0, $page->getOffset());
        $this->assertSame('GET', $mock->request()->getMethod());
        $this->assertSame(
            'https://api.shop-pro.jp/v1/stocks',
            (string) $mock->request()->getUri()->withQuery(''),
        );
        $this->assertSame([
            'ids' => '101,102',
            'display_state' => 'showing',
            'recent_zero_stocks' => '1',
            'limit' => '50',
            'offset' => '10',
        ], $mock->query());
    }

    public function test_エラー応答はErrorsになる(): void
    {
        $mock = HttpMock::json(401, self::fixture('errors_401.json'));

        $result = (new Stock('token', $mock->client()))->page(new SearchParameters([]));

        $this->assertInstanceOf(Errors::class, $result);
    }

    public function test_page引数のアクセストークンが空なら送信前に例外になる(): void
    {
        $this->expectException(ParameterException::class);
        (new Stock('constructor-token'))->page(new SearchParameters([]), '');
    }

    public function test_page引数のアクセストークンを優先する(): void
    {
        $mock = HttpMock::json(200, '{"stocks":[],"meta":{"total":0,"limit":10,"offset":0}}');

        (new Stock('constructor-token', $mock->client()))->page(
            new SearchParameters([]),
            'argument-token',
        );

        $this->assertSame('Bearer argument-token', $mock->header('Authorization'));
    }
}
