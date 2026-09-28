<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Services;

use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Gift\Gift as GiftEntity;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use Shimoning\ColorMeShopApi\Services\Gift;
use Shimoning\ColorMeShopApi\Tests\Support\HttpMock;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class GiftTest extends TestCase
{
    public function test_ギフト設定を取得する(): void
    {
        $mock = HttpMock::json(200, self::fixture('gift.json'));

        $gift = (new Gift('my-token', $mock->client()))->get();

        $this->assertInstanceOf(GiftEntity::class, $gift);
        $this->assertSame('my-shop', $gift->getAccountId());
        $this->assertSame('お祝い用のし', $gift->getNoshi()->getTypes()[0]->getName());
        $this->assertSame(1725148800, $gift->getMakeDate()?->getTimestamp());
    }

    public function test_正しいエンドポイントへAuthorization付きでGETする(): void
    {
        $mock = HttpMock::json(200, self::fixture('gift.json'));

        (new Gift('my-token', $mock->client()))->get();

        $this->assertSame('GET', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/gift', $mock->uri());
        $this->assertSame('Bearer my-token', $mock->header('Authorization'));
    }

    public function test_エラーレスポンスならErrorsを返す(): void
    {
        $mock = HttpMock::json(401, self::fixture('errors_401.json'));

        $this->assertInstanceOf(
            Errors::class,
            (new Gift('my-token', $mock->client()))->get(),
        );
    }

    public function test_空のアクセストークンは送信前に拒否される(): void
    {
        $mock = HttpMock::json(200, self::fixture('gift.json'));
        $gift = new Gift('my-token', $mock->client());

        try {
            $gift->get('');
            $this->fail('空のアクセストークンで API 呼び出しが受理された');
        } catch (ParameterException $exception) {
            $this->assertSame('アクセストークンは必ず指定してください', $exception->getMessage());
            $this->assertSame(0, $mock->countRequests());
        }
    }

    public function test_引数のアクセストークンが優先される(): void
    {
        $mock = HttpMock::json(200, self::fixture('gift.json'));

        (new Gift('my-token', $mock->client()))->get('override-token');

        $this->assertSame('Bearer override-token', $mock->header('Authorization'));
    }

    public function test_giftキーがなければ空のギフト設定を返す(): void
    {
        $mock = HttpMock::json(200, '{}');

        $gift = (new Gift('my-token', $mock->client()))->get();

        $this->assertInstanceOf(GiftEntity::class, $gift);
        foreach (['getAccountId', 'getNoshi', 'getCard', 'getWrapping'] as $getter) {
            try {
                $gift->{$getter}();
                $this->fail($getter . ' が MissingFieldException を投げなかった');
            } catch (MissingFieldException $exception) {
                $this->assertSame(MissingFieldException::class, $exception::class);
            }
        }
    }
}
