<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests;

use Shimoning\ColorMeShopApi\Client;
use Shimoning\ColorMeShopApi\Entities\Page;
use Shimoning\ColorMeShopApi\Entities\Stock\SearchParameters;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use Shimoning\ColorMeShopApi\Tests\Support\HttpMock;

class StockClientTest extends TestCase
{
    public function test_getStocksは在庫サービスへ委譲する(): void
    {
        $mock = HttpMock::json(200, self::fixture('stocks_page.json'));

        $result = (new Client('token', $mock->client()))->getStocks(new SearchParameters([
            'stocks' => 5,
            'fields' => 'product_id,name,stocks',
        ]));

        $this->assertInstanceOf(Page::class, $result);
        $this->assertSame('GET', $mock->request()->getMethod());
        $this->assertSame('/v1/stocks', $mock->request()->getUri()->getPath());
        $this->assertSame([
            'stocks' => '5',
            'fields' => 'product_id,name,stocks',
        ], $mock->query());
    }

    public function test_getStocksは検索条件を省略できる(): void
    {
        $mock = HttpMock::json(200, '{"stocks":[],"meta":{"total":0,"limit":10,"offset":0}}');

        (new Client('token', $mock->client()))->getStocks();

        $this->assertSame([], $mock->query());
    }

    public function test_getStocksは引数のアクセストークンを優先する(): void
    {
        $mock = HttpMock::json(200, '{"stocks":[],"meta":{"total":0,"limit":10,"offset":0}}');

        (new Client('constructor-token', $mock->client()))->getStocks(null, 'argument-token');

        $this->assertSame('Bearer argument-token', $mock->header('Authorization'));
    }

    public function test_コンストラクタに空文字を渡してもトークン未指定として扱う(): void
    {
        $this->expectException(ParameterException::class);

        (new Client(''))->getStocks();
    }
}
