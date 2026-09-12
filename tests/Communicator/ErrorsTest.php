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

    private function assertAllErrorGettersAreSafe(Errors $errors): void
    {
        foreach ($errors->all() as $error) {
            $this->assertIsString($error->getMessage());
            $this->assertIsString($error->getCode());
            $this->assertIsInt($error->getStatus());
        }

        $this->addToAssertionCount(1);
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

    public function test_必須フィールドが欠けたエラー要素はコレクションに追加しない(): void
    {
        $errors = Errors::build($this->makeResponse(422, '{"errors":[{"message":"invalid"}]}'));

        $this->assertSame([], $errors->all());
    }

    public function test_必須フィールドが欠けたエラー要素から利用時の二次例外を発生させない(): void
    {
        $errors = Errors::build($this->makeResponse(422, '{"errors":[{"message":"invalid"}]}'));

        $this->assertAllErrorGettersAreSafe($errors);
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

    /**
     * @return array<string, array{string}>
     */
    public static function invalidArrayElementProvider(): array
    {
        return [
            // 数値キーだけの配列は既知フィールドを持たない空 Error になり得るため、
            // getter の二次例外を防ぐ目的でコレクションには混入させない。
            '数値キーを持つネスト配列' => ['{"errors":[[["nested"]]]}'],
            '数値キーと既知キーが混在する配列' => [
                '{"errors":[{"0":"nested","code":"422210","message":"invalid","status":422}]}',
            ],
            '未知キーだけの連想配列' => ['{"errors":[{"unknown":"x"}]}'],
            'message が配列' => ['{"errors":[{"message":["nested"]}]}'],
            'status が null' => ['{"errors":[{"status":null}]}'],
        ];
    }

    /**
     * Error として全必須 getter を安全に利用できない要素はスキップし、
     * 呼び出し側が元レスポンスから障害内容を確認できること。
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidArrayElementProvider')]
    public function test_構築できない配列要素は無視して元レスポンスを保持する(string $body): void
    {
        $response = $this->makeResponse(502, $body);

        $errors = Errors::build($response);

        $this->assertCount(0, $errors);
        $this->assertSame([], $errors->all());
        $this->assertSame($response, $errors->getResponse());
        $this->assertSame(502, $errors->getResponse()->getStatus());
        $this->assertSame($body, $errors->getResponse()->getRawBody());
    }

    public function test_構築できない要素だけをスキップして正常な要素を保持する(): void
    {
        $body = '{"errors":[[["nested"]],{"code":"422210","message":"invalid","status":422}]}';
        $response = $this->makeResponse(422, $body);

        $errors = Errors::build($response);

        $this->assertSame(1, $errors->count());
        $this->assertSame('422210', $errors[0]->getCode());
        $this->assertSame('invalid', $errors[0]->getMessage());
        $this->assertSame(422, $errors[0]->getStatus());
        $this->assertAllErrorGettersAreSafe($errors);
        $this->assertSame($response, $errors->getResponse());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function getterSafeBodyProvider(): array
    {
        return [
            'errors なし' => ['{}'],
            'errors が null' => ['{"errors":null}'],
            'errors が文字列' => ['{"errors":"error"}'],
            'errors が整数' => ['{"errors":1}'],
            'errors が小数' => ['{"errors":1.5}'],
            'errors が真偽値' => ['{"errors":true}'],
            'errors がオブジェクト' => ['{"errors":{"message":"invalid"}}'],
            'errors が空配列' => ['{"errors":[]}'],
            '要素が文字列' => ['{"errors":["error"]}'],
            '要素が整数' => ['{"errors":[1]}'],
            '要素が小数' => ['{"errors":[1.5]}'],
            '要素が真偽値' => ['{"errors":[false]}'],
            '要素が null' => ['{"errors":[null]}'],
            '要素が空配列' => ['{"errors":[[]]}'],
            '一段のリスト' => ['{"errors":[["nested"]]}'],
            '二段のリスト' => ['{"errors":[[["nested"]]]}'],
            '深いリスト' => ['{"errors":[[[[[["nested"]]]]]]}'],
            '未知キーのみ' => ['{"errors":[{"unknown":"x"}]}'],
            '未知キーがネスト配列' => ['{"errors":[{"unknown":{"nested":["x"]}}]}'],
            'code のみ' => ['{"errors":[{"code":"422210"}]}'],
            'message のみ' => ['{"errors":[{"message":"invalid"}]}'],
            'status のみ' => ['{"errors":[{"status":422}]}'],
            'code と message のみ' => ['{"errors":[{"code":"422210","message":"invalid"}]}'],
            'code と status のみ' => ['{"errors":[{"code":"422210","status":422}]}'],
            'message と status のみ' => ['{"errors":[{"message":"invalid","status":422}]}'],
            'code が整数' => ['{"errors":[{"code":422210,"message":"invalid","status":422}]}'],
            'message が整数' => ['{"errors":[{"code":"422210","message":1,"status":422}]}'],
            'status が文字列' => ['{"errors":[{"code":"422210","message":"invalid","status":"422"}]}'],
            'field が整数' => [
                '{"errors":[{"code":"422210","message":"invalid","field":1,"status":422}]}',
            ],
            '数値キーと必須キーが混在' => [
                '{"errors":[{"0":"nested","code":"422210","message":"invalid","status":422}]}',
            ],
            '正常要素とスカラーの混在' => [
                '{"errors":[{"code":"422210","message":"invalid","status":422},"error"]}',
            ],
            '正常要素とリストの混在' => [
                '{"errors":[[["nested"]],{"code":"422210","message":"invalid","status":422}]}',
            ],
            'field なし正常要素' => [
                '{"errors":[{"code":"401010","message":"unauthorized","status":401}]}',
            ],
            'field あり正常要素' => [
                '{"errors":[{"code":"422210","message":"invalid","field":"sale.id","status":422}]}',
            ],
            '未知キー付き正常要素' => [
                '{"errors":[{"code":"422210","message":"invalid","status":422,"unknown":"x"}]}',
            ],
        ];
    }

    /**
     * build 自体だけでなく、返された Error を通常利用しても二次例外にならないことを保証する。
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getterSafeBodyProvider')]
    public function test_どのようなレスポンス形状でもコレクション内の全getterを安全に呼べる(string $body): void
    {
        $errors = Errors::build($this->makeResponse(502, $body));

        $this->assertAllErrorGettersAreSafe($errors);
    }

    public function test_循環参照を含む要素でも正常要素だけを保持して全getterを安全に呼べる(): void
    {
        $cyclic = [];
        $cyclic['unknown'] = &$cyclic;
        $response = $this->createStub(Response::class);
        $response->method('getParsedBody')->willReturn([
            'errors' => [
                $cyclic,
                ['code' => '422210', 'message' => 'invalid', 'status' => 422],
            ],
        ]);

        $errors = Errors::build($response);

        $this->assertSame(1, $errors->count());
        $this->assertAllErrorGettersAreSafe($errors);
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
