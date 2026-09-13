<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\OAuth;

use GuzzleHttp\Psr7\Response as Psr7Response;
use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Communicator\RequestMeta;
use Shimoning\ColorMeShopApi\Communicator\Response;
use Shimoning\ColorMeShopApi\Entities\OAuth\ErrorResponse;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class ErrorResponseTest extends TestCase
{
    /**
     * @param array<string, mixed> $data
     */
    private function makeErrorResponse(array $data, int $status = 400): ErrorResponse
    {
        $body = \json_encode($data, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE);
        $response = new Response(
            new Psr7Response($status, ['Content-Type' => 'application/json'], $body),
            new RequestMeta('POST', 'https://api.shop-pro.jp/oauth/token', []),
        );

        return new ErrorResponse($data, $response);
    }

    public function test_実APIで観測したOAuthエラーと元レスポンスを保持する(): void
    {
        $body = self::fixture('oauth_error_401.json');
        $data = \json_decode($body, true, 512, \JSON_THROW_ON_ERROR);
        $response = new Response(
            new Psr7Response(401, ['Content-Type' => 'application/json'], $body),
            new RequestMeta('POST', 'https://api.shop-pro.jp/oauth/token', []),
        );

        $error = new ErrorResponse($data, $response);

        $this->assertSame('invalid_client', $error->getError());
        $this->assertSame(
            'クライアント認証に失敗しました。クライアントIDが正しいかご確認ください。',
            $error->getErrorDescription(),
        );
        $this->assertNull($error->getErrorUri());
        $this->assertNull($error->getState());
        $this->assertSame($response, $error->getResponse());
        $this->assertSame(401, $error->getResponse()->getStatus());
        $this->assertSame($body, $error->getResponse()->getRawBody());
        $this->assertSame($data, $error->getRaw());
        $this->assertSame([
            'error' => 'invalid_client',
            'error_description' => 'クライアント認証に失敗しました。クライアントIDが正しいかご確認ください。',
            'error_uri' => null,
            'state' => null,
        ], $error->toArray());
    }

    /**
     * @return array<string, array{array<string, mixed>, string|null, string|null, string|null, string|null}>
     */
    public static function oauthErrorProvider(): array
    {
        return [
            'error のみ' => [
                ['error' => 'invalid_request'],
                'invalid_request',
                null,
                null,
                null,
            ],
            'error_description のみ' => [
                ['error_description' => '説明だけ'],
                null,
                '説明だけ',
                null,
                null,
            ],
            'error と error_description' => [
                ['error' => 'invalid_grant', 'error_description' => '認可コードが不正です。'],
                'invalid_grant',
                '認可コードが不正です。',
                null,
                null,
            ],
            'RFC の全フィールドと追加プロパティ' => [
                [
                    'error' => 'access_denied',
                    'error_description' => '認可されませんでした。',
                    'error_uri' => 'https://example.test/oauth/errors/access_denied',
                    'state' => 'opaque-state',
                    'extra' => 'kept-only-in-raw',
                ],
                'access_denied',
                '認可されませんでした。',
                'https://example.test/oauth/errors/access_denied',
                'opaque-state',
            ],
            '空 object' => [
                [],
                null,
                null,
                null,
                null,
            ],
        ];
    }

    /**
     * RFC 6749 の error は必須なので、欠損時だけ MissingFieldException とする。
     * optional getter と元レスポンスの getter から想定外の例外が出ないこともまとめて固定する。
     *
     * @param array<string, mixed> $data
     */
    #[DataProvider('oauthErrorProvider')]
    public function test_各フィールドの有無と全getterの契約(
        array $data,
        ?string $expectedError,
        ?string $expectedDescription,
        ?string $expectedUri,
        ?string $expectedState,
    ): void {
        $error = $this->makeErrorResponse($data);

        if ($expectedError === null) {
            try {
                $error->getError();
                $this->fail('error が欠損しているのに MissingFieldException が投げられなかった');
            } catch (MissingFieldException $exception) {
                $this->assertSame(
                    ErrorResponse::class . ' の API フィールド『error』が欠損しています。',
                    $exception->getMessage(),
                );
            }
        } else {
            $this->assertSame($expectedError, $error->getError());
        }

        $this->assertSame($expectedDescription, $error->getErrorDescription());
        $this->assertSame($expectedUri, $error->getErrorUri());
        $this->assertSame($expectedState, $error->getState());
        $this->assertSame(400, $error->getResponse()->getStatus());
        $this->assertSame($data, $error->getRaw());
    }

    public function test_追加プロパティは生データだけに保持する(): void
    {
        $error = $this->makeErrorResponse([
            'error' => 'server_error',
            'extra' => ['request_id' => 'req-1'],
        ]);

        $this->assertSame(['request_id' => 'req-1'], $error->getRaw()['extra']);
        $this->assertArrayNotHasKey('extra', $error->toArray());
    }

    public function test_errorの不正型はInvalidFieldExceptionになる(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(
            ErrorResponse::class . ' の API フィールド『error』が不正です。string を期待しましたが null でした。',
        );

        $this->makeErrorResponse(['error' => null]);
    }
}
