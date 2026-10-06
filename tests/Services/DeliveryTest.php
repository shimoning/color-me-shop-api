<?php

namespace Shimoning\ColorMeShopApi\Tests\Services;

use Shimoning\ColorMeShopApi\Services\Delivery;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Constants\DeliveryMethodType;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Delivery\Delivery as DeliveryEntity;
use Shimoning\ColorMeShopApi\Entities\Delivery\Date\Date;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
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

    public function test_配送日時設定を取得する(): void
    {
        $mock = HttpMock::json(200, self::fixture('delivery_date.json'));

        $deliveryDate = (new Delivery('my-token', $mock->client()))->dateSetting();

        $this->assertInstanceOf(Date::class, $deliveryDate);
        $this->assertSame('my-shop', $deliveryDate->getAccountId());
        $this->assertSame(2, $deliveryDate->getDays()->getMin());
        $this->assertSame(['午前中', '14時から16時'], $deliveryDate->getTimes()->getPeriods());
        $this->assertSame(1725148800, $deliveryDate->getMakeDate()?->getTimestamp());
    }

    public function test_delivery_dateキーがなければ空の配送日時設定を返す(): void
    {
        $mock = HttpMock::json(200, '{}');

        $deliveryDate = (new Delivery('my-token', $mock->client()))->dateSetting();

        $this->assertInstanceOf(Date::class, $deliveryDate);
        foreach (['getAccountId', 'getDays', 'getTimes'] as $getter) {
            try {
                $deliveryDate->{$getter}();
                $this->fail($getter . ' が MissingFieldException を投げなかった');
            } catch (MissingFieldException $exception) {
                $this->assertSame(MissingFieldException::class, $exception::class);
            }
        }
        $this->assertNull($deliveryDate->getMakeDate());
        $this->assertNull($deliveryDate->getUpdateDate());
    }

    public function test_配送日時設定を正しいエンドポイントへAuthorization付きでGETする(): void
    {
        $mock = HttpMock::json(200, self::fixture('delivery_date.json'));

        (new Delivery('my-token', $mock->client()))->dateSetting();

        $this->assertSame('GET', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/deliveries/date', $mock->uri());
        $this->assertSame('Bearer my-token', $mock->header('Authorization'));
    }

    public function test_配送日時設定のエラーレスポンスならErrorsを返す(): void
    {
        $mock = HttpMock::json(401, self::fixture('errors_401.json'));

        $this->assertInstanceOf(
            Errors::class,
            (new Delivery('my-token', $mock->client()))->dateSetting(),
        );
    }

    public function test_配送日時設定で空のアクセストークンは送信前に拒否される(): void
    {
        $mock = HttpMock::json(200, self::fixture('delivery_date.json'));
        $delivery = new Delivery('my-token', $mock->client());

        try {
            $delivery->dateSetting('');
            $this->fail('空のアクセストークンで API 呼び出しが受理された');
        } catch (ParameterException $exception) {
            $this->assertSame('アクセストークンは必ず指定してください', $exception->getMessage());
            $this->assertSame(0, $mock->countRequests());
        }
    }

    public function test_配送日時設定で引数のアクセストークンが優先される(): void
    {
        $mock = HttpMock::json(200, self::fixture('delivery_date.json'));

        (new Delivery('my-token', $mock->client()))->dateSetting('override-token');

        $this->assertSame('Bearer override-token', $mock->header('Authorization'));
    }
}
