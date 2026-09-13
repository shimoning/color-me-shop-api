<?php

namespace Shimoning\ColorMeShopApi\Tests\Services;

use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Services\OAuth;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Constants\AuthScope;
use Shimoning\ColorMeShopApi\Entities\OAuth\Options;
use Shimoning\ColorMeShopApi\Entities\OAuth\AccessToken;
use Shimoning\ColorMeShopApi\Entities\OAuth\ErrorResponse;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;
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
        $this->assertSame(1700000000, $token->getCreatedAt());
    }

    public function test_部分的な成功応答は交換処理で構築でき欠損はgetter時に固有例外になる(): void
    {
        $mock = HttpMock::json(200, '{"scope":"read_products"}');

        $token = (new OAuth($this->options(), $mock->client()))->exchangeCode2Token('auth-code');

        $this->assertInstanceOf(AccessToken::class, $token);
        $this->assertSame([AuthScope::READ_PRODUCTS], $token->getScopes());
        $this->expectException(MissingFieldException::class);
        $this->expectExceptionMessage(
            AccessToken::class . ' の API フィールド『access_token』が欠損しています。',
        );

        $token->getAccessToken();
    }

    public function test_2xxの空ボディは元レスポンスを保持したErrorsを返す(): void
    {
        $mock = HttpMock::json(204, '');

        $errors = (new OAuth($this->options(), $mock->client()))->exchangeCode2Token('auth-code');

        $this->assertInstanceOf(Errors::class, $errors);
        $this->assertCount(0, $errors);
        $this->assertSame(204, $errors->getResponse()->getStatus());
        $this->assertSame('', $errors->getResponse()->getRawBody());
        $this->assertNull($errors->getResponse()->getParsedBody());
    }

    public function test_2xxの非配列JSONは元レスポンスを保持したErrorsを返す(): void
    {
        $body = '"unexpected"';
        $mock = HttpMock::json(200, $body);

        $errors = (new OAuth($this->options(), $mock->client()))->exchangeCode2Token('auth-code');

        $this->assertInstanceOf(Errors::class, $errors);
        $this->assertCount(0, $errors);
        $this->assertSame(200, $errors->getResponse()->getStatus());
        $this->assertSame($body, $errors->getResponse()->getRawBody());
        $this->assertNull($errors->getResponse()->getParsedBody());
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

    public function test_実APIで観測したOAuthエラーレスポンス(): void
    {
        $body = self::fixture('oauth_error_401.json');
        $mock = HttpMock::json(401, $body);

        $error = (new OAuth($this->options(), $mock->client()))->exchangeCode2Token('invalid-code');

        $this->assertInstanceOf(ErrorResponse::class, $error);
        $this->assertSame('invalid_client', $error->getError());
        $this->assertSame(
            'クライアント認証に失敗しました。クライアントIDが正しいかご確認ください。',
            $error->getErrorDescription(),
        );
        $this->assertNull($error->getErrorUri());
        $this->assertNull($error->getState());
        $this->assertSame(401, $error->getResponse()->getStatus());
        $this->assertSame($body, $error->getResponse()->getRawBody());
    }

    public function test_errorキーだけのOAuthエラーも専用クラスで返す(): void
    {
        $mock = HttpMock::json(400, '{"error":"invalid_request"}');

        $error = (new OAuth($this->options(), $mock->client()))->exchangeCode2Token('invalid-code');

        $this->assertInstanceOf(ErrorResponse::class, $error);
        $this->assertSame('invalid_request', $error->getError());
        $this->assertNull($error->getErrorDescription());
        $this->assertNull($error->getErrorUri());
        $this->assertNull($error->getState());
    }

    public function test_2xxでもerrorキーがあればOAuthエラーを優先する(): void
    {
        $mock = HttpMock::json(200, '{"error":"server_error"}');

        $error = (new OAuth($this->options(), $mock->client()))->exchangeCode2Token('invalid-code');

        $this->assertInstanceOf(ErrorResponse::class, $error);
        $this->assertSame('server_error', $error->getError());
        $this->assertSame(200, $error->getResponse()->getStatus());
    }

    public function test_errorとerrorsが併存する場合はOAuthエラーを優先する(): void
    {
        $body = '{"error":"invalid_request","errors":[{"code":"401010","message":"unauthorized","status":401}]}';
        $mock = HttpMock::json(400, $body);

        $error = (new OAuth($this->options(), $mock->client()))->exchangeCode2Token('invalid-code');

        $this->assertInstanceOf(ErrorResponse::class, $error);
        $this->assertSame('invalid_request', $error->getError());
        $this->assertSame(400, $error->getResponse()->getStatus());
        $this->assertSame($body, $error->getResponse()->getRawBody());
    }

    public function test_errorが不正型なら元レスポンスを保持するErrorsへフォールバックする(): void
    {
        $body = '{"error":null,"error_description":"upstream error"}';
        $mock = HttpMock::json(400, $body);

        $errors = (new OAuth($this->options(), $mock->client()))->exchangeCode2Token('invalid-code');

        $this->assertInstanceOf(Errors::class, $errors);
        $this->assertCount(0, $errors);
        $this->assertSame(400, $errors->getResponse()->getStatus());
        $this->assertSame($body, $errors->getResponse()->getRawBody());
    }

    #[DataProvider('invalidOptionalFieldProvider')]
    public function test_OAuthエラーの任意フィールドが不正型なら元レスポンスを保持するErrorsへフォールバックする(
        string $field,
        mixed $invalidValue,
    ): void {
        $body = \json_encode([
            'error' => 'invalid_request',
            $field => $invalidValue,
        ], \JSON_THROW_ON_ERROR);
        $mock = HttpMock::json(400, $body);

        $errors = (new OAuth($this->options(), $mock->client()))->exchangeCode2Token('invalid-code');

        $this->assertInstanceOf(Errors::class, $errors);
        $this->assertCount(0, $errors);
        $this->assertSame(400, $errors->getResponse()->getStatus());
        $this->assertSame($body, $errors->getResponse()->getRawBody());
    }

    /**
     * @return array<string, array{string, mixed}>
     */
    public static function invalidOptionalFieldProvider(): array
    {
        return [
            'error_description' => ['error_description', []],
            'error_uri' => ['error_uri', false],
            'state' => ['state', 123],
        ];
    }

    public function test_errorキーのない不完全な応答はErrorsとして元レスポンスを保持する(): void
    {
        $body = '{"error_description":"説明だけ"}';
        $mock = HttpMock::json(400, $body);

        $errors = (new OAuth($this->options(), $mock->client()))->exchangeCode2Token('invalid-code');

        $this->assertInstanceOf(Errors::class, $errors);
        $this->assertCount(0, $errors);
        $this->assertSame($body, $errors->getResponse()->getRawBody());
    }

    public function test_空objectのエラー応答はErrorsとして元レスポンスを保持する(): void
    {
        $mock = HttpMock::json(400, '{}');

        $errors = (new OAuth($this->options(), $mock->client()))->exchangeCode2Token('invalid-code');

        $this->assertInstanceOf(Errors::class, $errors);
        $this->assertCount(0, $errors);
        $this->assertSame('{}', $errors->getResponse()->getRawBody());
    }

    public function test_OAuthエラーの追加プロパティを生データに保持する(): void
    {
        $mock = HttpMock::json(400, '{"error":"temporarily_unavailable","request_id":"req-1"}');

        $error = (new OAuth($this->options(), $mock->client()))->exchangeCode2Token('invalid-code');

        $this->assertInstanceOf(ErrorResponse::class, $error);
        $this->assertSame('req-1', $error->getRaw()['request_id']);
        $this->assertArrayNotHasKey('request_id', $error->toArray());
    }

    public function test_ColorMe形式のエラーレスポンスは従来どおりErrorsを返す(): void
    {
        $mock = HttpMock::json(401, self::fixture('errors_401.json'));

        $errors = (new OAuth($this->options(), $mock->client()))->exchangeCode2Token('invalid-code');

        $this->assertInstanceOf(Errors::class, $errors);
        $this->assertSame('401010', $errors[0]->getCode());
    }
}
