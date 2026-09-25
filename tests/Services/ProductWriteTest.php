<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Services;

use GuzzleHttp\Psr7\Response as Psr7Response;
use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Communicator\NoContent;
use Shimoning\ColorMeShopApi\Constants\CategoryDisplayState;
use Shimoning\ColorMeShopApi\Constants\ErrorCode;
use Shimoning\ColorMeShopApi\Constants\PickupType;
use Shimoning\ColorMeShopApi\Constants\GroupDisplayState;
use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;
use Shimoning\ColorMeShopApi\Entities\Product\BigCategory;
use Shimoning\ColorMeShopApi\Entities\Product\CategoryChildInput;
use Shimoning\ColorMeShopApi\Entities\Product\CategoryInput;
use Shimoning\ColorMeShopApi\Entities\Product\Group;
use Shimoning\ColorMeShopApi\Entities\Product\GroupInput;
use Shimoning\ColorMeShopApi\Entities\Product\Option;
use Shimoning\ColorMeShopApi\Entities\Product\OptionCreateInput;
use Shimoning\ColorMeShopApi\Entities\Product\OptionValue;
use Shimoning\ColorMeShopApi\Entities\Product\OptionValueCreateInput;
use Shimoning\ColorMeShopApi\Entities\Product\Pickup;
use Shimoning\ColorMeShopApi\Entities\Product\PickupInput;
use Shimoning\ColorMeShopApi\Entities\Product\Product as ProductEntity;
use Shimoning\ColorMeShopApi\Entities\Product\ProductImage;
use Shimoning\ColorMeShopApi\Entities\Product\ProductInput;
use Shimoning\ColorMeShopApi\Entities\Product\SmallCategory;
use Shimoning\ColorMeShopApi\Entities\Product\Variant;
use Shimoning\ColorMeShopApi\Entities\Product\VariantUpdateInput;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
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
            'variants' => [['option1_value' => 'S', 'stocks' => 3]],
        ]));

        $this->assertSame(
            '{"product":{"stocks":{"increment":5},"variants":[{"option1_value":"S","stocks":3}]}}',
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

    // --- group (Phase 3) --------------------------------------------------

    public function test_商品グループ作成はgroupキーのJSONをPOSTし201のGroupを返す(): void
    {
        $mock = HttpMock::json(201, self::fixture('group_created.json'));

        $group = (new Product('token', $mock->client()))->createGroup(new GroupInput([
            'name' => '夏物',
            'display_state' => 'showing',
            'parent_group_id' => null,
            'meta_tag' => ['title' => '夏物特集'],
        ]));

        $this->assertInstanceOf(Group::class, $group);
        $this->assertSame(401, $group->getId());
        $this->assertSame('夏物', $group->getName());
        $this->assertSame('夏物特集', $group->getMetaTag()?->getTitle());
        $this->assertSame('POST', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/groups', $mock->uri());
        $this->assertStringContainsString('application/json', $mock->header('Content-Type'));
        $this->assertSame('Bearer token', $mock->header('Authorization'));
        $this->assertSame(
            ['group' => ['name' => '夏物', 'display_state' => 'showing', 'parent_group_id' => null, 'meta_tag' => ['title' => '夏物特集']]],
            $mock->jsonBody(),
        );
        $this->assertStringContainsString('"parent_group_id":null', $mock->body());
    }

    public function test_商品グループ更新はgroupキーのJSONをPUTし明示したnullを送信し未指定を省略する(): void
    {
        $mock = HttpMock::json(200, self::fixture('group_created.json'));

        $group = (new Product('token', $mock->client()))->updateGroup(401, new GroupInput([
            'expl' => null, 'display_state' => GroupDisplayState::HIDDEN,
        ]));

        $this->assertInstanceOf(Group::class, $group);
        $this->assertSame('PUT', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/groups/401', $mock->uri());
        $this->assertSame('{"group":{"expl":null,"display_state":"hidden"}}', $mock->body());
    }

    public function test_空の商品グループ入力はJSONの空objectとして送信する(): void
    {
        $create = HttpMock::json(201, self::fixture('group_created.json'));
        $update = HttpMock::json(200, self::fixture('group_created.json'));

        (new Product('token', $create->client()))->createGroup(new GroupInput([]));
        (new Product('token', $update->client()))->updateGroup('401', new GroupInput([]));

        $this->assertSame('{"group":{}}', $create->body());
        $this->assertSame('{"group":{}}', $update->body());
    }

    public function test_商品グループ書き込みの404と422はErrorsになる(): void
    {
        $notFound = HttpMock::json(404, '{"errors":[{"code":404100,"message":"not found","status":404}]}');
        // 空入力 {"group":{}} の実測 (2026-09-21): 422210 / field "group" / 「パラメータが指定されていません。」
        $invalid = HttpMock::json(422, '{"errors":[{"code":422210,"field":"group","message":"パラメータが指定されていません。","status":422}]}');

        $errors404 = (new Product('token', $notFound->client()))->updateGroup(999, new GroupInput(['name' => 'x']));
        $errors422 = (new Product('token', $invalid->client()))->createGroup(new GroupInput([]));

        $this->assertInstanceOf(Errors::class, $errors404);
        $this->assertSame(404, $errors404->getResponse()->getStatus());
        $this->assertInstanceOf(Errors::class, $errors422);
        $this->assertSame(ErrorCode::VALIDATE_ERROR_FIELD, $errors422[0]->getErrorCode());
        $this->assertSame('group', $errors422[0]->getField());
        $this->assertSame('パラメータが指定されていません。', $errors422[0]->getMessage());
    }

    // --- category (Phase 3) -----------------------------------------------

    public function test_大カテゴリー作成はcategoryキーのJSONをPOSTし201のBigCategoryを返す(): void
    {
        $mock = HttpMock::json(201, self::fixture('category_created.json'));

        $category = (new Product('token', $mock->client()))->createCategory(new CategoryInput([
            'name' => 'Tシャツ', 'sort' => 1, 'display_state' => 'members_only', 'meta_tag' => ['keywords' => null],
        ]));

        $this->assertInstanceOf(BigCategory::class, $category);
        $this->assertSame(9001, $category->getIdBig());
        $this->assertSame(0, $category->getIdSmall());
        $this->assertSame([], $category->getChildren());
        $this->assertSame('POST', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/categories', $mock->uri());
        $this->assertStringContainsString('application/json', $mock->header('Content-Type'));
        $this->assertSame('Bearer token', $mock->header('Authorization'));
        $this->assertSame(
            ['category' => ['name' => 'Tシャツ', 'sort' => 1, 'display_state' => 'members_only', 'meta_tag' => ['keywords' => null]]],
            $mock->jsonBody(),
        );
        $this->assertStringContainsString('"meta_tag":{"keywords":null}', $mock->body());
    }

    public function test_大カテゴリー更新はcategoryキーのJSONをPUTし明示したnullを送信し未指定を省略する(): void
    {
        $mock = HttpMock::json(200, self::fixture('category_created.json'));

        $category = (new Product('token', $mock->client()))->updateCategory(9001, new CategoryInput([
            'expl' => null, 'display_state' => CategoryDisplayState::HIDDEN,
        ]));

        $this->assertInstanceOf(BigCategory::class, $category);
        $this->assertSame('PUT', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/categories/9001', $mock->uri());
        $this->assertSame('{"category":{"expl":null,"display_state":"hidden"}}', $mock->body());
    }

    public function test_小カテゴリー作成はcategoryキーのJSONをPOSTし201のSmallCategoryを返す(): void
    {
        $mock = HttpMock::json(201, self::fixture('category_child_created.json'));

        $category = (new Product('token', $mock->client()))->createCategoryChild(9001, new CategoryChildInput([
            'name' => '半袖', 'meta_tag' => ['title' => '半袖', 'description' => null],
        ]));

        $this->assertInstanceOf(SmallCategory::class, $category);
        $this->assertSame(9001, $category->getIdBig());
        $this->assertSame(5, $category->getIdSmall());
        $this->assertSame('半袖', $category->getName());
        $this->assertSame('POST', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/categories/9001/children', $mock->uri());
        $this->assertSame(
            ['category' => ['name' => '半袖', 'meta_tag' => ['title' => '半袖', 'description' => null]]],
            $mock->jsonBody(),
        );
        $this->assertStringContainsString('"description":null}', $mock->body());
    }

    public function test_小カテゴリー更新はcategoryキーのJSONをPUTしSmallCategoryを返す(): void
    {
        $mock = HttpMock::json(200, self::fixture('category_child_created.json'));

        $category = (new Product('token', $mock->client()))->updateCategoryChild('9001', '5', new CategoryChildInput([
            'sort' => null,
        ]));

        $this->assertInstanceOf(SmallCategory::class, $category);
        $this->assertSame('PUT', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/categories/9001/children/5', $mock->uri());
        $this->assertSame('{"category":{"sort":null}}', $mock->body());
    }

    public function test_空のカテゴリー入力はJSONの空objectとして送信する(): void
    {
        $big = HttpMock::json(201, self::fixture('category_created.json'));
        $small = HttpMock::json(201, self::fixture('category_child_created.json'));

        (new Product('token', $big->client()))->createCategory(new CategoryInput([]));
        (new Product('token', $small->client()))->createCategoryChild(9001, new CategoryChildInput([]));

        $this->assertSame('{"category":{}}', $big->body());
        $this->assertSame('{"category":{}}', $small->body());
    }

    public function test_大カテゴリー書き込みの応答が小カテゴリーなら例外になる(): void
    {
        $mock = HttpMock::json(201, self::fixture('category_child_created.json'));

        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('BigCategory');
        (new Product('token', $mock->client()))->createCategory(new CategoryInput(['name' => 'x']));
    }

    public function test_小カテゴリー書き込みの応答が大カテゴリーなら例外になる(): void
    {
        $mock = HttpMock::json(200, self::fixture('category_created.json'));

        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('SmallCategory');
        (new Product('token', $mock->client()))->updateCategoryChild(9001, 5, new CategoryChildInput(['name' => 'x']));
    }

    public function test_カテゴリー書き込みの応答にid_smallがなければ例外になる(): void
    {
        $mock = HttpMock::json(200, '{"category":{"id_big":9001}}');

        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('id_small');
        (new Product('token', $mock->client()))->updateCategory(9001, new CategoryInput(['name' => 'x']));
    }

    /**
     * 応答の `category` が配列でなければ `Category::fromArray()` の TypeError ではなく、
     * Phase 2 の `categories[n]` と同じく InvalidFieldException にする。
     */
    #[DataProvider('scalarCategoryResponseProvider')]
    public function test_カテゴリー書き込みの応答のcategoryが配列以外なら例外になる(string $body, string $expectedType): void
    {
        $mock = HttpMock::json(200, $body);

        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('category』が不正です。' . BigCategory::class . ' を期待しましたが ' . $expectedType . ' でした。');
        (new Product('token', $mock->client()))->updateCategory(9001, new CategoryInput(['name' => 'x']));
    }

    /** @return array<string, array{string, string}> */
    public static function scalarCategoryResponseProvider(): array
    {
        return [
            'string' => ['{"category":"x"}', 'string'],
            'int' => ['{"category":1}', 'int'],
            'bool' => ['{"category":true}', 'bool'],
        ];
    }

    public function test_小カテゴリー書き込みの応答のcategoryが配列以外なら例外になる(): void
    {
        $mock = HttpMock::json(201, '{"category":"x"}');

        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(SmallCategory::class . ' を期待しましたが string でした。');
        (new Product('token', $mock->client()))->createCategoryChild(9001, new CategoryChildInput(['name' => 'x']));
    }

    /**
     * 公式 OpenAPI はカテゴリーの 4 操作に 403 を列挙する (未観測)。Errors 経路は 2xx 以外を一律に扱う。
     */
    public function test_カテゴリー書き込みの403と404と422はErrorsになる(): void
    {
        $forbidden = HttpMock::json(403, '{"errors":[{"code":403000,"message":"forbidden","status":403}]}');
        $notFound = HttpMock::json(404, '{"errors":[{"code":404100,"message":"not found","status":404}]}');
        $invalid = HttpMock::json(422, self::fixture('errors_422.json'));

        $errors403 = (new Product('token', $forbidden->client()))->updateCategory(9001, new CategoryInput(['name' => 'x']));
        $errors404 = (new Product('token', $notFound->client()))->updateCategoryChild(9001, 999, new CategoryChildInput(['name' => 'x']));
        $errors422 = (new Product('token', $invalid->client()))->createCategory(new CategoryInput([]));

        $this->assertInstanceOf(Errors::class, $errors403);
        $this->assertSame(403, $errors403->getResponse()->getStatus());
        $this->assertInstanceOf(Errors::class, $errors404);
        $this->assertSame(404, $errors404->getResponse()->getStatus());
        $this->assertInstanceOf(Errors::class, $errors422);
        $this->assertSame(2, $errors422->count());
    }

    // --- variant ----------------------------------------------------------

    public function test_バリエーション更新はvariantキーのJSONをPUTしVariantを返す(): void
    {
        $variant = self::fixtureArray('products_read.json')['product']['variants'][0];
        $variant['stocks'] = 3;
        $mock = HttpMock::json(200, \json_encode(['variant' => $variant]));

        $result = (new Product('token', $mock->client()))->updateVariant(101, 301, new VariantUpdateInput([
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

        (new Product('token', $mock->client()))->updateVariant(101, 301, new VariantUpdateInput([]));

        $this->assertSame('{"variant":{}}', $mock->body());
    }

    public function test_バリエーション更新のエラーレスポンス(): void
    {
        $mock = HttpMock::json(404, self::fixture('errors_401.json'));

        $this->assertInstanceOf(Errors::class, (new Product('token', $mock->client()))
            ->updateVariant(101, 999, new VariantUpdateInput(['stocks' => 3])));
    }

    // --- option -----------------------------------------------------------

    public function test_オプション作成はoptionキーのJSONをPOSTし201のOptionを返す(): void
    {
        $mock = HttpMock::json(201, self::fixture('product_option_created.json'));

        $option = (new Product('token', $mock->client()))->createOption(101, new OptionCreateInput([
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

        (new Product('token', $option->client()))->createOption(101, new OptionCreateInput([]));
        (new Product('token', $value->client()))->createOptionValue(101, 201, new OptionValueCreateInput([]));

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

        $value = (new Product('token', $mock->client()))->createOptionValue(101, 201, new OptionValueCreateInput(['name' => 'L']));

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

    #[DataProvider('invalidPickupTypeProvider')]
    public function test_ピックアップ削除はPickupTypeにない種別を送信前に拒否する(int|string $pickupType): void
    {
        $mock = HttpMock::json(200, self::fixture('product_pickup.json'));

        try {
            (new Product('token', $mock->client()))->deletePickup(101, $pickupType);
            $this->fail('ParameterException が送出されていません。');
        } catch (ParameterException $exception) {
            $this->assertStringContainsString('pickup_type', $exception->getMessage());
        }
        $this->assertSame(0, $mock->countRequests());
    }

    /** @return array<string, array{int|string}> */
    public static function invalidPickupTypeProvider(): array
    {
        return [
            'undefined int' => [2],
            'undefined string' => ['2'],
            'non numeric' => ['abc'],
            'path segment' => ['3/../999'],
            'empty' => [''],
            'float-like' => ['3.0'],
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

    public function test_画像作成はストリームに送信ファイル名を指定できる(): void
    {
        $stream = \fopen('php://memory', 'r+');
        $this->assertNotFalse($stream);
        \fwrite($stream, 'stream-bytes');
        \rewind($stream);
        $mock = HttpMock::json(201, self::fixture('product_image_created.json'));

        $image = (new Product('token', $mock->client()))->createImage(101, $stream, 5, null, 'photo.png');

        $this->assertInstanceOf(ProductImage::class, $image);
        $this->assertStringContainsString('name="image"; filename="photo.png"', $mock->body());
    }

    public function test_画像作成はファイル名を省略するとストリームのURIの末尾を送る(): void
    {
        $stream = \fopen('php://memory', 'r+');
        $this->assertNotFalse($stream);
        \fwrite($stream, 'stream-bytes');
        \rewind($stream);
        $mock = HttpMock::json(201, self::fixture('product_image_created.json'));

        (new Product('token', $mock->client()))->createImage(101, $stream, 5);

        $this->assertStringContainsString('name="image"; filename="memory"', $mock->body());
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
