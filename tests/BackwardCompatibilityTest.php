<?php

namespace Shimoning\ColorMeShopApi\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Communicator\Request;
use Shimoning\ColorMeShopApi\Communicator\RequestOptions;

/**
 * Phase 3 の DI リファクタで公開シグネチャを壊していないことを保証する。
 *
 * リファクタは「任意引数の追加のみ」に限定しているため、
 * 必須引数の数と既存の呼び出し方が変わらないことを固定する。
 */
class BackwardCompatibilityTest extends TestCase
{
    public static function serviceProvider(): array
    {
        $services = ['Customer', 'Delivery', 'Payment', 'Product', 'Sales', 'Shop'];

        $cases = [];
        foreach ($services as $service) {
            $cases[$service] = ['Shimoning\\ColorMeShopApi\\Services\\' . $service];
        }
        return $cases;
    }

    #[DataProvider('serviceProvider')]
    public function test_サービスはアクセストークン1つだけで生成できる(string $service): void
    {
        $this->assertInstanceOf($service, new $service('dummy-token'));
    }

    #[DataProvider('serviceProvider')]
    public function test_サービスの必須引数はアクセストークンのみ(string $service): void
    {
        $constructor = (new \ReflectionClass($service))->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertSame(1, $constructor->getNumberOfRequiredParameters(), $service);
        $this->assertSame('accessToken', $constructor->getParameters()[0]->getName());
    }

    /**
     * ClientInterface の注入は公開 API の一部になったため、
     * 第2引数の存在と名前も固定しておく。
     */
    #[DataProvider('serviceProvider')]
    public function test_サービスの第2引数はhttpClientである(string $service): void
    {
        $parameters = (new \ReflectionClass($service))->getConstructor()->getParameters();

        $this->assertCount(2, $parameters, $service);
        $this->assertSame('httpClient', $parameters[1]->getName(), $service);
        $this->assertTrue($parameters[1]->allowsNull(), $service);
    }

    public function test_OAuthサービスの引数はoptionsとhttpClientである(): void
    {
        $parameters = (new \ReflectionClass(\Shimoning\ColorMeShopApi\Services\OAuth::class))
            ->getConstructor()->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertSame('options', $parameters[0]->getName());
        $this->assertSame('httpClient', $parameters[1]->getName());
        $this->assertTrue($parameters[1]->allowsNull());
    }

    public function test_Requestの引数はoptionsとclientである(): void
    {
        $parameters = (new \ReflectionClass(Request::class))->getConstructor()->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertSame('options', $parameters[0]->getName());
        $this->assertSame('client', $parameters[1]->getName());
        $this->assertTrue($parameters[0]->allowsNull());
        $this->assertTrue($parameters[1]->allowsNull());
    }

    public function test_OAuthサービスはOptionsだけで生成できる(): void
    {
        $options = new \Shimoning\ColorMeShopApi\Entities\OAuth\Options('id', 'secret', 'https://example.test/callback');

        $this->assertInstanceOf(
            \Shimoning\ColorMeShopApi\Services\OAuth::class,
            new \Shimoning\ColorMeShopApi\Services\OAuth($options),
        );
    }

    public function test_Requestは引数なしで生成できる(): void
    {
        $this->assertInstanceOf(Request::class, new Request());
    }

    public function test_RequestはRequestOptionsだけで生成できる(): void
    {
        $this->assertInstanceOf(Request::class, new Request(new RequestOptions(['json' => true])));
    }

    public function test_Requestの必須引数はない(): void
    {
        $constructor = (new \ReflectionClass(Request::class))->getConstructor();

        $this->assertSame(0, $constructor->getNumberOfRequiredParameters());
    }

    public function test_Clientはアクセストークンなしでも生成できる(): void
    {
        $this->assertInstanceOf(
            \Shimoning\ColorMeShopApi\Client::class,
            new \Shimoning\ColorMeShopApi\Client(),
        );
    }

    public function test_Clientはアクセストークンだけで生成できる(): void
    {
        $this->assertInstanceOf(
            \Shimoning\ColorMeShopApi\Client::class,
            new \Shimoning\ColorMeShopApi\Client('my-token'),
        );
    }

    public function test_Clientの必須引数はない(): void
    {
        $constructor = (new \ReflectionClass(\Shimoning\ColorMeShopApi\Client::class))->getConstructor();

        $this->assertSame(0, $constructor->getNumberOfRequiredParameters());
        $this->assertSame('accessToken', $constructor->getParameters()[0]->getName());
    }

    public function test_Clientの引数はaccessTokenとhttpClientである(): void
    {
        $parameters = (new \ReflectionClass(\Shimoning\ColorMeShopApi\Client::class))
            ->getConstructor()->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertSame('accessToken', $parameters[0]->getName());
        $this->assertSame('httpClient', $parameters[1]->getName());
        $this->assertTrue($parameters[0]->allowsNull());
        $this->assertTrue($parameters[1]->allowsNull());
    }
}
