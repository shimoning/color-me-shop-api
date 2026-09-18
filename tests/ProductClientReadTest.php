<?php

namespace Shimoning\ColorMeShopApi\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Client;
use Shimoning\ColorMeShopApi\Entities\Product\SearchParameters;
use Shimoning\ColorMeShopApi\Tests\Support\HttpMock;

class ProductClientReadTest extends TestCase
{
    #[DataProvider('routes')]
    public function test_商品読み取りファサードが対応するGETへ委譲する(string $method, array $args, string $body, string $path): void
    {
        $mock = HttpMock::json(200, $body);
        $client = new Client('token', $mock->client());

        $client->$method(...$args);

        $this->assertSame('GET', $mock->request()->getMethod());
        $this->assertSame($path, $mock->request()->getUri()->getPath());
    }

    public static function routes(): array
    {
        return [
            ['getProducts', [new SearchParameters([])], '{"products":[],"meta":{"total":0,"limit":10,"offset":0}}', '/v1/products'],
            ['getProduct', [101], '{"product":{"id":101}}', '/v1/products/101'],
            ['getProductVariants', [101], '{"variants":[],"meta":{"total":0,"limit":10,"offset":0}}', '/v1/products/101/variants'],
            ['getProductVariant', [101, 301], '{"variant":{"id":301}}', '/v1/products/101/variants/301'],
            ['getProductImages', [101], '{"product":{"id":101,"images":[]}}', '/v1/products/101/images'],
            ['getProductAdvertisings', [], '{"product_advertisings":[]}', '/v1/product_advertisings'],
            ['getProductGroup', [401], '{"group":{"id":401}}', '/v1/groups/401'],
        ];
    }
}
