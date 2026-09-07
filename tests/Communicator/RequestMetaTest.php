<?php

namespace Shimoning\ColorMeShopApi\Tests\Communicator;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Communicator\RequestMeta;

class RequestMetaTest extends TestCase
{
    public function test_渡されたリクエスト情報をそのまま保持する(): void
    {
        $options = ['headers' => ['Authorization' => 'Bearer x'], 'http_errors' => false];
        $meta = new RequestMeta('POST', 'https://api.shop-pro.jp/v1/sales', $options);

        $this->assertSame('POST', $meta->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/sales', $meta->getUri());
        $this->assertSame($options, $meta->getOptions());
    }

    public function test_オプションが空でも保持できる(): void
    {
        $meta = new RequestMeta('GET', 'https://example.test', []);

        $this->assertSame([], $meta->getOptions());
    }
}
