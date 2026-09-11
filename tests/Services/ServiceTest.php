<?php

namespace Shimoning\ColorMeShopApi\Tests\Services;

use GuzzleHttp\Psr7\Response as Psr7Response;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Communicator\Request;
use Shimoning\ColorMeShopApi\Communicator\RequestMeta;
use Shimoning\ColorMeShopApi\Communicator\Response;
use Shimoning\ColorMeShopApi\Services\Service;
use Shimoning\ColorMeShopApi\Tests\Support\HttpMock;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class ServiceTest extends TestCase
{
    private function makeResponse(int $status, string $body): Response
    {
        return new Response(
            new Psr7Response($status, [], $body),
            new RequestMeta('GET', 'https://api.shop-pro.jp/v1/test', []),
        );
    }

    public function test_基底クラスは抽象クラスである(): void
    {
        $this->assertTrue((new \ReflectionClass(Service::class))->isAbstract());
    }

    public function test__endpointはベースURLとパスをスラッシュ重複なしで結合する(): void
    {
        $service = new ServiceStub('my-token');

        $this->assertSame('https://api.shop-pro.jp/v1/sales', $service->endpointForTest('/sales'));
        $this->assertSame('https://api.shop-pro.jp/v1/sales', $service->endpointForTest('sales'));
    }

    public function test__requestは既定のアクセストークンと指定オプションを使う(): void
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

    public function test__requestは引数のアクセストークンを優先する(): void
    {
        $mock = HttpMock::json(200, '{}');
        $service = new ServiceStub('my-token', $mock->client());

        $service->requestForTest([], 'override-token')->get($service->endpointForTest('/test'));

        $this->assertSame('Bearer override-token', $mock->header('Authorization'));
    }

    public function test__handleは成功時にパース済みボディをmapperで変換する(): void
    {
        $service = new ServiceStub('my-token');
        $response = $this->makeResponse(200, '{"value":21}');

        $result = $service->handleForTest(
            $response,
            fn(?array $data): int => ($data['value'] ?? 0) * 2,
        );

        $this->assertSame(42, $result);
    }

    public function test__handleは失敗時にmapperを呼び出さずErrorsを返す(): void
    {
        $service = new ServiceStub('my-token');
        $response = $this->makeResponse(
            422,
            '{"errors":[{"code":"422210","message":"invalid","status":422}]}',
        );

        $result = $service->handleForTest(
            $response,
            fn(?array $data): mixed => throw new \LogicException('mapper must not be called'),
        );

        $this->assertInstanceOf(Errors::class, $result);
        $this->assertSame($response, $result->getResponse());
        $this->assertSame('422210', $result[0]->getCode());
    }

    public function test__handleは成功した空ボディもmapperに渡す(): void
    {
        $service = new ServiceStub('my-token');
        $response = $this->makeResponse(204, '');

        $result = $service->handleForTest(
            $response,
            fn(?array $data): bool => $data === null,
        );

        $this->assertTrue($result);
    }
}

final class ServiceStub extends Service
{
    public function endpointForTest(string $path): string
    {
        return $this->_endpoint($path);
    }

    public function requestForTest(array $options = [], ?string $accessToken = null): Request
    {
        return $this->_request($options, $accessToken);
    }

    /**
     * @template T
     * @param callable(?array): T $mapper
     * @return T|Errors
     */
    public function handleForTest(Response $response, callable $mapper): mixed
    {
        return $this->_handle($response, $mapper);
    }
}
