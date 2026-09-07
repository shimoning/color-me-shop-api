<?php

namespace Shimoning\ColorMeShopApi\Tests\Communicator;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Shimoning\ColorMeShopApi\Communicator\Response;
use Shimoning\ColorMeShopApi\Communicator\RequestMeta;

class ResponseTest extends TestCase
{
    private function makeResponse(
        int $status = 200,
        string $body = '{}',
        array $headers = [],
        ?RequestMeta $meta = null,
    ): Response {
        return new Response(
            new Psr7Response($status, $headers, $body),
            $meta ?? new RequestMeta('GET', 'https://api.shop-pro.jp/v1/shop', []),
        );
    }

    // --- ステータス -------------------------------------------------------

    public function test_HTTPステータスを保持する(): void
    {
        $this->assertSame(201, $this->makeResponse(201)->getStatus());
    }

    #[DataProvider('successStatusProvider')]
    public function test_2xxは成功とみなす(int $status): void
    {
        $this->assertTrue($this->makeResponse($status)->isSuccess());
    }

    public static function successStatusProvider(): array
    {
        return [
            '200 OK' => [200],
            '201 Created' => [201],
            '204 No Content' => [204],
            '2xx の上限' => [299],
        ];
    }

    #[DataProvider('failureStatusProvider')]
    public function test_2xx以外は失敗とみなす(int $status): void
    {
        $this->assertFalse($this->makeResponse($status)->isSuccess());
    }

    public static function failureStatusProvider(): array
    {
        return [
            '2xx の下限の1つ下' => [199],
            '300 Multiple Choices' => [300],
            '301 Moved Permanently' => [301],
            '400 Bad Request' => [400],
            '401 Unauthorized' => [401],
            '404 Not Found' => [404],
            '422 Unprocessable Entity' => [422],
            '500 Internal Server Error' => [500],
        ];
    }

    // --- ボディ -----------------------------------------------------------

    public function test_JSONボディを連想配列にパースする(): void
    {
        $response = $this->makeResponse(200, '{"shop":{"id":"my-shop","name":"テスト店"}}');

        $this->assertSame(
            ['shop' => ['id' => 'my-shop', 'name' => 'テスト店']],
            $response->getParsedBody(),
        );
    }

    public function test_生のボディ文字列をそのまま保持する(): void
    {
        $body = '{"shop":{"id":"my-shop"}}';

        $this->assertSame($body, $this->makeResponse(200, $body)->getRawBody());
    }

    public function test_不正なJSONはパース結果がnullになる(): void
    {
        $response = $this->makeResponse(200, '<html>Internal Server Error</html>');

        $this->assertNull($response->getParsedBody());
        $this->assertSame('<html>Internal Server Error</html>', $response->getRawBody());
    }

    public function test_空のボディはパース結果がnullになる(): void
    {
        $response = $this->makeResponse(204, '');

        $this->assertNull($response->getParsedBody());
        $this->assertSame('', $response->getRawBody());
    }

    /**
     * json_decode は連想配列以外も返しうるが、パース処理の戻り値の型宣言が ?array のため、
     * スカラーの JSON を返すレスポンスはインスタンス生成の時点で TypeError になる (仕様化テスト)。
     */
    public function test_配列にならないJSONは生成時にTypeErrorになる(): void
    {
        $this->expectException(\TypeError::class);

        $this->makeResponse(200, '"just a string"');
    }

    // --- ヘッダ -----------------------------------------------------------

    public function test_レスポンスヘッダを保持する(): void
    {
        $response = $this->makeResponse(200, '{}', ['X-RateLimit-Remaining' => '99']);

        $this->assertSame(['99'], $response->getRawHeader()['X-RateLimit-Remaining']);
    }

    public function test_ヘッダがなくても空配列を返す(): void
    {
        $this->assertSame([], $this->makeResponse(200, '{}', [])->getRawHeader());
    }

    // --- RequestMeta ------------------------------------------------------

    public function test_リクエストのメタ情報を保持する(): void
    {
        $meta = new RequestMeta('PUT', 'https://api.shop-pro.jp/v1/sales/1', ['json' => ['sale' => []]]);
        $response = $this->makeResponse(200, '{}', [], $meta);

        $this->assertSame($meta, $response->getRequestMeta());
        $this->assertSame('PUT', $response->getRequestMeta()->getMethod());
    }
}
