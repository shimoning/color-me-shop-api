<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests;

use GuzzleHttp\Psr7\Response as Psr7Response;
use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Client;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Communicator\NoContent;
use Shimoning\ColorMeShopApi\Constants\PickupType;
use Shimoning\ColorMeShopApi\Entities\Product\BigCategory;
use Shimoning\ColorMeShopApi\Entities\Product\CategoryChildInput;
use Shimoning\ColorMeShopApi\Entities\Product\CategoryInput;
use Shimoning\ColorMeShopApi\Entities\Product\Group;
use Shimoning\ColorMeShopApi\Entities\Product\GroupInput;
use Shimoning\ColorMeShopApi\Entities\Product\Option;
use Shimoning\ColorMeShopApi\Entities\Product\OptionInput;
use Shimoning\ColorMeShopApi\Entities\Product\OptionValue;
use Shimoning\ColorMeShopApi\Entities\Product\OptionValueInput;
use Shimoning\ColorMeShopApi\Entities\Product\Pickup;
use Shimoning\ColorMeShopApi\Entities\Product\PickupInput;
use Shimoning\ColorMeShopApi\Entities\Product\Product as ProductEntity;
use Shimoning\ColorMeShopApi\Entities\Product\ProductImage;
use Shimoning\ColorMeShopApi\Entities\Product\ProductInput;
use Shimoning\ColorMeShopApi\Entities\Product\SmallCategory;
use Shimoning\ColorMeShopApi\Entities\Product\Variant;
use Shimoning\ColorMeShopApi\Entities\Product\VariantInput;
use Shimoning\ColorMeShopApi\Tests\Support\HttpMock;

class ProductClientWriteTest extends TestCase
{
    /**
     * @param list<mixed> $args
     * @param class-string $expected
     */
    #[DataProvider('routes')]
    public function test_商品書き込みファサードが対応するリクエストへ委譲する(
        string $method,
        array $args,
        int $status,
        string $body,
        string $httpMethod,
        string $path,
        string $expected,
        ?string $requestBody,
    ): void {
        $mock = new HttpMock([new Psr7Response($status, ['Content-Type' => 'application/json'], $body)]);
        $client = new Client('token', $mock->client());

        $result = $client->$method(...$args);

        $this->assertInstanceOf($expected, $result);
        $this->assertSame($httpMethod, $mock->request()->getMethod());
        $this->assertSame($path, $mock->request()->getUri()->getPath());
        $this->assertSame('Bearer token', $mock->header('Authorization'));
        if ($requestBody === '') {
            $this->assertSame('', $mock->body());
        } else if ($requestBody !== null) {
            $this->assertSame(\json_decode($requestBody, true, 512, \JSON_THROW_ON_ERROR), $mock->jsonBody());
        }
    }

    /** @return array<string, array{string, list<mixed>, int, string, string, string, class-string, ?string}> */
    public static function routes(): array
    {
        $stream = \fopen('php://memory', 'r+');
        \fwrite($stream, 'image');
        \rewind($stream);

        return [
            'createProduct' => [
                'createProduct', [new ProductInput(['name' => '商品'])],
                200, '{"product":{"id":101,"name":"商品"}}', 'POST', '/v1/products', ProductEntity::class,
                '{"product":{"name":"商品"}}',
            ],
            'updateProduct' => [
                'updateProduct', [101, new ProductInput(['sales_price' => null])],
                200, '{"product":{"id":101}}', 'PUT', '/v1/products/101', ProductEntity::class,
                '{"product":{"sales_price":null}}',
            ],
            'updateProductVariant' => [
                'updateProductVariant', [101, 301, new VariantInput(['stocks' => 3])],
                200, '{"variant":{"id":301}}', 'PUT', '/v1/products/101/variants/301', Variant::class,
                '{"variant":{"stocks":3}}',
            ],
            'createProductOption' => [
                'createProductOption', [101, new OptionInput(['name' => '色', 'values' => [['name' => '赤']]])],
                201, '{"option":{"id":201}}', 'POST', '/v1/products/101/options', Option::class,
                '{"option":{"name":"色","values":[{"name":"赤"}]}}',
            ],
            'deleteProductOption' => [
                'deleteProductOption', [101, 201],
                204, '', 'DELETE', '/v1/products/101/options/201', NoContent::class, '',
            ],
            'createProductOptionValue' => [
                'createProductOptionValue', [101, 201, new OptionValueInput(['name' => '青'])],
                201, '{"option_value":{"value_id":3}}', 'POST', '/v1/products/101/options/201/values', OptionValue::class,
                '{"option_value":{"name":"青"}}',
            ],
            'deleteProductOptionValue' => [
                'deleteProductOptionValue', [101, 201, 3],
                204, '', 'DELETE', '/v1/products/101/options/201/values/3', NoContent::class, '',
            ],
            'createProductPickup' => [
                'createProductPickup', [101, new PickupInput(['pickup_type' => 1, 'order_num' => 0])],
                200, '{"pickup":{"pickup_type":1}}', 'POST', '/v1/products/101/pickups', Pickup::class,
                '{"pickup_type":1,"order_num":0}',
            ],
            'updateProductPickup' => [
                'updateProductPickup', [101, new PickupInput(['pickup_type' => 1, 'order_num' => 2])],
                200, '{"pickup":{"pickup_type":1}}', 'PUT', '/v1/products/101/pickups', Pickup::class,
                '{"pickup_type":1,"order_num":2}',
            ],
            'deleteProductPickup' => [
                'deleteProductPickup', [101, PickupType::BEST_SELLER],
                200, '{"pickup":{"pickup_type":1}}', 'DELETE', '/v1/products/101/pickups/1', Pickup::class, '',
            ],
            'createProductImage' => [
                'createProductImage', [101, $stream, 0],
                201, '{"product_image":{"position":0,"url":"https://example.invalid/a.jpg"}}', 'POST',
                '/v1/products/101/images', ProductImage::class, null,
            ],
            'deleteProductImage' => [
                'deleteProductImage', [101, 0],
                204, '', 'DELETE', '/v1/products/101/images/0', NoContent::class, '',
            ],
            'createProductGroup' => [
                'createProductGroup', [new GroupInput(['name' => '夏物', 'parent_group_id' => null])],
                201, '{"group":{"id":401,"name":"夏物"}}', 'POST', '/v1/groups', Group::class,
                '{"group":{"name":"夏物","parent_group_id":null}}',
            ],
            'updateProductGroup' => [
                'updateProductGroup', [401, new GroupInput(['expl' => null, 'meta_tag' => ['title' => 't']])],
                200, '{"group":{"id":401}}', 'PUT', '/v1/groups/401', Group::class,
                '{"group":{"expl":null,"meta_tag":{"title":"t"}}}',
            ],
            'createProductCategory' => [
                'createProductCategory', [new CategoryInput(['name' => 'Tシャツ'])],
                201, '{"category":{"id_big":9001,"id_small":0,"children":[]}}', 'POST', '/v1/categories', BigCategory::class,
                '{"category":{"name":"Tシャツ"}}',
            ],
            'updateProductCategory' => [
                'updateProductCategory', [9001, new CategoryInput(['sort' => 2])],
                200, '{"category":{"id_big":9001,"id_small":0}}', 'PUT', '/v1/categories/9001', BigCategory::class,
                '{"category":{"sort":2}}',
            ],
            'createProductCategoryChild' => [
                'createProductCategoryChild', [9001, new CategoryChildInput(['name' => '半袖'])],
                201, '{"category":{"id_big":9001,"id_small":5}}', 'POST', '/v1/categories/9001/children', SmallCategory::class,
                '{"category":{"name":"半袖"}}',
            ],
            'updateProductCategoryChild' => [
                'updateProductCategoryChild', [9001, 5, new CategoryChildInput(['display_state' => 'hidden'])],
                200, '{"category":{"id_big":9001,"id_small":5}}', 'PUT', '/v1/categories/9001/children/5', SmallCategory::class,
                '{"category":{"display_state":"hidden"}}',
            ],
        ];
    }

    public function test_画像作成ファサードは送信ファイル名をServiceへ渡す(): void
    {
        $stream = \fopen('php://memory', 'r+');
        $this->assertNotFalse($stream);
        \fwrite($stream, 'image');
        \rewind($stream);
        $mock = new HttpMock([new Psr7Response(201, ['Content-Type' => 'application/json'], '{"product_image":{"position":0,"url":"https://example.invalid/a.jpg"}}')]);
        $client = new Client('token', $mock->client());

        $result = $client->createProductImage(101, $stream, 0, null, 'photo.png');

        $this->assertInstanceOf(ProductImage::class, $result);
        $this->assertStringContainsString('name="image"; filename="photo.png"', $mock->body());
    }

    public function test_書き込みファサードのエラーはErrorsとして返る(): void
    {
        $mock = HttpMock::json(422, self::fixture('errors_422.json'));
        $client = new Client('token', $mock->client());

        $errors = $client->updateProduct(101, new ProductInput(['display_state' => 'hidden']));

        $this->assertInstanceOf(Errors::class, $errors);
        $this->assertSame(422, $errors->getResponse()->getStatus());
    }

    public function test_書き込みファサードは引数のアクセストークンを優先する(): void
    {
        $mock = new HttpMock([new Psr7Response(204, [], '')]);
        $client = new Client('token', $mock->client());

        $client->deleteProductOption(101, 201, 'other-token');

        $this->assertSame('Bearer other-token', $mock->header('Authorization'));
    }
}
