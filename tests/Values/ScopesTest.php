<?php

namespace Shimoning\ColorMeShopApi\Tests\Values;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Values\Scopes;
use Shimoning\ColorMeShopApi\Constants\AuthScope;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;

class ScopesTest extends TestCase
{
    public function test_AuthScopeの配列をスペース区切りで連結する(): void
    {
        $scopes = new Scopes([AuthScope::READ_SALES, AuthScope::WRITE_SALES]);

        $this->assertSame('read_sales write_sales', $scopes->get());
    }

    public function test_文字列の配列を受け付ける(): void
    {
        $scopes = new Scopes(['read_products', 'write_products']);

        $this->assertSame('read_products write_products', $scopes->get());
    }

    public function test_AuthScopeと文字列を混在できる(): void
    {
        $scopes = new Scopes([AuthScope::READ_SHOP_COUPONS, 'read_sales']);

        $this->assertSame('read_shop_coupons read_sales', $scopes->get());
    }

    public function test_単一のスコープはそのまま返る(): void
    {
        $this->assertSame('read_sales', (new Scopes([AuthScope::READ_SALES]))->get());
    }

    public function test_定義されていない文字列はParameterExceptionを投げる(): void
    {
        $this->expectException(ParameterException::class);
        $this->expectExceptionMessage('scope は、文字列か AuthScope 型を設定してください。');

        new Scopes(['read_unknown']);
    }

    public function test_文字列でもAuthScopeでもない値はParameterExceptionを投げる(): void
    {
        $this->expectException(ParameterException::class);

        new Scopes([123]);
    }

    public function test_不正な値が1つでも含まれていれば例外を投げる(): void
    {
        $this->expectException(ParameterException::class);

        new Scopes([AuthScope::READ_SALES, 'read_unknown']);
    }

    public function test_validateは常にtrueを返す(): void
    {
        $scopes = new Scopes([AuthScope::READ_SALES]);

        $this->assertTrue($scopes->validate([]));
    }
}
