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
}
