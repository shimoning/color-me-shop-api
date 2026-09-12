<?php

namespace Shimoning\ColorMeShopApi\Tests\Services;

use Shimoning\ColorMeShopApi\Services\Delivery;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Constants\DeliveryMethodType;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Delivery\Delivery as DeliveryEntity;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;
use Shimoning\ColorMeShopApi\Tests\Support\HttpMock;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class DeliveryTest extends TestCase
{
    public function test_配送方法の一覧を取得する(): void
    {
        $mock = HttpMock::json(200, self::fixture('deliveries.json'));

        $deliveries = (new Delivery('my-token', $mock->client()))->all();

        $this->assertInstanceOf(Collection::class, $deliveries);
        $this->assertSame(1, $deliveries->count());
        $this->assertContainsOnlyInstancesOf(DeliveryEntity::class, $deliveries->all());
        $this->assertSame('宅急便', $deliveries[0]->getName());
        $this->assertSame(DeliveryMethodType::YAMATO, $deliveries[0]->getMethodType());
    }

    public function test_配送方法fixtureで欠損する配送料設定は参照時に固有例外になる(): void
    {
        $mock = HttpMock::json(200, self::fixture('deliveries.json'));
        $deliveries = (new Delivery('my-token', $mock->client()))->all();

        $this->expectException(MissingFieldException::class);

        $deliveries[0]->getCharge();
    }

    public function test_正しいエンドポイントにGETする(): void
    {
        $mock = HttpMock::json(200, self::fixture('deliveries.json'));

        (new Delivery('my-token', $mock->client()))->all();

        $this->assertSame('GET', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/deliveries', $mock->uri());
        $this->assertSame('Bearer my-token', $mock->header('Authorization'));
    }

    public function test_deliveriesキーがなければ空のコレクションを返す(): void
    {
        $mock = HttpMock::json(200, '{}');

        $this->assertSame(0, (new Delivery('my-token', $mock->client()))->all()->count());
    }

    public function test_エラーレスポンスならErrorsを返す(): void
    {
        $mock = HttpMock::json(500, '{}');

        $this->assertInstanceOf(Errors::class, (new Delivery('my-token', $mock->client()))->all());
    }
}
