<?php

namespace Shimoning\ColorMeShopApi\Tests\Communicator;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Shimoning\ColorMeShopApi\Communicator\Request;
use Shimoning\ColorMeShopApi\Communicator\RequestOptions;
use Shimoning\ColorMeShopApi\Communicator\Response;
use Shimoning\ColorMeShopApi\Tests\Support\HttpMock;

class RequestTest extends TestCase
{
    // --- コンストラクタ ------------------------------------------------------

    public function test_引数を省略するとデフォルトのオプションとクライアントを使う(): void
    {
        $this->assertInstanceOf(Request::class, new Request());
    }

    /**
     * 引数の型宣言が nullable である以上、null を明示的に渡してもデフォルトに
     * フォールバックすること。options と client で扱いを揃えている。
     */
    public function test_optionsにnullを渡してもデフォルトにフォールバックする(): void
    {
        $mock = HttpMock::json(200, '{}');

        (new Request(null, $mock->client()))->get('https://api.shop-pro.jp/v1/shop');

        $this->assertSame('Shimoning ColorMeShopApi Client', $mock->header('User-Agent'));
        $this->assertNull($mock->header('Authorization'));
    }

    public function test_clientにnullを渡してもデフォルトにフォールバックする(): void
    {
        $this->assertInstanceOf(Request::class, new Request(new RequestOptions(), null));
    }

    public function test_両方にnullを渡しても生成できる(): void
    {
        $this->assertInstanceOf(Request::class, new Request(null, null));
    }

    // --- GET --------------------------------------------------------------

    public function test_GETリクエストを送信する(): void
    {
        $mock = HttpMock::json(200, '{"ok":true}');

        $response = (new Request(new RequestOptions(), $mock->client()))
            ->get('https://api.shop-pro.jp/v1/shop');

        $this->assertSame(1, $mock->countRequests());
        $this->assertSame('GET', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/shop', $mock->uri());
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(['ok' => true], $response->getParsedBody());
    }

    public function test_GETのデータはクエリ文字列になる(): void
    {
        $mock = HttpMock::json(200, '{}');

        (new Request(new RequestOptions(), $mock->client()))
            ->get('https://api.shop-pro.jp/v1/sales', ['limit' => 50, 'offset' => 100]);

        $this->assertSame(['limit' => '50', 'offset' => '100'], $mock->query());
    }

    public function test_GETのデータが空ならクエリ文字列を付けない(): void
    {
        $mock = HttpMock::json(200, '{}');

        (new Request(new RequestOptions(), $mock->client()))
            ->get('https://api.shop-pro.jp/v1/shop', []);

        $this->assertSame('https://api.shop-pro.jp/v1/shop', $mock->uri());
    }

    public function test_GETの配列パラメータはブラケット付きで展開される(): void
    {
        $mock = HttpMock::json(200, '{}');

        (new Request(new RequestOptions(), $mock->client()))
            ->get('https://api.shop-pro.jp/v1/sales', ['ids' => [1, 2]]);

        $this->assertSame(['ids' => ['1', '2']], $mock->query());
    }

    // --- POST / PUT -------------------------------------------------------

    public function test_POSTでJSONボディを送信する(): void
    {
        $mock = HttpMock::json(200, '{}');

        (new Request(new RequestOptions(['json' => true]), $mock->client()))
            ->post('https://api.shop-pro.jp/v1/sales/1/mails', ['mail' => ['type' => 'accepted']]);

        $this->assertSame('POST', $mock->request()->getMethod());
        $this->assertSame(['mail' => ['type' => 'accepted']], $mock->jsonBody());
        $this->assertStringContainsString('application/json', $mock->header('Content-Type'));
    }

    public function test_PUTでJSONボディを送信する(): void
    {
        $mock = HttpMock::json(200, '{}');

        (new Request(new RequestOptions(['json' => true]), $mock->client()))
            ->put('https://api.shop-pro.jp/v1/sales/1', ['sale' => ['paid' => true]]);

        $this->assertSame('PUT', $mock->request()->getMethod());
        $this->assertSame(['sale' => ['paid' => true]], $mock->jsonBody());
    }

    public function test_formオプションならフォーム形式で送信する(): void
    {
        $mock = HttpMock::json(200, '{}');

        (new Request(new RequestOptions(['form' => true]), $mock->client()))
            ->post('https://api.shop-pro.jp/oauth/token', ['grant_type' => 'authorization_code', 'code' => 'abc']);

        $this->assertSame('grant_type=authorization_code&code=abc', $mock->body());
        $this->assertStringContainsString(
            'application/x-www-form-urlencoded',
            $mock->header('Content-Type'),
        );
    }

    public function test_ボディが空ならペイロードを送らない(): void
    {
        $mock = HttpMock::json(200, '{}');

        (new Request(new RequestOptions(['json' => true]), $mock->client()))
            ->post('https://api.shop-pro.jp/v1/sales/1/mails', []);

        $this->assertSame('', $mock->body());
    }

    // --- ヘッダ -----------------------------------------------------------

    public function test_AuthorizationヘッダにBearerトークンを付与する(): void
    {
        $mock = HttpMock::json(200, '{}');

        (new Request(new RequestOptions(['authorization' => 'my-token']), $mock->client()))
            ->get('https://api.shop-pro.jp/v1/shop');

        $this->assertSame('Bearer my-token', $mock->header('Authorization'));
    }

    public function test_アクセストークンがなければAuthorizationヘッダを付けない(): void
    {
        $mock = HttpMock::json(200, '{}');

        (new Request(new RequestOptions(), $mock->client()))
            ->get('https://api.shop-pro.jp/v1/shop');

        $this->assertNull($mock->header('Authorization'));
    }

    public function test_UserAgentを付与する(): void
    {
        $mock = HttpMock::json(200, '{}');

        (new Request(new RequestOptions(), $mock->client()))
            ->get('https://api.shop-pro.jp/v1/shop');

        $this->assertSame('Shimoning ColorMeShopApi Client', $mock->header('User-Agent'));
    }

    public function test_呼び出し側のヘッダを追加できる(): void
    {
        $mock = HttpMock::json(200, '{}');

        (new Request(new RequestOptions(), $mock->client()))
            ->get('https://api.shop-pro.jp/v1/shop', [], ['X-Custom' => 'value']);

        $this->assertSame('value', $mock->header('X-Custom'));
    }

    // --- テストヘルパーの契約 ------------------------------------------------

    /**
     * jsonBody() は JSON であることを前提としたヘルパーなので、
     * パースに失敗したら黙って null を返さずに即座に失敗すること。
     */
    public function test_jsonBodyはJSONでないボディで例外を投げる(): void
    {
        $mock = HttpMock::json(200, '{}');

        (new Request(new RequestOptions(['form' => true]), $mock->client()))
            ->post('https://api.shop-pro.jp/oauth/token', ['code' => 'abc']);

        $this->assertSame('code=abc', $mock->body());

        $this->expectException(\JsonException::class);
        $mock->jsonBody();
    }

    // --- 仕様化テスト (既知の不具合) ----------------------------------------

    public function test_タイムアウトはGuzzleのオプションとして渡される(): void
    {
        $mock = HttpMock::json(200, '{}');

        (new Request(new RequestOptions(['timeout' => 5, 'connect_timeout' => 2]), $mock->client()))
            ->get('https://api.shop-pro.jp/v1/shop');

        $options = $mock->options();
        $this->assertSame(5.0, $options['timeout']);
        $this->assertSame(2.0, $options['connect_timeout']);
    }

    public function test_タイムアウトはHTTPヘッダとして送信しない(): void
    {
        $mock = HttpMock::json(200, '{}');

        (new Request(new RequestOptions(['timeout' => 5, 'connect_timeout' => 2]), $mock->client()))
            ->get('https://api.shop-pro.jp/v1/shop');

        $this->assertNull($mock->header('timeout'));
        $this->assertNull($mock->header('connect_timeout'));
    }

    /**
     * 既定値の 0 は「未設定」を意味するため、Guzzle のオプションに含めない。
     * クライアント側で設定されたタイムアウトを上書きしてしまわないようにする。
     */
    public function test_タイムアウトが未設定ならオプションに含めない(): void
    {
        $mock = HttpMock::json(200, '{}');

        (new Request(new RequestOptions(), $mock->client()))
            ->get('https://api.shop-pro.jp/v1/shop');

        $options = $mock->options();
        $this->assertArrayNotHasKey('timeout', $options);
        $this->assertArrayNotHasKey('connect_timeout', $options);
    }

    /**
     * Content-Type を組み立てる分岐は、$options['headers'] を構築した「後」に
     * $headers を書き換えているため送信内容に反映されない (デッドコード)。
     * 実際の Content-Type は Guzzle が json / form_params オプションから
     * 自動付与しており、意図された "charset=utf-8" は付かない。
     */
    public function test_JSONのContentTypeにcharsetが付かない(): void
    {
        $mock = HttpMock::json(200, '{}');

        (new Request(new RequestOptions(['json' => true]), $mock->client()))
            ->post('https://api.shop-pro.jp/v1/sales/1/mails', ['a' => 1]);

        $this->assertSame('application/json', $mock->header('Content-Type'));
        $this->assertStringNotContainsString('charset', $mock->header('Content-Type'));
    }

    // --- エラーレスポンス ---------------------------------------------------

    public function test_4xxでも例外を投げずResponseを返す(): void
    {
        $mock = HttpMock::json(401, '{"errors":[{"code":"401010","message":"NG","status":401}]}');

        $response = (new Request(new RequestOptions(), $mock->client()))
            ->get('https://api.shop-pro.jp/v1/shop');

        $this->assertSame(401, $response->getStatus());
        $this->assertFalse($response->isSuccess());
    }

    public function test_5xxでも例外を投げずResponseを返す(): void
    {
        $mock = HttpMock::json(500, '{}');

        $response = (new Request(new RequestOptions(), $mock->client()))
            ->get('https://api.shop-pro.jp/v1/shop');

        $this->assertSame(500, $response->getStatus());
        $this->assertFalse($response->isSuccess());
    }

    // --- RequestMeta ------------------------------------------------------

    public function test_レスポンスにリクエストのメタ情報を含める(): void
    {
        $mock = HttpMock::json(200, '{}');

        $response = (new Request(new RequestOptions(['json' => true]), $mock->client()))
            ->put('https://api.shop-pro.jp/v1/sales/1', ['sale' => []]);

        $meta = $response->getRequestMeta();
        $this->assertSame('PUT', $meta->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/sales/1', $meta->getUri());
        $this->assertArrayHasKey('headers', $meta->getOptions());
    }

    public function test_GETのメタ情報のURIはクエリ文字列を含む(): void
    {
        $mock = HttpMock::json(200, '{}');

        $response = (new Request(new RequestOptions(), $mock->client()))
            ->get('https://api.shop-pro.jp/v1/sales', ['limit' => 10]);

        $this->assertSame('https://api.shop-pro.jp/v1/sales?limit=10', $response->getRequestMeta()->getUri());
    }
}
