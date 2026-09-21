<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Communicator;

use GuzzleHttp\Psr7\Response as Psr7Response;
use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Communicator\NoContent;
use Shimoning\ColorMeShopApi\Communicator\RequestMeta;
use Shimoning\ColorMeShopApi\Communicator\Response;
use Shimoning\ColorMeShopApi\Entities\Entity;

class NoContentTest extends TestCase
{
    public function test_204の応答情報をEntityではない値オブジェクトとして保持する(): void
    {
        $response = new Response(
            new Psr7Response(204, ['X-Request-Id' => 'request-id']),
            new RequestMeta('DELETE', 'https://api.shop-pro.jp/v1/products/1/options/2', []),
        );

        $result = new NoContent($response);

        $this->assertNotInstanceOf(Entity::class, $result);
        $this->assertSame($response, $result->getResponse());
        $this->assertSame(204, $result->getResponse()->getStatus());
        $this->assertSame(['request-id'], $result->getResponse()->getRawHeader()['X-Request-Id']);
        $this->assertFalse(\method_exists($result, 'getStatus'));
        $this->assertFalse(\method_exists($result, 'getRawHeader'));
    }
}
