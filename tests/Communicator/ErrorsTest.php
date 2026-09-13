<?php

namespace Shimoning\ColorMeShopApi\Tests\Communicator;

use GuzzleHttp\Psr7\Response as Psr7Response;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Communicator\Response;
use Shimoning\ColorMeShopApi\Communicator\RequestMeta;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Entity;
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

    private function assertAllErrorGettersAreSafe(Errors $errors): void
    {
        foreach ($errors->all() as $error) {
            try {
                $this->assertIsString($error->getCode());
            } catch (MissingFieldException) {
                $this->addToAssertionCount(1);
            }

            try {
                $this->assertIsString($error->getMessage());
            } catch (MissingFieldException) {
                $this->addToAssertionCount(1);
            }

            try {
                $this->assertIsInt($error->getStatus());
            } catch (MissingFieldException) {
                $this->addToAssertionCount(1);
            }

            $this->assertTrue(\is_string($error->getField()) || $error->getField() === null);
            $this->assertIsArray($error->getRaw());
            $this->assertIsArray($error->toArray());
            $this->assertIsArray($error->toArrayRecursive());
        }
    }

    /**
     * @return array{code?: string, message?: string, field?: string|null, status?: int}
     */
    private function availableErrorValues(Error $error): array
    {
        $values = [];
        $raw = $error->getRaw();

        if (\array_key_exists('code', $raw)) {
            $values['code'] = $error->getCode();
        }
        if (\array_key_exists('message', $raw)) {
            $values['message'] = $error->getMessage();
        }
        if (\array_key_exists('field', $raw)) {
            $values['field'] = $error->getField();
        }
        if (\array_key_exists('status', $raw)) {
            $values['status'] = $error->getStatus();
        }

        return $values;
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

    public function test_既知フィールドが1つでもあればコレクションに追加する(): void
    {
        $errors = Errors::build($this->makeResponse(422, '{"errors":[{"message":"invalid"}]}'));

        $this->assertCount(1, $errors);
        $this->assertSame('invalid', $errors[0]->getMessage());
    }

    public function test_欠損フィールドを持つエラー要素でも全getterと配列化を安全に試行できる(): void
    {
        $errors = Errors::build($this->makeResponse(422, '{"errors":[{"message":"invalid"}]}'));

        $this->assertCount(1, $errors);
        $this->assertAllErrorGettersAreSafe($errors);
    }

    public function test_fieldとmessageのみの公式準拠エラーを保持する(): void
    {
        $errors = Errors::build($this->makeResponse(
            422,
            '{"errors":[{"field":"group.name","message":"is invalid"}]}',
        ));

        $this->assertCount(1, $errors);
        $this->assertSame(
            ['message' => 'is invalid', 'field' => 'group.name'],
            $this->availableErrorValues($errors[0]),
        );
        $this->assertAllErrorGettersAreSafe($errors);
    }

    public function test_integerのcodeを持つ公式準拠エラーをstringへ正規化して保持する(): void
    {
        $errors = Errors::build($this->makeResponse(
            401,
            '{"errors":[{"code":401010,"message":"unauthorized","status":401}]}',
        ));

        $this->assertCount(1, $errors);
        $this->assertSame(
            ['code' => '401010', 'message' => 'unauthorized', 'status' => 401],
            $this->availableErrorValues($errors[0]),
        );
        $this->assertAllErrorGettersAreSafe($errors);
    }

    public function test_Code追加プロパティが既知のcodeを上書きしない(): void
    {
        $errors = Errors::build($this->makeResponse(
            422,
            '{"errors":[{"code":401010,"message":"valid","status":422,"Code":"shadow"}]}',
        ));

        $this->assertCount(1, $errors);
        $this->assertSame(
            ['code' => '401010', 'message' => 'valid', 'status' => 422, 'Code' => 'shadow'],
            $errors[0]->getRaw(),
        );
        $this->assertSame('401010', $errors[0]->getCode());
        $this->assertSame('valid', $errors[0]->getMessage());
        $this->assertSame(422, $errors[0]->getStatus());
        $this->assertNull($errors[0]->getField());
    }

    /**
     * @return array<string, array{string, mixed}>
     */
    public static function additionalPropertyProvider(): array
    {
        return [
            'code の先頭だけ大文字' => ['Code', 'shadow'],
            'code の全てが大文字' => ['CODE', 'shadow'],
            'code に末尾アンダースコア' => ['code_', 'shadow'],
            'message に末尾アンダースコア' => ['message_', 'shadow'],
            'status に末尾アンダースコア' => ['status_', 500],
            'field に末尾アンダースコア' => ['field_', 'shadow'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('additionalPropertyProvider')]
    public function test_追加プロパティが既知プロパティを上書きしない(
        string $additionalKey,
        mixed $additionalValue,
    ): void {
        $body = \json_encode([
            'errors' => [[
                'code' => 401010,
                'message' => 'valid',
                'status' => 422,
                'field' => 'sale.id',
                $additionalKey => $additionalValue,
            ]],
        ], \JSON_THROW_ON_ERROR);

        $errors = Errors::build($this->makeResponse(422, $body));

        $this->assertCount(1, $errors);
        $this->assertSame(
            [
                'code' => '401010',
                'message' => 'valid',
                'status' => 422,
                'field' => 'sale.id',
                $additionalKey => $additionalValue,
            ],
            $errors[0]->getRaw(),
        );
        $this->assertSame('401010', $errors[0]->getCode());
        $this->assertSame('valid', $errors[0]->getMessage());
        $this->assertSame(422, $errors[0]->getStatus());
        $this->assertSame('sale.id', $errors[0]->getField());
    }

    public function test_Code配列だけのobjectを空Errorとして保持する(): void
    {
        $errors = Errors::build($this->makeResponse(422, '{"errors":[{"Code":[1]}]}'));

        $this->assertCount(1, $errors);
        $this->assertSame(['Code' => [1]], $errors[0]->getRaw());
        $this->assertSame(
            ['code' => null, 'message' => null, 'field' => null, 'status' => null],
            $errors[0]->toArray(),
        );
        $this->assertAllErrorGettersAreSafe($errors);
    }

    public function test_fieldがnullなら欠損扱いにして有効なcodeと要素を保持する(): void
    {
        $errors = Errors::build($this->makeResponse(
            422,
            '{"errors":[{"code":401010,"field":null}]}',
        ));

        $this->assertCount(1, $errors);
        $this->assertSame(['code' => '401010'], $errors[0]->getRaw());
        $this->assertSame('401010', $errors[0]->getCode());
        $this->assertNull($errors[0]->getField());
    }

    public function test_ネストした追加objectをgetRawで再帰的に配列化する(): void
    {
        $errors = Errors::build($this->makeResponse(
            422,
            '{"errors":[{"unknown":{"nested":["x"]}}]}',
        ));

        $this->assertCount(1, $errors);
        $this->assertSame(['unknown' => ['nested' => ['x']]], $errors[0]->getRaw());
        $this->assertIsArray($errors[0]->getRaw()['unknown']);
        $this->assertIsArray($errors[0]->getRaw()['unknown']['nested']);
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
     * @return array<string, array{string, list<array<array-key, mixed>>}>
     */
    public static function elementShapeProvider(): array
    {
        return [
            // 空 object もワイヤ上のエラー1件として意図的に保持する。
            // getter の欠損は Entity 共通契約どおり MissingFieldException で通知する。
            '空 object' => ['{"errors":[{}]}', [[]]],
            '数値キーを持つネスト配列' => ['{"errors":[[["nested"]]]}', []],
            '数値キーと既知キーが混在する配列' => [
                '{"errors":[{"0":"nested","code":"422210","message":"invalid","status":422}]}',
                [[0 => 'nested', 'code' => '422210', 'message' => 'invalid', 'status' => 422]],
            ],
            '未知キーだけの連想配列' => ['{"errors":[{"unknown":"x"}]}', [['unknown' => 'x']]],
            'message が配列' => ['{"errors":[{"message":["nested"]}]}', [[]]],
            'status が null' => ['{"errors":[{"status":null}]}', [[]]],
        ];
    }

    /**
     * object 形状は有効フィールドがなくても保持し、リスト形状だけをスキップする。
     * 空 Error の getter が MissingFieldException を投げるのは Entity 共通契約として意図した挙動である。
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('elementShapeProvider')]
    public function test_object形状だけをErrorとして保持して元レスポンスも保持する(
        string $body,
        array $expectedRaw,
    ): void {
        $response = $this->makeResponse(502, $body);

        $errors = Errors::build($response);

        $this->assertCount(\count($expectedRaw), $errors);
        $this->assertSame(
            $expectedRaw,
            \array_map(fn(Error $error): array => $error->getRaw(), $errors->all()),
        );
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
     * 将来 Error のフィールド構築経路が変わって例外が発生する状況を、
     * Entity のプロパティ解決キャッシュを一時的に差し替えて再現する。
     */
    public function test_Error構築中に例外が発生しても失敗要素だけをスキップする(): void
    {
        // Error の通常のプロパティ解決結果をキャッシュさせる。
        new Error([]);

        $cache = new \ReflectionProperty(Entity::class, '_properties');
        $originalProperties = $cache->getValue();
        $failingProperties = $originalProperties;
        $failingProperties[Error::class]['code'] = new \ReflectionProperty(Entity::class, '_raw');
        $cache->setValue(null, $failingProperties);

        $response = $this->makeResponse(
            422,
            '{"errors":[{"code":"fails"},{"message":"valid","status":422}]}',
        );

        try {
            $errors = Errors::build($response);
        } finally {
            $cache->setValue(null, $originalProperties);
        }

        $this->assertInstanceOf(Errors::class, $errors);
        $this->assertCount(1, $errors);
        $this->assertSame(['message' => 'valid', 'status' => 422], $errors[0]->getRaw());
        $this->assertSame($response, $errors->getResponse());
    }

    /**
     * @return array<string, array{
     *     string,
     *     list<array{code?: string, message?: string, field?: string|null, status?: int}>
     * }>
     */
    public static function getterSafeBodyProvider(): array
    {
        return [
            'errors なし' => ['{}', []],
            'errors が null' => ['{"errors":null}', []],
            'errors が文字列' => ['{"errors":"error"}', []],
            'errors が整数' => ['{"errors":1}', []],
            'errors が小数' => ['{"errors":1.5}', []],
            'errors が真偽値' => ['{"errors":true}', []],
            'errors がオブジェクト' => ['{"errors":{"message":"invalid"}}', []],
            'errors が空配列' => ['{"errors":[]}', []],
            '要素が文字列' => ['{"errors":["error"]}', []],
            '要素が整数' => ['{"errors":[1]}', []],
            '要素が小数' => ['{"errors":[1.5]}', []],
            '要素が真偽値' => ['{"errors":[false]}', []],
            '要素が null' => ['{"errors":[null]}', []],
            '要素が空配列' => ['{"errors":[[]]}', []],
            '一段のリスト' => ['{"errors":[["nested"]]}', []],
            '二段のリスト' => ['{"errors":[[["nested"]]]}', []],
            '深いリスト' => ['{"errors":[[[[[["nested"]]]]]]}', []],
            '未知キーのみ' => ['{"errors":[{"unknown":"x"}]}', [[]]],
            '未知キーがネスト配列' => ['{"errors":[{"unknown":{"nested":["x"]}}]}', [[]]],
            'code のみ' => ['{"errors":[{"code":"422210"}]}', [['code' => '422210']]],
            'message のみ' => ['{"errors":[{"message":"invalid"}]}', [['message' => 'invalid']]],
            'status のみ' => ['{"errors":[{"status":422}]}', [['status' => 422]]],
            'code と message のみ' => [
                '{"errors":[{"code":"422210","message":"invalid"}]}',
                [['code' => '422210', 'message' => 'invalid']],
            ],
            'code と status のみ' => [
                '{"errors":[{"code":"422210","status":422}]}',
                [['code' => '422210', 'status' => 422]],
            ],
            'message と status のみ' => [
                '{"errors":[{"message":"invalid","status":422}]}',
                [['message' => 'invalid', 'status' => 422]],
            ],
            'code が整数' => [
                '{"errors":[{"code":422210,"message":"invalid","status":422}]}',
                [['code' => '422210', 'message' => 'invalid', 'status' => 422]],
            ],
            'message が整数' => [
                '{"errors":[{"code":"422210","message":1,"status":422}]}',
                [['code' => '422210', 'status' => 422]],
            ],
            'status が文字列' => [
                '{"errors":[{"code":"422210","message":"invalid","status":"422"}]}',
                [['code' => '422210', 'message' => 'invalid']],
            ],
            'field が整数' => [
                '{"errors":[{"code":"422210","message":"invalid","field":1,"status":422}]}',
                [['code' => '422210', 'message' => 'invalid', 'status' => 422]],
            ],
            '数値キーと必須キーが混在' => [
                '{"errors":[{"0":"nested","code":"422210","message":"invalid","status":422}]}',
                [['code' => '422210', 'message' => 'invalid', 'status' => 422]],
            ],
            '正常要素とスカラーの混在' => [
                '{"errors":[{"code":"422210","message":"invalid","status":422},"error"]}',
                [['code' => '422210', 'message' => 'invalid', 'status' => 422]],
            ],
            '正常要素とリストの混在' => [
                '{"errors":[[["nested"]],{"code":"422210","message":"invalid","status":422}]}',
                [['code' => '422210', 'message' => 'invalid', 'status' => 422]],
            ],
            'field なし正常要素' => [
                '{"errors":[{"code":"401010","message":"unauthorized","status":401}]}',
                [['code' => '401010', 'message' => 'unauthorized', 'status' => 401]],
            ],
            'field あり正常要素' => [
                '{"errors":[{"code":"422210","message":"invalid","field":"sale.id","status":422}]}',
                [[
                    'code' => '422210',
                    'message' => 'invalid',
                    'field' => 'sale.id',
                    'status' => 422,
                ]],
            ],
            '未知キー付き正常要素' => [
                '{"errors":[{"code":"422210","message":"invalid","status":422,"unknown":"x"}]}',
                [['code' => '422210', 'message' => 'invalid', 'status' => 422]],
            ],
        ];
    }

    /**
     * build 自体だけでなく、返された Error を通常利用しても二次例外にならないことを保証する。
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getterSafeBodyProvider')]
    public function test_どのようなレスポンス形状でも期待要素を保持し全getterを安全に試行できる(
        string $body,
        array $expected,
    ): void {
        $errors = Errors::build($this->makeResponse(502, $body));

        $this->assertCount(\count($expected), $errors);
        $this->assertSame(
            $expected,
            \array_map(fn(Error $error): array => $this->availableErrorValues($error), $errors->all()),
        );
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

        $this->assertSame(2, $errors->count());
        $this->assertAllErrorGettersAreSafe($errors);
    }

    public function test_object形状の境界でgetRawとtoArrayを固定する(): void
    {
        $errors = Errors::build($this->makeResponse(
            422,
            '{"errors":[{},'
                . '{"extra":"only"},'
                . '{"0":"extra","code":401010},'
                . '{"code":401010,"message":"unauthorized","status":401,"field":"token","extra":true},'
                . '[],null,"scalar"]}',
        ));

        $this->assertCount(4, $errors);
        $this->assertSame(
            [
                [],
                ['extra' => 'only'],
                [0 => 'extra', 'code' => '401010'],
                [
                    'code' => '401010',
                    'message' => 'unauthorized',
                    'status' => 401,
                    'field' => 'token',
                    'extra' => true,
                ],
            ],
            \array_map(fn(Error $error): array => $error->getRaw(), $errors->all()),
        );
        $this->assertSame(
            [
                ['code' => null, 'message' => null, 'field' => null, 'status' => null],
                ['code' => null, 'message' => null, 'field' => null, 'status' => null],
                ['code' => '401010', 'message' => null, 'field' => null, 'status' => null],
                ['code' => '401010', 'message' => 'unauthorized', 'field' => 'token', 'status' => 401],
            ],
            \array_map(fn(Error $error): array => $error->toArray(), $errors->all()),
        );
    }

    public function test_部分不正でも有効なフィールドを保持し不正フィールドだけを欠損扱いにする(): void
    {
        $errors = Errors::build($this->makeResponse(
            422,
            '{"errors":[{"code":401010,"message":["配列"]}]}',
        ));

        $this->assertCount(1, $errors);
        $this->assertSame('401010', $errors[0]->getCode());
        $this->assertSame(['code' => '401010'], $errors[0]->getRaw());
        $this->assertSame(
            ['code' => '401010', 'message' => null, 'field' => null, 'status' => null],
            $errors[0]->toArray(),
        );

        $this->expectException(MissingFieldException::class);
        $errors[0]->getMessage();
    }

    /**
     * @return array<string, array{string, array<string, mixed>, array<string, mixed>}>
     */
    public static function errorFieldSubsetProvider(): array
    {
        $values = [
            'code' => 401010,
            'message' => 'invalid',
            'status' => 422,
            'field' => 'sale.id',
        ];
        $normalizedValues = ['code' => '401010'] + \array_diff_key($values, ['code' => true]);
        $cases = [];

        for ($mask = 0; $mask < 16; ++$mask) {
            $payload = [];
            foreach (\array_keys($values) as $index => $key) {
                if (($mask & (1 << $index)) !== 0) {
                    $payload[$key] = $values[$key];
                }
            }

            $wirePayload = $payload === [] ? (object) [] : $payload;
            $body = \json_encode(['errors' => [$wirePayload]], \JSON_THROW_ON_ERROR);
            $expectedRaw = \array_intersect_key($normalizedValues, $payload);
            $cases[\sprintf('subset %04b', $mask)] = [
                $body,
                $expectedRaw,
                [
                    'code' => $expectedRaw['code'] ?? null,
                    'message' => $expectedRaw['message'] ?? null,
                    'field' => $expectedRaw['field'] ?? null,
                    'status' => $expectedRaw['status'] ?? null,
                ],
            ];
        }

        return $cases;
    }

    /**
     * 4フィールドのどの部分集合でも object は1件として保持する。
     * 空部分集合の getter が MissingFieldException を投げることも意図した Entity 契約である。
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('errorFieldSubsetProvider')]
    public function test_4フィールドの16部分集合でgetRawとtoArrayを固定する(
        string $body,
        array $expectedRaw,
        array $expectedArray,
    ): void {
        $errors = Errors::build($this->makeResponse(422, $body));

        $this->assertCount(1, $errors);
        $this->assertSame($expectedRaw, $errors[0]->getRaw());
        $this->assertSame($expectedArray, $errors[0]->toArray());
    }

    /**
     * @return array<string, array{string, array<string, mixed>, array<string, mixed>}>
     */
    public static function codeBoundaryProvider(): array
    {
        return [
            'code 0' => ['0', ['code' => '0'], ['code' => '0', 'message' => null, 'field' => null, 'status' => null]],
            '負数' => ['-1', ['code' => '-1'], ['code' => '-1', 'message' => null, 'field' => null, 'status' => null]],
            'PHP_INT_MAX' => [
                (string) \PHP_INT_MAX,
                ['code' => (string) \PHP_INT_MAX],
                ['code' => (string) \PHP_INT_MAX, 'message' => null, 'field' => null, 'status' => null],
            ],
            'float' => ['1.5', [], ['code' => null, 'message' => null, 'field' => null, 'status' => null]],
            '数値文字列' => [
                '"401010"',
                ['code' => '401010'],
                ['code' => '401010', 'message' => null, 'field' => null, 'status' => null],
            ],
            '通常 integer' => [
                '401010',
                ['code' => '401010'],
                ['code' => '401010', 'message' => null, 'field' => null, 'status' => null],
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('codeBoundaryProvider')]
    public function test_code境界値のgetRawとtoArrayを固定する(
        string $wireCode,
        array $expectedRaw,
        array $expectedArray,
    ): void {
        $errors = Errors::build($this->makeResponse(422, '{"errors":[{"code":' . $wireCode . '}]}'));

        $this->assertCount(1, $errors);
        $this->assertSame($expectedRaw, $errors[0]->getRaw());
        $this->assertSame($expectedArray, $errors[0]->toArray());
    }

    public function test_401と422fixtureのgetRawとtoArrayは不変(): void
    {
        $errors401 = Errors::build($this->makeResponse(401, self::fixture('errors_401.json')));
        $errors422 = Errors::build($this->makeResponse(422, self::fixture('errors_422.json')));

        $this->assertSame(
            [
                'code' => '401010',
                'message' => 'このリソースにアクセスできません。有効なアクセストークンが見つからないか、必要なスコープが付与されていません。',
                'status' => 401,
            ],
            $errors401[0]->getRaw(),
        );
        $this->assertSame(
            [
                'code' => '401010',
                'message' => 'このリソースにアクセスできません。有効なアクセストークンが見つからないか、必要なスコープが付与されていません。',
                'field' => null,
                'status' => 401,
            ],
            $errors401[0]->toArray(),
        );
        $this->assertSame(
            [
                [
                    'code' => '422210',
                    'message' => 'パラメータが指定されていません。',
                    'field' => 'sale.id',
                    'status' => 422,
                ],
                [
                    'code' => '422210',
                    'message' => 'パラメータが指定されていません。',
                    'field' => 'sale.paid',
                    'status' => 422,
                ],
            ],
            \array_map(fn(Error $error): array => $error->getRaw(), $errors422->all()),
        );
        $this->assertSame(
            \array_map(fn(Error $error): array => $error->getRaw(), $errors422->all()),
            \array_map(fn(Error $error): array => $error->toArray(), $errors422->all()),
        );
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
