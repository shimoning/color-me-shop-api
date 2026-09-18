<?php

namespace Shimoning\ColorMeShopApi\Tests\Services;

use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Page;
use Shimoning\ColorMeShopApi\Entities\Product\Advertising;
use Shimoning\ColorMeShopApi\Entities\Product\AdvertisingSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\Group;
use Shimoning\ColorMeShopApi\Entities\Product\Product as ProductEntity;
use Shimoning\ColorMeShopApi\Entities\Product\ProductImage;
use Shimoning\ColorMeShopApi\Entities\Product\SearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\Variant;
use Shimoning\ColorMeShopApi\Services\Product;
use Shimoning\ColorMeShopApi\Tests\Support\HttpMock;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class ProductReadTest extends TestCase
{
    public function test_商品一覧は検索クエリとmetaを持つPageを返す(): void
    {
        $fixture = self::fixtureArray('products_read.json');
        $mock = HttpMock::json(200, json_encode([
            'products' => [$fixture['product']],
            'meta' => ['total' => 1, 'limit' => 50, 'offset' => 0],
        ]));

        $page = (new Product('token', $mock->client()))->products(new SearchParameters([
            'ids' => [101, 102], 'group_ids' => [301, 302], 'limit' => 100,
        ]));

        $this->assertInstanceOf(Page::class, $page);
        $this->assertInstanceOf(ProductEntity::class, $page[0]);
        $this->assertSame(50, $page->getLimit());
        $this->assertSame('/v1/products', $mock->request()->getUri()->getPath());
        $this->assertSame(['ids' => '101,102', 'group_ids' => '301,302', 'limit' => '100'], $mock->query());
    }

    public function test_商品とバリエーション単体を取得する(): void
    {
        $fixture = self::fixtureArray('products_read.json');
        $productMock = HttpMock::json(200, json_encode(['product' => $fixture['product']]));
        $product = (new Product('token', $productMock->client()))->product('101');
        $this->assertInstanceOf(ProductEntity::class, $product);
        $this->assertSame('https://api.shop-pro.jp/v1/products/101', $productMock->uri());

        $variantMock = HttpMock::json(200, json_encode(['variant' => $fixture['variant']]));
        $variant = (new Product('token', $variantMock->client()))->variant(101, 301);
        $this->assertInstanceOf(Variant::class, $variant);
        $this->assertNull($variant->getOption2());
        $this->assertSame('https://api.shop-pro.jp/v1/products/101/variants/301', $variantMock->uri());
    }

    public function test_バリエーション一覧は指定したlimitとoffsetでPageを返す(): void
    {
        $fixture = self::fixtureArray('products_read.json');
        $mock = HttpMock::json(200, json_encode([
            'variants' => [$fixture['variant']],
            'meta' => ['total' => 16, 'limit' => 100, 'offset' => 10],
        ]));

        $page = (new Product('token', $mock->client()))->variants(101, 100, 10);
        $this->assertInstanceOf(Page::class, $page);
        $this->assertInstanceOf(Variant::class, $page[0]);
        $this->assertSame(100, $page->getLimit());
        $this->assertSame(10, $page->getOffset());
        $this->assertSame(['limit' => '100', 'offset' => '10'], $mock->query());
        $this->assertSame('/v1/products/101/variants', $mock->request()->getUri()->getPath());
    }

    public function test_バリエーション一覧は型番と取得フィールドを送信する(): void
    {
        $mock = HttpMock::json(200, '{"variants":[],"meta":{"total":0,"limit":10,"offset":0}}');

        (new Product('token', $mock->client()))->variants(101, modelNumber: 'TEST', fields: 'id,model_number');

        $this->assertSame(['model_number' => 'TEST', 'fields' => 'id,model_number'], $mock->query());
    }

    public function test_画像専用GETはProductImageのCollectionを返す(): void
    {
        $fixture = self::fixtureArray('products_read.json');
        $mock = HttpMock::json(200, json_encode(['product' => ['id' => 101, 'images' => [$fixture['product_image']]]]));

        $images = (new Product('token', $mock->client()))->images(101);
        $this->assertInstanceOf(Collection::class, $images);
        $this->assertInstanceOf(ProductImage::class, $images[0]);
        $this->assertSame('/v1/products/101/images', $mock->request()->getUri()->getPath());
    }

    public function test_画像ゼロ枚とバリエーション既定件数を扱える(): void
    {
        $imagesMock = HttpMock::json(200, '{"product":{"id":101,"images":[]}}');
        $images = (new Product('token', $imagesMock->client()))->images(101);
        $this->assertSame(0, $images->count());

        $variantsMock = HttpMock::json(200, '{"variants":[],"meta":{"total":16,"limit":10,"offset":0}}');
        $variants = (new Product('token', $variantsMock->client()))->variants(101);
        $this->assertSame(10, $variants->getLimit());
        $this->assertSame([], $variantsMock->query());
    }

    public function test_広告とグループ単体を取得する(): void
    {
        $fixture = self::fixtureArray('products_read.json');
        $adMock = HttpMock::json(200, json_encode([
            'product_advertisings' => [$fixture['advertising']],
            'meta' => ['total' => 61, 'limit' => 25, 'offset' => 50],
        ]));
        $ads = (new Product('token', $adMock->client()))->advertisings(new AdvertisingSearchParameters([
            'product_ids' => [101, 102], 'display_state' => 'showing', 'limit' => 25, 'offset' => 50,
        ]));
        $this->assertInstanceOf(Page::class, $ads);
        $this->assertInstanceOf(Advertising::class, $ads[0]);
        $this->assertSame(61, $ads->getTotal());
        $this->assertSame(25, $ads->getLimit());
        $this->assertSame(50, $ads->getOffset());
        $this->assertSame([
            'product_ids' => '101,102', 'display_state' => 'showing', 'limit' => '25', 'offset' => '50',
        ], $adMock->query());
        $this->assertSame('/v1/product_advertisings', $adMock->request()->getUri()->getPath());

        $groupMock = HttpMock::json(200, '{"group":{"id":401,"name":"テストグループ"}}');
        $group = (new Product('token', $groupMock->client()))->group(401);
        $this->assertInstanceOf(Group::class, $group);
        $this->assertSame(401, $group->getId());
        $this->assertSame('/v1/groups/401', $groupMock->request()->getUri()->getPath());
    }

    #[DataProvider('errorMethodProvider')]
    public function test_各GETのエラー応答はErrorsになる(string $method, array $arguments): void
    {
        $mock = HttpMock::json(404, '{"errors":[{"code":404100,"message":"not found","status":404}]}');
        $service = new Product('token', $mock->client());
        $this->assertInstanceOf(Errors::class, $service->$method(...$arguments));
    }

    public static function errorMethodProvider(): array
    {
        return [
            ['products', [new SearchParameters([])]], ['product', [999]],
            ['variants', [999]], ['variant', [999, 999]],
            ['images', [999]], ['advertisings', []], ['group', [999]],
        ];
    }
}
