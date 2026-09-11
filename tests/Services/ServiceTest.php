<?php

namespace Shimoning\ColorMeShopApi\Tests\Services;

use Shimoning\ColorMeShopApi\Communicator\Request;
use Shimoning\ColorMeShopApi\Services\Service;
use Shimoning\ColorMeShopApi\Tests\Support\HttpMock;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class ServiceTest extends TestCase
{
    public function test_基底クラスは抽象クラスである(): void
    {
        $this->assertTrue((new \ReflectionClass(Service::class))->isAbstract());
    }

    public function test_endpointはベースURLとパスをスラッシュ重複なしで結合する(): void
    {
        $service = new ServiceStub('my-token');

        $this->assertSame('https://api.shop-pro.jp/v1/sales', $service->endpointForTest('/sales'));
        $this->assertSame('https://api.shop-pro.jp/v1/sales', $service->endpointForTest('sales'));
    }

    public function test_requestは既定のアクセストークンと指定オプションを使う(): void
    {
        $mock = HttpMock::json(200, '{}');
        $service = new ServiceStub('my-token', $mock->client());

        $service->requestForTest(['json' => true])->post(
            $service->endpointForTest('/test'),
            ['value' => 1],
        );

        $this->assertSame('Bearer my-token', $mock->header('Authorization'));
        $this->assertStringContainsString('application/json', $mock->header('Content-Type'));
        $this->assertSame(['value' => 1], $mock->jsonBody());
    }

    public function test_requestは引数のアクセストークンを優先する(): void
    {
        $mock = HttpMock::json(200, '{}');
        $service = new ServiceStub('my-token', $mock->client());

        $service->requestForTest([], 'override-token')->get($service->endpointForTest('/test'));

        $this->assertSame('Bearer override-token', $mock->header('Authorization'));
    }
}

final class ServiceStub extends Service
{
    public function endpointForTest(string $path): string
    {
        return $this->endpoint($path);
    }

    public function requestForTest(array $options = [], ?string $accessToken = null): Request
    {
        return $this->request($options, $accessToken);
    }
}
