<?php

namespace Shimoning\ColorMeShopApi\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Client;
use Shimoning\ColorMeShopApi\Entities\Page;
use Shimoning\ColorMeShopApi\Entities\Product\AdvertisingSearchParameters;
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

    public function test_広告一覧の条件とページ情報をClient経由で扱える(): void
    {
        $mock = HttpMock::json(200, '{"product_advertisings":[],"meta":{"total":51,"limit":1,"offset":50}}');
        $client = new Client('token', $mock->client());

        $page = $client->getProductAdvertisings(new AdvertisingSearchParameters(['limit' => 1, 'offset' => 50]));

        $this->assertInstanceOf(Page::class, $page);
        $this->assertSame(51, $page->getTotal());
        $this->assertSame(['limit' => '1', 'offset' => '50'], $mock->query());
    }

    public function test_バリエーション条件をClient経由で送信する(): void
    {
        $mock = HttpMock::json(200, '{"variants":[],"meta":{"total":0,"limit":10,"offset":0}}');
        $client = new Client('token', $mock->client());

        $client->getProductVariants(101, modelNumber: 'TEST', fields: 'id,model_number');

        $this->assertSame(['model_number' => 'TEST', 'fields' => 'id,model_number'], $mock->query());
    }
}
