<?php

namespace Shimoning\ColorMeShopApi\Tests\Communicator;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\BufferStream;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use GuzzleHttp\Psr7\Response as Psr7Response;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;
use Shimoning\ColorMeShopApi\Communicator\Request;
use Shimoning\ColorMeShopApi\Communicator\RequestOptions;
use Shimoning\ColorMeShopApi\Communicator\Response;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
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
        $this->assertSame('application/json; charset=utf-8', $mock->header('Content-Type'));
    }

    // --- Content-Type -----------------------------------------------------

    public function test_jsonオプションのContentTypeにcharsetを付与する(): void
    {
        $mock = HttpMock::json(200, '{}');

        (new Request(new RequestOptions(['json' => true]), $mock->client()))
            ->post('https://api.shop-pro.jp/v1/sales/1/mails', ['a' => 1]);

        $this->assertSame('application/json; charset=utf-8', $mock->header('Content-Type'));
    }

    public function test_formオプションのContentTypeを付与する(): void
    {
        $mock = HttpMock::json(200, '{}');

        (new Request(new RequestOptions(['form' => true]), $mock->client()))
            ->post('https://api.shop-pro.jp/oauth/token', ['a' => 1]);

        $this->assertSame('application/x-www-form-urlencoded', $mock->header('Content-Type'));
    }

    /**
     * HTTP ヘッダ名は大小文字を区別しないため、呼び出し側がどの表記で指定しても
     * 既定値を追加せずその指定を尊重すること。
     * 追加してしまうと "a, b" のように値が2つ並んだ不正なヘッダになる。
     */
    #[DataProvider('contentTypeHeaderNameProvider')]
    public function test_呼び出し側のContentTypeを上書きしない(string $headerName): void
    {
        $mock = HttpMock::json(200, '{}');

        (new Request(new RequestOptions(['json' => true]), $mock->client()))
            ->post('https://api.shop-pro.jp/v1/sales/1/mails', ['a' => 1], [$headerName => 'application/vnd.api+json']);

        $this->assertSame('application/vnd.api+json', $mock->header('Content-Type'));
    }

    public static function contentTypeHeaderNameProvider(): array
    {
        return [
            '一般的な表記' => ['Content-Type'],
            'すべて小文字' => ['content-type'],
            'すべて大文字' => ['CONTENT-TYPE'],
            '不揃いな表記' => ['CoNtEnT-tYpE'],
        ];
    }

    #[DataProvider('contentTypeHeaderNameProvider')]
    public function test_呼び出し側がContentTypeを指定したら値は1つだけになる(string $headerName): void
    {
        $mock = HttpMock::json(200, '{}');

        (new Request(new RequestOptions(['json' => true]), $mock->client()))
            ->post('https://api.shop-pro.jp/v1/sales/1/mails', ['a' => 1], [$headerName => 'application/vnd.api+json']);

        $this->assertCount(1, $mock->request()->getHeader('Content-Type'));
    }

    public function test_GETにはContentTypeを付けない(): void
    {
        $mock = HttpMock::json(200, '{}');

        (new Request(new RequestOptions(['json' => true]), $mock->client()))
            ->get('https://api.shop-pro.jp/v1/shop');

        $this->assertNull($mock->header('Content-Type'));
    }

    public function test_PUTでJSONボディを送信する(): void
    {
        $mock = HttpMock::json(200, '{}');

        (new Request(new RequestOptions(['json' => true]), $mock->client()))
            ->put('https://api.shop-pro.jp/v1/sales/1', ['sale' => ['paid' => true]]);

        $this->assertSame('PUT', $mock->request()->getMethod());
        $this->assertSame(['sale' => ['paid' => true]], $mock->jsonBody());
    }

    // --- DELETE -----------------------------------------------------------

    public function test_DELETEはボディなしでヘッダを付与して送信する(): void
    {
        $mock = new HttpMock([new Psr7Response(204, ['X-Request-Id' => 'request-id'])]);

        $response = (new Request(
            new RequestOptions(['authorization' => 'my-token', 'json' => true]),
            $mock->client(),
        ))->delete(
            'https://api.shop-pro.jp/v1/products/1/options/2',
            ['X-Custom' => 'value'],
        );

        $this->assertSame('DELETE', $mock->request()->getMethod());
        $this->assertSame('', $mock->body());
        $this->assertNull($mock->header('Content-Type'));
        $this->assertSame('Bearer my-token', $mock->header('Authorization'));
        $this->assertSame('value', $mock->header('X-Custom'));
        $this->assertSame(204, $response->getStatus());
    }

    public function test_DELETEのエラー応答も例外にせずResponseで返す(): void
    {
        $mock = HttpMock::json(422, '{"errors":[]}');

        $response = (new Request(new RequestOptions(), $mock->client()))
            ->delete('https://api.shop-pro.jp/v1/products/1/options/2');

        $this->assertSame(422, $response->getStatus());
        $this->assertFalse($response->isSuccess());
    }

    // --- multipart --------------------------------------------------------

    public function test_multipartはフィールドとファイルパスをJSON化せず送信する(): void
    {
        $path = \tempnam(\sys_get_temp_dir(), 'colorme-multipart-');
        $this->assertNotFalse($path);
        \file_put_contents($path, 'image-content');
        $mock = HttpMock::json(201, '{}');

        try {
            $response = (new Request(
                new RequestOptions(['authorization' => 'my-token', 'json' => true]),
                $mock->client(),
            ))->postMultipart(
                'https://api.shop-pro.jp/v1/products/1/images',
                ['position' => 0],
                ['image' => $path],
                ['X-Custom' => 'value'],
            );
        } finally {
            \unlink($path);
        }

        $body = $mock->body();
        $this->assertSame('POST', $mock->request()->getMethod());
        $this->assertStringStartsWith('multipart/form-data; boundary=', $mock->header('Content-Type'));
        $this->assertStringContainsString('name="position"', $body);
        $this->assertStringContainsString("\r\n\r\n0\r\n", $body);
        $this->assertStringContainsString('name="image"', $body);
        $this->assertStringContainsString('filename="' . \basename($path) . '"', $body);
        $this->assertStringContainsString('image-content', $body);
        $this->assertStringNotContainsString('{"position":0}', $body);
        $this->assertSame('Bearer my-token', $mock->header('Authorization'));
        $this->assertSame('value', $mock->header('X-Custom'));
        $this->assertArrayHasKey('multipart', $response->getRequestMeta()->getOptions());
        $this->assertArrayNotHasKey('json', $response->getRequestMeta()->getOptions());
        $this->assertSame([
            ['name' => 'position', 'contents' => '0'],
            ['name' => 'image', 'filename' => \basename($path), 'size' => 13],
        ], $response->getRequestMeta()->getOptions()['multipart']);
    }

    public function test_multipartは読み取り可能なストリームを送信できる(): void
    {
        $stream = \fopen('php://temp', 'w+b');
        $this->assertIsResource($stream);
        \fwrite($stream, 'stream-content');
        \rewind($stream);
        $mock = HttpMock::json(201, '{}');

        try {
            (new Request(new RequestOptions(), $mock->client()))->postMultipart(
                'https://api.shop-pro.jp/v1/products/1/images',
                ['position' => 1],
                ['image' => $stream],
            );
            $body = $mock->body();
            $this->assertIsResource($stream);
        } finally {
            \fclose($stream);
        }

        $this->assertStringContainsString('stream-content', $body);
        $this->assertStringContainsString('filename="temp"', $body);
    }

    public function test_multipartはPSR7ストリームと明示ファイル名を送信できる(): void
    {
        $stream = Utils::streamFor('psr7-stream-content');
        $mock = HttpMock::json(201, '{}');

        $response = (new Request(new RequestOptions(), $mock->client()))->postMultipart(
            'https://api.shop-pro.jp/v1/products/1/images',
            [],
            ['image' => $stream],
            [],
            ['image' => 'product-image.png'],
        );

        $this->assertStringContainsString('name="image"; filename="product-image.png"', $mock->body());
        $this->assertStringContainsString('psr7-stream-content', $mock->body());
        $this->assertSame([
            'name' => 'image',
            'filename' => 'product-image.png',
            'size' => 19,
        ], $response->getRequestMeta()->getOptions()['multipart'][0]);
    }

    public function test_multipartはURIのないストリームにフィールド名のfilenameを補う(): void
    {
        $stream = new BufferStream();
        $stream->write('buffer-content');
        $mock = HttpMock::json(201, '{}');

        (new Request(new RequestOptions(), $mock->client()))->postMultipart(
            'https://api.shop-pro.jp/v1/products/1/images',
            [],
            ['image' => $stream],
        );

        $this->assertStringContainsString('name="image"; filename="image"', $mock->body());
    }

    public function test_multipartは内部で開いたファイルを送信後に閉じる(): void
    {
        $path = \tempnam(\sys_get_temp_dir(), 'colorme-owned-stream-');
        $this->assertNotFalse($path);
        \file_put_contents($path, 'owned-stream-content');
        $capturedStream = null;
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->willReturnCallback(static function (string $method, string $uri, array $options) use (&$capturedStream): Psr7Response {
                $capturedStream = $options['multipart'][0]['contents'];
                return new Psr7Response(201, [], '{}');
            });

        try {
            (new Request(new RequestOptions(), $client))->postMultipart(
                'https://api.shop-pro.jp/v1/products/1/images',
                [],
                ['image' => $path],
            );
        } finally {
            \unlink($path);
        }

        $this->assertFalse(\is_resource($capturedStream));
    }

    public function test_multipartはHTTP送信失敗時も内部で開いたファイルを閉じる(): void
    {
        $path = \tempnam(\sys_get_temp_dir(), 'colorme-failed-stream-');
        $this->assertNotFalse($path);
        \file_put_contents($path, 'failed-stream-content');
        $capturedStream = null;
        $client = $this->createMock(ClientInterface::class);
        $client->method('request')
            ->willReturnCallback(static function (string $method, string $uri, array $options) use (&$capturedStream): never {
                $capturedStream = $options['multipart'][0]['contents'];
                throw new \RuntimeException('HTTP 送信失敗');
            });

        try {
            (new Request(new RequestOptions(), $client))->postMultipart(
                'https://api.shop-pro.jp/v1/products/1/images',
                [],
                ['image' => $path],
            );
            $this->fail('HTTP 送信失敗が伝播しなかった');
        } catch (\RuntimeException $exception) {
            $this->assertSame('HTTP 送信失敗', $exception->getMessage());
        } finally {
            \unlink($path);
        }

        $this->assertFalse(\is_resource($capturedStream));
    }

    public function test_multipartは存在しないファイルパスをHTTP送信前に拒否する(): void
    {
        $path = \tempnam(\sys_get_temp_dir(), 'colorme-missing-');
        $this->assertNotFalse($path);
        \unlink($path);
        $mock = HttpMock::json(201, '{}');

        try {
            (new Request(new RequestOptions(), $mock->client()))->postMultipart(
                'https://api.shop-pro.jp/v1/products/1/images',
                [],
                ['image' => $path],
            );
            $this->fail('存在しないファイルパスが受理された');
        } catch (ParameterException $exception) {
            $this->assertStringContainsString('フィールド『image』', $exception->getMessage());
            $this->assertSame(0, $mock->countRequests());
        }
    }

    public function test_multipartは読み取り不可のストリームをHTTP送信前に拒否する(): void
    {
        $path = \tempnam(\sys_get_temp_dir(), 'colorme-unreadable-');
        $this->assertNotFalse($path);
        $stream = \fopen($path, 'wb');
        $this->assertIsResource($stream);
        $mock = HttpMock::json(201, '{}');

        try {
            (new Request(new RequestOptions(), $mock->client()))->postMultipart(
                'https://api.shop-pro.jp/v1/products/1/images',
                [],
                ['image' => $stream],
            );
            $this->fail('読み取り不可のストリームが受理された');
        } catch (ParameterException $exception) {
            $this->assertStringContainsString('フィールド『image』', $exception->getMessage());
            $this->assertSame(0, $mock->countRequests());
        } finally {
            \fclose($stream);
            \unlink($path);
        }
    }

    public function test_multipartは読み取り不可のPSR7ストリームをHTTP送信前に拒否する(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('isReadable')->willReturn(false);
        $mock = HttpMock::json(201, '{}');

        try {
            (new Request(new RequestOptions(), $mock->client()))->postMultipart(
                'https://api.shop-pro.jp/v1/products/1/images',
                [],
                ['image' => $stream],
            );
            $this->fail('読み取り不可の PSR-7 ストリームが受理された');
        } catch (ParameterException $exception) {
            $this->assertStringContainsString('フィールド『image』', $exception->getMessage());
            $this->assertSame(0, $mock->countRequests());
        }
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
