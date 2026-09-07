<?php

namespace Shimoning\ColorMeShopApi\Tests\Communicator;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Communicator\RequestOptions;

class RequestOptionsTest extends TestCase
{
    // --- デフォルト値 -----------------------------------------------------

    public function test_引数なしのデフォルト値(): void
    {
        $options = new RequestOptions();

        $this->assertSame(0.0, $options->getTimeout());
        $this->assertSame(0.0, $options->getConnectTimeout());
        $this->assertFalse($options->isForm());
        $this->assertFalse($options->isJson());
        $this->assertNull($options->getAuthorization());
    }

    public function test_空配列でもデフォルト値になる(): void
    {
        $options = new RequestOptions([]);

        $this->assertSame(0.0, $options->getTimeout());
        $this->assertNull($options->getAuthorization());
    }

    // --- タイムアウト -----------------------------------------------------

    public function test_タイムアウトをfloatにキャストする(): void
    {
        $options = new RequestOptions(['timeout' => 3, 'connect_timeout' => '1.5']);

        $this->assertSame(3.0, $options->getTimeout());
        $this->assertSame(1.5, $options->getConnectTimeout());
    }

    // --- ボディ形式 -------------------------------------------------------

    public function test_formフラグを立てられる(): void
    {
        $options = new RequestOptions(['form' => true]);

        $this->assertTrue($options->isForm());
        $this->assertFalse($options->isJson());
    }

    public function test_jsonフラグを立てられる(): void
    {
        $options = new RequestOptions(['json' => true]);

        $this->assertTrue($options->isJson());
        $this->assertFalse($options->isForm());
    }

    public function test_フラグはboolにキャストされる(): void
    {
        $this->assertTrue((new RequestOptions(['json' => 1]))->isJson());
        $this->assertFalse((new RequestOptions(['json' => 0]))->isJson());
    }

    // --- Authorization ----------------------------------------------------

    public function test_アクセストークンにBearerを付与する(): void
    {
        $options = new RequestOptions(['authorization' => 'my-token']);

        $this->assertSame('Bearer my-token', $options->getAuthorization());
    }

    public function test_すでにBearerが付いている場合は二重に付与しない(): void
    {
        $options = new RequestOptions(['authorization' => 'Bearer my-token']);

        $this->assertSame('Bearer my-token', $options->getAuthorization());
    }

    public function test_先頭以外にBearerを含む場合は付与される(): void
    {
        $options = new RequestOptions(['authorization' => 'token-Bearer ']);

        $this->assertSame('Bearer token-Bearer ', $options->getAuthorization());
    }
}
