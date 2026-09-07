<?php

namespace Shimoning\ColorMeShopApi\Tests\Communicator;

use GuzzleHttp\Psr7\Response as Psr7Response;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Communicator\Response;
use Shimoning\ColorMeShopApi\Communicator\RequestMeta;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Error;
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
     * 仕様化テスト (既知の不具合)。
     *
     * Error::$field はデフォルト値を持たない typed property のため、API レスポンスに
     * field が含まれない場合 (401 / 404 など) に getField() が Error を投げる。
     * 詳細と対処方針は docs/TESTING_PLAN.md の「発見事項」を参照。
     */
    public function test_fieldがないエラーのgetFieldは未初期化エラーになる(): void
    {
        $errors = Errors::build($this->makeResponse(401, self::fixture('errors_401.json')));

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('must not be accessed before initialization');

        $errors[0]->getField();
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
