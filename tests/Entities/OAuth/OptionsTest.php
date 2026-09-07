<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\OAuth;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\OAuth\Options;
use Shimoning\ColorMeShopApi\Constants\AuthRedirectUri;

class OptionsTest extends TestCase
{
    private function makeOptions(): Options
    {
        return new Options('my-client-id', 'my-secret', 'https://example.test/callback');
    }

    public function test_コンストラクタで渡した値を取得できる(): void
    {
        $options = $this->makeOptions();

        $this->assertSame('my-client-id', $options->getClientId());
        $this->assertSame('my-secret', $options->getClientSecret());
        $this->assertSame('https://example.test/callback', $options->getRedirectUri());
    }

    public function test_エンドポイントは省略すると既定値になる(): void
    {
        $this->assertSame('https://api.shop-pro.jp/oauth', $this->makeOptions()->getEndpointUri());
    }

    public function test_エンドポイントを差し替えられる(): void
    {
        $options = new Options('id', 'secret', 'https://example.test/cb', 'https://oauth.example.test');

        $this->assertSame('https://oauth.example.test', $options->getEndpointUri());
    }

    public function test_リダイレクトURIにenumを渡すと文字列として取得できる(): void
    {
        $options = new Options('id', 'secret', AuthRedirectUri::NO_REDIRECT);

        $this->assertSame('urn:ietf:wg:oauth:2.0:oob', $options->getRedirectUri());
    }

    // --- setRedirectUri ----------------------------------------------------

    public function test_リダイレクトURIを文字列で設定できる(): void
    {
        $options = $this->makeOptions();

        $this->assertSame('https://example.test/new', $options->setRedirectUri('https://example.test/new'));
        $this->assertSame('https://example.test/new', $options->getRedirectUri());
    }

    /**
     * 引数は AuthRedirectUri|string を受け付けるが、戻り値の型は string のため
     * enum を渡すと TypeError になっていた。
     */
    public function test_リダイレクトURIをenumで設定できる(): void
    {
        $options = $this->makeOptions();

        $this->assertSame('urn:ietf:wg:oauth:2.0:oob', $options->setRedirectUri(AuthRedirectUri::NO_REDIRECT));
        $this->assertSame('urn:ietf:wg:oauth:2.0:oob', $options->getRedirectUri());
    }
}
