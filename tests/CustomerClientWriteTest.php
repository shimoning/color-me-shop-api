<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests;

use GuzzleHttp\Psr7\Response as Psr7Response;
use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Client;
use Shimoning\ColorMeShopApi\Entities\Customer\Customer as CustomerEntity;
use Shimoning\ColorMeShopApi\Entities\Customer\CustomerCreateInput;
use Shimoning\ColorMeShopApi\Tests\Support\HttpMock;

class CustomerClientWriteTest extends TestCase
{
    /**
     * @param list<mixed> $args
     * @param class-string $expected
     */
    #[DataProvider('routes')]
    public function test_顧客書き込みファサードが対応するリクエストへ委譲する(
        string $method,
        array $args,
        int $status,
        string $body,
        string $httpMethod,
        string $path,
        string $expected,
        ?string $requestBody,
    ): void {
        $mock = new HttpMock([new Psr7Response($status, ['Content-Type' => 'application/json'], $body)]);
        $client = new Client('token', $mock->client());

        $result = $client->$method(...$args);

        $this->assertInstanceOf($expected, $result);
        $this->assertSame($httpMethod, $mock->request()->getMethod());
        $this->assertSame($path, $mock->request()->getUri()->getPath());
        $this->assertSame('Bearer token', $mock->header('Authorization'));
        if ($requestBody !== null) {
            $this->assertSame(\json_decode($requestBody, true, 512, \JSON_THROW_ON_ERROR), $mock->jsonBody());
        }
    }

    /** @return array<string, array{string, list<mixed>, int, string, string, string, class-string, ?string}> */
    public static function routes(): array
    {
        return [
            'createCustomer' => [
                'createCustomer',
                [new CustomerCreateInput([
                    'name' => 'カラーミー太郎',
                    'mail' => 'taro@example.com',
                    'pref_id' => 13,
                    'postal' => '1508512',
                    'address1' => '渋谷区桜丘町26-1',
                    'tel' => '03-5456-2622',
                ])],
                200, '{"customer":{"id":501}}', 'POST', '/v1/customers', CustomerEntity::class,
                '{"customer":{"name":"カラーミー太郎","mail":"taro@example.com","pref_id":13,'
                    . '"postal":"1508512","address1":"渋谷区桜丘町26-1","tel":"03-5456-2622"}}',
            ],
        ];
    }

    public function test_引数のアクセストークンが優先される(): void
    {
        $mock = new HttpMock([new Psr7Response(200, ['Content-Type' => 'application/json'], '{"customer":{"id":501}}')]);
        $client = new Client('token', $mock->client());

        $client->createCustomer(
            new CustomerCreateInput([
                'name' => 'カラーミー太郎',
                'mail' => 'taro@example.com',
                'pref_id' => 13,
                'postal' => '1508512',
                'address1' => '渋谷区桜丘町26-1',
                'tel' => '03-5456-2622',
            ]),
            'other-token',
        );

        $this->assertSame('Bearer other-token', $mock->header('Authorization'));
    }
}
