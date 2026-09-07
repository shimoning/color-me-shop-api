<?php

namespace Shimoning\ColorMeShopApi\Tests\Services;

use Shimoning\ColorMeShopApi\Services\OAuth;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Constants\AuthScope;
use Shimoning\ColorMeShopApi\Entities\OAuth\Options;
use Shimoning\ColorMeShopApi\Entities\OAuth\AccessToken;
use Shimoning\ColorMeShopApi\Values\Scopes;
use Shimoning\ColorMeShopApi\Tests\Support\HttpMock;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class OAuthTest extends TestCase
{
    private function options(): Options
    {
        return new Options('my-client-id', 'my-client-secret', 'https://example.test/callback');
    }

    // --- getUrl (通信なし) --------------------------------------------------

    public function test_認可URLを組み立てる(): void
    {
        $url = (new OAuth($this->options()))
            ->getUrl(new Scopes([AuthScope::READ_SALES, AuthScope::WRITE_SALES]));

        $this->assertStringStartsWith('https://api.shop-pro.jp/oauth/authorize?', $url);

        \parse_str(\parse_url($url, \PHP_URL_QUERY), $query);
        $this->assertSame('my-client-id', $query['client_id']);
        $this->assertSame('https://example.test/callback', $query['redirect_uri']);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('read_sales write_sales', $query['scope']);
    }

    public function test_認可URLはRFC3986でエンコードする(): void
    {
        $url = (new OAuth($this->options()))->getUrl(new Scopes([AuthScope::READ_SALES, AuthScope::WRITE_SALES]));

        // RFC3986 ではスペースは "+" ではなく "%20"
        $this->assertStringContainsString('scope=read_sales%20write_sales', $url);
        $this->assertStringNotContainsString('+', $url);
    }

    public function test_認可URLはクライアントシークレットを含めない(): void
    {
        $url = (new OAuth($this->options()))->getUrl(new Scopes([AuthScope::READ_SALES]));

        $this->assertStringNotContainsString('my-client-secret', $url);
    }

    public function test_エンドポイントを差し替えられる(): void
    {
        $options = new Options('id', 'secret', 'https://example.test/cb', 'https://oauth.example.test');

        $url = (new OAuth($options))->getUrl(new Scopes([AuthScope::READ_SALES]));

        $this->assertStringStartsWith('https://oauth.example.test/authorize?', $url);
    }

    // --- exchangeCode2Token -----------------------------------------------

    public function test_認可コードをアクセストークンに交換する(): void
    {
        $mock = HttpMock::json(200, self::fixture('oauth_token.json'));

        $token = (new OAuth($this->options(), $mock->client()))->exchangeCode2Token('auth-code');

        $this->assertInstanceOf(AccessToken::class, $token);
        $this->assertSame('dummy-access-token', $token->getAccessToken());
        $this->assertSame('bearer', $token->getTokenType());
        $this->assertSame([AuthScope::READ_PRODUCTS, AuthScope::READ_SALES], $token->getScopes());
    }

    public function test_トークン交換はフォーム形式でPOSTする(): void
    {
        $mock = HttpMock::json(200, self::fixture('oauth_token.json'));

        (new OAuth($this->options(), $mock->client()))->exchangeCode2Token('auth-code');

        $this->assertSame('POST', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/oauth/token', $mock->uri());
        $this->assertStringContainsString(
            'application/x-www-form-urlencoded',
            $mock->header('Content-Type'),
        );
    }

    public function test_トークン交換のリクエストボディ(): void
    {
        $mock = HttpMock::json(200, self::fixture('oauth_token.json'));

        (new OAuth($this->options(), $mock->client()))->exchangeCode2Token('auth-code');

        \parse_str($mock->body(), $body);
        $this->assertSame([
            'client_id' => 'my-client-id',
            'client_secret' => 'my-client-secret',
            'redirect_uri' => 'https://example.test/callback',
            'grant_type' => 'authorization_code',
            'code' => 'auth-code',
        ], $body);
    }

    public function test_トークン交換はAuthorizationヘッダを付けない(): void
    {
        $mock = HttpMock::json(200, self::fixture('oauth_token.json'));

        (new OAuth($this->options(), $mock->client()))->exchangeCode2Token('auth-code');

        $this->assertNull($mock->header('Authorization'));
    }

    public function test_トークン交換のエラーレスポンス(): void
    {
        $mock = HttpMock::json(401, self::fixture('errors_401.json'));

        $errors = (new OAuth($this->options(), $mock->client()))->exchangeCode2Token('invalid-code');

        $this->assertInstanceOf(Errors::class, $errors);
        $this->assertSame('401010', $errors[0]->getCode());
    }
}
