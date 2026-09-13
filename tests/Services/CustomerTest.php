<?php

namespace Shimoning\ColorMeShopApi\Tests\Services;

use Shimoning\ColorMeShopApi\Services\Customer;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Page;
use Shimoning\ColorMeShopApi\Entities\Customer\Customer as CustomerEntity;
use Shimoning\ColorMeShopApi\Entities\Customer\SearchParameters;
use Shimoning\ColorMeShopApi\Exceptions\InvalidPaginationException;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;
use Shimoning\ColorMeShopApi\Tests\Support\HttpMock;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class CustomerTest extends TestCase
{
    // --- page -------------------------------------------------------------

    public function test_顧客一覧をPageとして取得する(): void
    {
        $mock = HttpMock::json(200, self::fixture('customers_page.json'));

        $page = (new Customer('my-token', $mock->client()))->page(new SearchParameters([]));

        $this->assertInstanceOf(Page::class, $page);
        $this->assertSame(2, $page->count());
        $this->assertContainsOnlyInstancesOf(CustomerEntity::class, $page->all());
        $this->assertSame([501, 502], \array_map(fn($c) => $c->getId(), $page->all()));
    }

    public function test_顧客一覧はmetaからページング情報を組み立てる(): void
    {
        $mock = HttpMock::json(200, self::fixture('customers_page.json'));

        $page = (new Customer('my-token', $mock->client()))->page(new SearchParameters([]));

        $this->assertSame(2, $page->getTotal());
        $this->assertSame(10, $page->getLimit());
        $this->assertSame(0, $page->getOffset());
    }

    public function test_顧客一覧は正しいエンドポイントにGETする(): void
    {
        $mock = HttpMock::json(200, self::fixture('customers_page.json'));

        (new Customer('my-token', $mock->client()))->page(new SearchParameters([]));

        $this->assertSame('GET', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/customers', $mock->uri());
        $this->assertSame('Bearer my-token', $mock->header('Authorization'));
    }

    public function test_検索条件をクエリ文字列に変換して送信する(): void
    {
        $mock = HttpMock::json(200, self::fixture('customers_page.json'));

        (new Customer('my-token', $mock->client()))->page(new SearchParameters([
            'limit' => 20,
            'offset' => 40,
        ]));

        $this->assertSame(['limit' => '20', 'offset' => '40'], $mock->query());
    }

    public function test_顧客一覧のエラーレスポンス(): void
    {
        $mock = HttpMock::json(401, self::fixture('errors_401.json'));

        $errors = (new Customer('my-token', $mock->client()))->page(new SearchParameters([]));

        $this->assertInstanceOf(Errors::class, $errors);
    }

    public function test_顧客一覧の200応答でmetaがnullなら固有例外で早期に失敗する(): void
    {
        $mock = HttpMock::json(200, '{"customers":[{"id":501}],"meta":null}');

        $this->expectException(InvalidPaginationException::class);
        $this->expectExceptionMessage(
            'GET /v1/customers のレスポンスのページネーション情報「meta」が不正です。array を期待しましたが null でした。',
        );

        (new Customer('my-token', $mock->client()))->page(new SearchParameters([]));
    }

    public function test_顧客一覧の200応答でmetaの型が不正なら固有例外で早期に失敗する(): void
    {
        $mock = HttpMock::json(200, '{"customers":[],"meta":{"total":0,"limit":10,"offset":false}}');

        $this->expectException(InvalidPaginationException::class);
        $this->expectExceptionMessage(
            'GET /v1/customers のレスポンスのページネーション情報「meta.offset」が不正です。int を期待しましたが bool でした。',
        );

        (new Customer('my-token', $mock->client()))->page(new SearchParameters([]));
    }

    // --- one --------------------------------------------------------------

    public function test_顧客を1件取得する(): void
    {
        $mock = HttpMock::json(200, self::fixture('customer.json'));

        $customer = (new Customer('my-token', $mock->client()))->one(501);

        $this->assertInstanceOf(CustomerEntity::class, $customer);
        $this->assertSame(501, $customer->getId());
        $this->assertSame('山田太郎', $customer->getName());
        $this->assertSame('https://api.shop-pro.jp/v1/customers/501', $mock->uri());
    }

    public function test_顧客fixtureで欠損する購入回数は参照時に固有例外になる(): void
    {
        $mock = HttpMock::json(200, self::fixture('customer.json'));
        $customer = (new Customer('my-token', $mock->client()))->one(501);

        $this->expectException(MissingFieldException::class);

        $customer->getSalesCount();
    }

    public function test_顧客IDは文字列でも渡せる(): void
    {
        $mock = HttpMock::json(200, self::fixture('customer.json'));

        (new Customer('my-token', $mock->client()))->one('501');

        $this->assertSame('https://api.shop-pro.jp/v1/customers/501', $mock->uri());
    }

    public function test_顧客1件取得のエラーレスポンス(): void
    {
        $mock = HttpMock::json(404, '{"errors":[{"code":"404100","message":"NG","status":404}]}');

        $this->assertInstanceOf(Errors::class, (new Customer('my-token', $mock->client()))->one(9999));
    }

    public function test_引数のアクセストークンが優先される(): void
    {
        $mock = HttpMock::json(200, self::fixture('customer.json'));

        (new Customer('my-token', $mock->client()))->one(501, 'override-token');

        $this->assertSame('Bearer override-token', $mock->header('Authorization'));
    }
}
