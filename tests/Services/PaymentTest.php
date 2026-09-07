<?php

namespace Shimoning\ColorMeShopApi\Tests\Services;

use Shimoning\ColorMeShopApi\Services\Payment;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Constants\PaymentType;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Payment\Payment as PaymentEntity;
use Shimoning\ColorMeShopApi\Tests\Support\HttpMock;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class PaymentTest extends TestCase
{
    public function test_決済設定の一覧を取得する(): void
    {
        $mock = HttpMock::json(200, self::fixture('payments.json'));

        $payments = (new Payment('my-token', $mock->client()))->all();

        $this->assertInstanceOf(Collection::class, $payments);
        $this->assertSame(2, $payments->count());
        $this->assertContainsOnlyInstancesOf(PaymentEntity::class, $payments->all());
        $this->assertSame('カラーミークレジット', $payments[0]->getName());
        $this->assertSame(PaymentType::CREDIT_COLOR_ME, $payments[0]->getType());
        $this->assertSame('代金引換', $payments[1]->getName());
        $this->assertSame(PaymentType::COD, $payments[1]->getType());
    }

    public function test_正しいエンドポイントにGETする(): void
    {
        $mock = HttpMock::json(200, self::fixture('payments.json'));

        (new Payment('my-token', $mock->client()))->all();

        $this->assertSame('GET', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/payments', $mock->uri());
        $this->assertSame('Bearer my-token', $mock->header('Authorization'));
    }

    public function test_paymentsキーがなければ空のコレクションを返す(): void
    {
        $mock = HttpMock::json(200, '{}');

        $this->assertSame(0, (new Payment('my-token', $mock->client()))->all()->count());
    }

    public function test_エラーレスポンスならErrorsを返す(): void
    {
        $mock = HttpMock::json(401, self::fixture('errors_401.json'));

        $this->assertInstanceOf(Errors::class, (new Payment('my-token', $mock->client()))->all());
    }

    public function test_引数のアクセストークンが優先される(): void
    {
        $mock = HttpMock::json(200, self::fixture('payments.json'));

        (new Payment('my-token', $mock->client()))->all('override-token');

        $this->assertSame('Bearer override-token', $mock->header('Authorization'));
    }
}
