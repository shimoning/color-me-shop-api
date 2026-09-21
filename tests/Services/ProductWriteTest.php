<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Services;

use GuzzleHttp\Psr7\Response as Psr7Response;
use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Communicator\NoContent;
use Shimoning\ColorMeShopApi\Constants\PickupType;
use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;
use Shimoning\ColorMeShopApi\Entities\Product\Option;
use Shimoning\ColorMeShopApi\Entities\Product\OptionInput;
use Shimoning\ColorMeShopApi\Entities\Product\OptionValue;
use Shimoning\ColorMeShopApi\Entities\Product\OptionValueInput;
use Shimoning\ColorMeShopApi\Entities\Product\Pickup;
use Shimoning\ColorMeShopApi\Entities\Product\PickupInput;
use Shimoning\ColorMeShopApi\Entities\Product\Product as ProductEntity;
use Shimoning\ColorMeShopApi\Entities\Product\ProductImage;
use Shimoning\ColorMeShopApi\Entities\Product\ProductInput;
use Shimoning\ColorMeShopApi\Entities\Product\Variant;
use Shimoning\ColorMeShopApi\Entities\Product\VariantInput;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use Shimoning\ColorMeShopApi\Services\Product;
use Shimoning\ColorMeShopApi\Tests\Support\HttpMock;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class ProductWriteTest extends TestCase
{
    private static function productJson(): string
    {
        return \json_encode(['product' => self::fixtureArray('products_read.json')['product']]);
    }

    private static function noContent(): HttpMock
    {
        return new HttpMock([new Psr7Response(204, ['X-Request-Id' => 'request-id'], '')]);
    }

    // --- product ----------------------------------------------------------

    public function test_商品作成はproductキーのJSONをPOSTし商品Entityを返す(): void
    {
        $mock = HttpMock::json(200, self::productJson());

        $product = (new Product('token', $mock->client()))->create(new ProductInput([
            'name' => 'テスト商品', 'display_state' => 'hidden', 'stock_managed' => false,
        ]));

        $this->assertInstanceOf(ProductEntity::class, $product);
        $this->assertSame('テスト商品', $product->getName());
        $this->assertSame('POST', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/products', $mock->uri());
        $this->assertStringContainsString('application/json', $mock->header('Content-Type'));
        $this->assertSame('Bearer token', $mock->header('Authorization'));
        $this->assertSame(
            ['product' => ['name' => 'テスト商品', 'display_state' => 'hidden', 'stock_managed' => false]],
            $mock->jsonBody(),
        );
    }

    public function test_商品更新はproductキーのJSONをPUTし明示したnullを送信し未指定を省略する(): void
    {
        $mock = HttpMock::json(200, self::productJson());

        $product = (new Product('token', $mock->client()))->update(101, new ProductInput([
            'sales_price' => null, 'display_state' => 'showing',
        ]));

        $this->assertInstanceOf(ProductEntity::class, $product);
        $this->assertSame(ProductDisplayState::SHOWING, $product->getDisplayState());
        $this->assertSame('PUT', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/products/101', $mock->uri());
        $this->assertSame('{"product":{"sales_price":null,"display_state":"showing"}}', $mock->body());
    }

    public function test_商品更新はincrementとバリエーションのstocksをそのまま送信する(): void
    {
        $mock = HttpMock::json(200, self::productJson());

        (new Product('token', $mock->client()))->update('101', new ProductInput([
            'stocks' => ['increment' => 5],
            'variants' => [['option1_value' => 'S', 'stocks' => null]],
        ]));

        $this->assertSame(
            '{"product":{"stocks":{"increment":5},"variants":[{"option1_value":"S","stocks":null}]}}',
            $mock->body(),
        );
    }

    public function test_空の商品入力はJSONの空objectとして送信する(): void
    {
        $mock = HttpMock::json(200, self::productJson());

        (new Product('token', $mock->client()))->update(101, new ProductInput([]));

        $this->assertSame('{"product":{}}', $mock->body());
    }

    public function test_空の商品入力の作成もJSONの空objectとして送信する(): void
    {
        $mock = HttpMock::json(200, self::productJson());

        (new Product('token', $mock->client()))->create(new ProductInput([]));

        $this->assertSame('{"product":{}}', $mock->body());
    }

    public function test_商品更新の404と422はErrorsになる(): void
    {
        $notFound = HttpMock::json(404, '{"errors":[{"code":404100,"message":"not found","status":404}]}');
        $invalid = HttpMock::json(422, '{"errors":[{"code":422001,"field":"product.disp_flg","message":"invalid","status":422}]}');

        $errors404 = (new Product('token', $notFound->client()))->update(999, new ProductInput(['name' => 'x']));
        $errors422 = (new Product('token', $invalid->client()))->update(101, new ProductInput(['name' => 'x']));

        $this->assertInstanceOf(Errors::class, $errors404);
        $this->assertSame(404, $errors404->getResponse()->getStatus());
        $this->assertInstanceOf(Errors::class, $errors422);
        $this->assertSame(1, $errors422->count());
        $this->assertSame('product.disp_flg', $errors422[0]->getField());
    }

    public function test_商品作成のエラーレスポンス(): void
    {
        $mock = HttpMock::json(422, self::fixture('errors_422.json'));

        $errors = (new Product('token', $mock->client()))->create(new ProductInput(['name' => 'x']));

        $this->assertInstanceOf(Errors::class, $errors);
        $this->assertSame(2, $errors->count());
    }

    public function test_引数のアクセストークンが優先される(): void
    {
        $mock = HttpMock::json(200, self::productJson());

        (new Product('token', $mock->client()))->create(new ProductInput(['name' => 'x']), 'other-token');

        $this->assertSame('Bearer other-token', $mock->header('Authorization'));
    }

    // --- variant ----------------------------------------------------------

    public function test_バリエーション更新はvariantキーのJSONをPUTしVariantを返す(): void
    {
        $variant = self::fixtureArray('products_read.json')['product']['variants'][0];
        $variant['stocks'] = 3;
        $mock = HttpMock::json(200, \json_encode(['variant' => $variant]));

        $result = (new Product('token', $mock->client()))->updateVariant(101, 301, new VariantInput([
            'stocks' => 3, 'weight' => null,
        ]));

        $this->assertInstanceOf(Variant::class, $result);
        $this->assertSame(3, $result->getStocks());
        $this->assertSame('PUT', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/products/101/variants/301', $mock->uri());
        $this->assertSame('{"variant":{"stocks":3,"weight":null}}', $mock->body());
    }

    public function test_空のバリエーション入力はJSONの空objectとして送信する(): void
    {
        $mock = HttpMock::json(200, self::fixture('products_read.json'));

        (new Product('token', $mock->client()))->updateVariant(101, 301, new VariantInput([]));

        $this->assertSame('{"variant":{}}', $mock->body());
    }

    public function test_バリエーション更新のエラーレスポンス(): void
    {
        $mock = HttpMock::json(404, self::fixture('errors_401.json'));

        $this->assertInstanceOf(Errors::class, (new Product('token', $mock->client()))
            ->updateVariant(101, 999, new VariantInput(['stocks' => 3])));
    }

    // --- option -----------------------------------------------------------

    public function test_オプション作成はoptionキーのJSONをPOSTし201のOptionを返す(): void
    {
        $mock = HttpMock::json(201, self::fixture('product_option_created.json'));

        $option = (new Product('token', $mock->client()))->createOption(101, new OptionInput([
            'name' => 'サイズ', 'values' => [['name' => 'S'], ['name' => 'M']],
        ]));

        $this->assertInstanceOf(Option::class, $option);
        $this->assertSame(201, $option->getId());
        $this->assertSame(['S', 'M'], $option->getValues());
        $this->assertSame('POST', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/products/101/options', $mock->uri());
        $this->assertSame(
            ['option' => ['name' => 'サイズ', 'values' => [['name' => 'S'], ['name' => 'M']]]],
            $mock->jsonBody(),
        );
    }

    public function test_空のオプション入力とオプション値入力もJSONの空objectとして送信する(): void
    {
        $option = HttpMock::json(201, self::fixture('product_option_created.json'));
        $value = HttpMock::json(201, self::fixture('product_option_value_created.json'));

        (new Product('token', $option->client()))->createOption(101, new OptionInput([]));
        (new Product('token', $value->client()))->createOptionValue(101, 201, new OptionValueInput([]));

        $this->assertSame('{"option":{}}', $option->body());
        $this->assertSame('{"option_value":{}}', $value->body());
    }

    public function test_オプション削除はボディなしでDELETEし204をNoContentで返す(): void
    {
        $mock = self::noContent();

        $result = (new Product('token', $mock->client()))->deleteOption(101, 201);

        $this->assertInstanceOf(NoContent::class, $result);
        $this->assertSame(204, $result->getResponse()->getStatus());
        $this->assertSame(['request-id'], $result->getResponse()->getRawHeader()['X-Request-Id']);
        $this->assertSame('DELETE', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/products/101/options/201', $mock->uri());
        $this->assertSame('', $mock->body());
        $this->assertNull($mock->header('Content-Type'));
        $this->assertSame('Bearer token', $mock->header('Authorization'));
    }

    public function test_オプション値作成はoption_valueキーのJSONをPOSTし201のOptionValueを返す(): void
    {
        $mock = HttpMock::json(201, self::fixture('product_option_value_created.json'));

        $value = (new Product('token', $mock->client()))->createOptionValue(101, 201, new OptionValueInput(['name' => 'L']));

        $this->assertInstanceOf(OptionValue::class, $value);
        $this->assertSame(3, $value->getValueId());
        $this->assertSame('L', $value->getName());
        $this->assertSame('POST', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/products/101/options/201/values', $mock->uri());
        $this->assertSame(['option_value' => ['name' => 'L']], $mock->jsonBody());
    }

    public function test_オプション値削除は204をNoContentで返し最後の値の422はErrorsになる(): void
    {
        $mock = self::noContent();
        $service = new Product('token', $mock->client());

        $result = $service->deleteOptionValue(101, 201, 3);

        $this->assertInstanceOf(NoContent::class, $result);
        $this->assertSame('DELETE', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/products/101/options/201/values/3', $mock->uri());
        $this->assertSame('', $mock->body());

        $lastValue = HttpMock::json(422, '{"errors":[{"code":422001,"message":"last value","status":422}]}');
        $errors = (new Product('token', $lastValue->client()))->deleteOptionValue(101, 201, 3);
        $this->assertInstanceOf(Errors::class, $errors);
        $this->assertNull($errors[0]->getField());
    }

    // --- pickup -----------------------------------------------------------

    public function test_ピックアップ作成はトップレベルのJSONをPOSTしPickupを返す(): void
    {
        $mock = HttpMock::json(200, self::fixture('product_pickup.json'));

        $pickup = (new Product('token', $mock->client()))->createPickup(101, new PickupInput([
            'pickup_type' => 3, 'order_num' => 1,
        ]));

        $this->assertInstanceOf(Pickup::class, $pickup);
        $this->assertSame(3, $pickup->getPickupType());
        $this->assertSame(1, $pickup->getOrderNum());
        $this->assertSame(101, $pickup->getProductId());
        $this->assertSame('TEST_ACCOUNT', $pickup->getAccountId());
        $this->assertSame('POST', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/products/101/pickups', $mock->uri());
        $this->assertSame('{"pickup_type":3,"order_num":1}', $mock->body());
    }

    #[DataProvider('incompletePickupInputProvider')]
    public function test_ピックアップの作成と更新はpickup_typeとorder_numが未指定なら送信前に拒否する(array $fields): void
    {
        $mock = HttpMock::json(200, self::fixture('product_pickup.json'));
        $service = new Product('token', $mock->client());

        foreach (['createPickup', 'updatePickup'] as $method) {
            try {
                $service->$method(101, new PickupInput($fields));
                $this->fail($method . ' が ParameterException を投げませんでした。');
            } catch (ParameterException $exception) {
                $this->assertStringContainsString('pickup_type', $exception->getMessage());
                $this->assertStringContainsString('order_num', $exception->getMessage());
            }
        }
        $this->assertSame(0, $mock->countRequests());
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function incompletePickupInputProvider(): array
    {
        return [
            'empty' => [[]],
            'pickup_type only' => [['pickup_type' => 3]],
            'order_num only' => [['order_num' => 1]],
        ];
    }

    public function test_ピックアップ更新はトップレベルのJSONをPUTする(): void
    {
        $mock = HttpMock::json(200, self::fixture('product_pickup.json'));

        $pickup = (new Product('token', $mock->client()))->updatePickup(101, new PickupInput([
            'pickup_type' => 3, 'order_num' => null,
        ]));

        $this->assertInstanceOf(Pickup::class, $pickup);
        $this->assertSame('PUT', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/products/101/pickups', $mock->uri());
        $this->assertSame('{"pickup_type":3,"order_num":null}', $mock->body());
    }

    #[DataProvider('pickupTypeProvider')]
    public function test_ピックアップ削除は200の削除済みPickupを返す(PickupType|int|string $pickupType): void
    {
        $mock = HttpMock::json(200, self::fixture('product_pickup.json'));

        $pickup = (new Product('token', $mock->client()))->deletePickup(101, $pickupType);

        $this->assertInstanceOf(Pickup::class, $pickup);
        $this->assertNotInstanceOf(NoContent::class, $pickup);
        $this->assertSame(3, $pickup->getPickupType());
        $this->assertSame('DELETE', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/products/101/pickups/3', $mock->uri());
        $this->assertSame('', $mock->body());
    }

    /** @return array<string, array{PickupType|int|string}> */
    public static function pickupTypeProvider(): array
    {
        return [
            'enum' => [PickupType::NEW_ARRIVAL],
            'int' => [3],
            'string' => ['3'],
        ];
    }

    public function test_ピックアップのエラーレスポンス(): void
    {
        $mock = HttpMock::json(404, self::fixture('errors_401.json'));

        $this->assertInstanceOf(Errors::class, (new Product('token', $mock->client()))
            ->deletePickup(999, PickupType::RECOMMENDED));
    }

    // --- image ------------------------------------------------------------

    public function test_画像作成はmultipartでimageとpositionを送信し201のProductImageを返す(): void
    {
        $path = \tempnam(\sys_get_temp_dir(), 'colorme-image-');
        $this->assertNotFalse($path);
        \file_put_contents($path, 'png-bytes');
        $mock = HttpMock::json(201, self::fixture('product_image_created.json'));

        try {
            $image = (new Product('token', $mock->client()))->createImage(101, $path, 0);
        } finally {
            \unlink($path);
        }

        $this->assertInstanceOf(ProductImage::class, $image);
        $this->assertSame(0, $image->getPosition());
        $this->assertSame('https://example.invalid/product/101.jpg', $image->getUrl());
        $this->assertSame('POST', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/products/101/images', $mock->uri());
        $this->assertStringStartsWith('multipart/form-data; boundary=', $mock->header('Content-Type'));
        $body = $mock->body();
        $this->assertStringContainsString('name="position"', $body);
        $this->assertStringContainsString("\r\n\r\n0\r\n", $body);
        $this->assertStringContainsString('name="image"; filename="' . \basename($path) . '"', $body);
        $this->assertStringContainsString('png-bytes', $body);
        $this->assertSame('Bearer token', $mock->header('Authorization'));
    }

    public function test_画像作成はストリームも受け付ける(): void
    {
        $stream = \fopen('php://memory', 'r+');
        $this->assertNotFalse($stream);
        \fwrite($stream, 'stream-bytes');
        \rewind($stream);
        $mock = HttpMock::json(201, self::fixture('product_image_created.json'));

        $image = (new Product('token', $mock->client()))->createImage(101, $stream, 5);

        $this->assertInstanceOf(ProductImage::class, $image);
        $this->assertStringContainsString("\r\n\r\n5\r\n", $mock->body());
        $this->assertStringContainsString('stream-bytes', $mock->body());
    }

    public function test_画像作成は読めないファイルを送信前に拒否する(): void
    {
        $mock = HttpMock::json(201, self::fixture('product_image_created.json'));

        try {
            (new Product('token', $mock->client()))->createImage(101, '/nonexistent/image.png', 0);
            $this->fail('読めないファイルが受理されました。');
        } catch (ParameterException $exception) {
            $this->assertStringContainsString('image', $exception->getMessage());
            $this->assertSame(0, $mock->countRequests());
        }
    }

    public function test_画像作成の422はErrorsになる(): void
    {
        $stream = \fopen('php://memory', 'r+');
        $this->assertNotFalse($stream);
        $mock = HttpMock::json(422, self::fixture('errors_422.json'));

        $errors = (new Product('token', $mock->client()))->createImage(101, $stream, 50);

        $this->assertInstanceOf(Errors::class, $errors);
        $this->assertSame(422, $errors->getResponse()->getStatus());
    }

    public function test_画像削除はボディなしでDELETEし204をNoContentで返す(): void
    {
        $mock = self::noContent();

        $result = (new Product('token', $mock->client()))->deleteImage(101, 2);

        $this->assertInstanceOf(NoContent::class, $result);
        $this->assertSame(204, $result->getResponse()->getStatus());
        $this->assertSame('DELETE', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/products/101/images/2', $mock->uri());
        $this->assertSame('', $mock->body());
    }

    public function test_画像削除の404はErrorsになる(): void
    {
        $mock = HttpMock::json(404, '{"errors":[{"code":404100,"message":"not found","status":404}]}');

        $this->assertInstanceOf(Errors::class, (new Product('token', $mock->client()))->deleteImage(101, 2));
    }
}
