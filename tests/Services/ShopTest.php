<?php

namespace Shimoning\ColorMeShopApi\Tests\Services;

use Shimoning\ColorMeShopApi\Services\Shop;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Constants\ShopState;
use Shimoning\ColorMeShopApi\Entities\Shop\Shop as ShopEntity;
use Shimoning\ColorMeShopApi\Tests\Support\HttpMock;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class ShopTest extends TestCase
{
    public function test_ショップ情報を取得する(): void
    {
        $mock = HttpMock::json(200, self::fixture('shop.json'));

        $shop = (new Shop('my-token', $mock->client()))->get();

        $this->assertInstanceOf(ShopEntity::class, $shop);
        $this->assertSame('my-shop', $shop->getId());
        $this->assertSame(ShopState::ENABLED, $shop->getState());
    }

    public function test_正しいエンドポイントにGETする(): void
    {
        $mock = HttpMock::json(200, self::fixture('shop.json'));

        (new Shop('my-token', $mock->client()))->get();

        $this->assertSame('GET', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/shop', $mock->uri());
    }

    public function test_コンストラクタのアクセストークンを使う(): void
    {
        $mock = HttpMock::json(200, self::fixture('shop.json'));

        (new Shop('my-token', $mock->client()))->get();

        $this->assertSame('Bearer my-token', $mock->header('Authorization'));
    }

    public function test_引数のアクセストークンが優先される(): void
    {
        $mock = HttpMock::json(200, self::fixture('shop.json'));

        (new Shop('my-token', $mock->client()))->get('override-token');

        $this->assertSame('Bearer override-token', $mock->header('Authorization'));
    }

    public function test_エラーレスポンスならErrorsを返す(): void
    {
        $mock = HttpMock::json(401, self::fixture('errors_401.json'));

        $errors = (new Shop('my-token', $mock->client()))->get();

        $this->assertInstanceOf(Errors::class, $errors);
        $this->assertSame(1, $errors->count());
        $this->assertSame('401010', $errors[0]->getCode());
    }

    public function test_shopキーがなくても空のエンティティを返す(): void
    {
        $mock = HttpMock::json(200, '{}');

        $shop = (new Shop('my-token', $mock->client()))->get();

        $this->assertInstanceOf(ShopEntity::class, $shop);
        $this->assertSame([], $shop->getRaw());
    }
}
