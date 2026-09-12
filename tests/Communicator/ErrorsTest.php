<?php

namespace Shimoning\ColorMeShopApi\Tests\Communicator;

use GuzzleHttp\Psr7\Response as Psr7Response;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Communicator\Response;
use Shimoning\ColorMeShopApi\Communicator\RequestMeta;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Error;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class ErrorsTest extends TestCase
{
    private function makeResponse(int $status, string $body): Response
    {
        return new Response(
            new Psr7Response($status, [], $body),
            new RequestMeta('GET', 'https://api.shop-pro.jp/v1/sales', []),
        );
    }

    // --- build ------------------------------------------------------------

    public function test_エラーレスポンスからErrorエンティティのコレクションを組み立てる(): void
    {
        $errors = Errors::build($this->makeResponse(401, self::fixture('errors_401.json')));

        $this->assertInstanceOf(Errors::class, $errors);
        $this->assertSame(1, $errors->count());
        $this->assertContainsOnlyInstancesOf(Error::class, $errors->all());

        $error = $errors[0];
        $this->assertSame('401010', $error->getCode());
        $this->assertSame(401, $error->getStatus());
        $this->assertStringContainsString('有効なアクセストークンが見つからない', $error->getMessage());
    }

    public function test_複数のエラーをすべて変換する(): void
    {
        $errors = Errors::build($this->makeResponse(422, self::fixture('errors_422.json')));

        $this->assertSame(2, $errors->count());
        $this->assertSame(['sale.id', 'sale.paid'], \array_map(fn($e) => $e->getField(), $errors->all()));
    }

    /**
     * API のエラーレスポンスは field を含まないことがある (401 / 404 など)。
     * その場合でも getField() は例外を投げず null を返すこと。
     */
    public function test_fieldがないエラーのgetFieldはnullを返す(): void
    {
        $errors = Errors::build($this->makeResponse(401, self::fixture('errors_401.json')));

        $this->assertNull($errors[0]->getField());
    }

    public function test_fieldがあれば取得できる(): void
    {
        $errors = Errors::build($this->makeResponse(422, self::fixture('errors_422.json')));

        $this->assertSame('sale.id', $errors[0]->getField());
    }

    public function test_errorsキーがなければ空のコレクションになる(): void
    {
        $errors = Errors::build($this->makeResponse(500, '{"message":"Internal Server Error"}'));

        $this->assertSame(0, $errors->count());
        $this->assertSame([], $errors->all());
    }

    public function test_パースできないボディでも空のコレクションになる(): void
    {
        $errors = Errors::build($this->makeResponse(502, '<html>Bad Gateway</html>'));

        $this->assertSame(0, $errors->count());
    }

    public function test_errorsが空配列でも空のコレクションになる(): void
    {
        $errors = Errors::build($this->makeResponse(400, '{"errors":[]}'));

        $this->assertSame(0, $errors->count());
    }

    public function test_部分的なエラー要素でも利用可能な情報を保持して組み立てられる(): void
    {
        $errors = Errors::build($this->makeResponse(422, '{"errors":[{"message":"invalid"}]}'));

        $this->assertSame(1, $errors->count());
        $this->assertSame('invalid', $errors[0]->getMessage());
        $this->assertNull($errors[0]->getField());
    }

    public function test_部分的なエラー要素の欠損フィールドはgetter時に固有例外になる(): void
    {
        $errors = Errors::build($this->makeResponse(422, '{"errors":[{"message":"invalid"}]}'));

        $this->expectException(MissingFieldException::class);
        $this->expectExceptionMessage(Error::class . ' の API フィールド『code』が欠損しています。');

        $errors[0]->getCode();
    }

    public function test_errorsの文字列要素は無視して元レスポンスを保持する(): void
    {
        $body = '{"errors":["upstream error"]}';
        $response = $this->makeResponse(502, $body);

        $errors = Errors::build($response);

        $this->assertSame([], $errors->all());
        $this->assertSame($response, $errors->getResponse());
        $this->assertSame(502, $errors->getResponse()->getStatus());
        $this->assertSame($body, $errors->getResponse()->getRawBody());
    }

    public function test_errorsのnull要素は無視して元レスポンスを保持する(): void
    {
        $body = '{"errors":[null]}';
        $response = $this->makeResponse(503, $body);

        $errors = Errors::build($response);

        $this->assertSame([], $errors->all());
        $this->assertSame($response, $errors->getResponse());
        $this->assertSame(503, $errors->getResponse()->getStatus());
        $this->assertSame($body, $errors->getResponse()->getRawBody());
    }

    public function test_errorsがスカラーなら空として扱って元レスポンスを保持する(): void
    {
        $body = '{"errors":"upstream error"}';
        $response = $this->makeResponse(500, $body);

        $errors = Errors::build($response);

        $this->assertSame([], $errors->all());
        $this->assertSame($response, $errors->getResponse());
        $this->assertSame(500, $errors->getResponse()->getStatus());
        $this->assertSame($body, $errors->getResponse()->getRawBody());
    }

    // --- Response の保持 ---------------------------------------------------

    public function test_元のレスポンスを保持している(): void
    {
        $response = $this->makeResponse(401, self::fixture('errors_401.json'));
        $errors = Errors::build($response);

        $this->assertSame($response, $errors->getResponse());
        $this->assertSame(401, $errors->getResponse()->getStatus());
        $this->assertFalse($errors->getResponse()->isSuccess());
    }

    // --- Collection としての振る舞い ---------------------------------------

    public function test_Collectionを継承しforeachで走査できる(): void
    {
        $errors = Errors::build($this->makeResponse(422, self::fixture('errors_422.json')));

        $this->assertInstanceOf(Collection::class, $errors);

        $codes = [];
        foreach ($errors as $error) {
            $codes[] = $error->getCode();
        }

        $this->assertSame(['422210', '422210'], $codes);
    }

    public function test_コンストラクタに直接要素を渡せる(): void
    {
        $response = $this->makeResponse(400, '{}');
        $errors = new Errors($response, [new Error(['code' => 'x', 'message' => 'm', 'status' => 400])]);

        $this->assertSame(1, $errors->count());
        $this->assertSame('x', $errors[0]->getCode());
    }
}
