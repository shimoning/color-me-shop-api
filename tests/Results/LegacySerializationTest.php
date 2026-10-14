<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Results;

use GuzzleHttp\Psr7\Response as Psr7Response;
use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Communicator\RequestMeta;
use Shimoning\ColorMeShopApi\Communicator\Response;
use Shimoning\ColorMeShopApi\Entities\Error;
use Shimoning\ColorMeShopApi\Results\Errors;
use Shimoning\ColorMeShopApi\Results\NoContent;
use Shimoning\ColorMeShopApi\Results\OAuth\ErrorResponse;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class LegacySerializationTest extends TestCase
{
    private const LEGACY_ERRORS = 'Shimoning\\ColorMeShopApi\\Communicator\\Errors';
    private const LEGACY_NO_CONTENT = 'Shimoning\\ColorMeShopApi\\Communicator\\NoContent';
    private const LEGACY_OAUTH_ERROR_RESPONSE =
        'Shimoning\\ColorMeShopApi\\Entities\\OAuth\\ErrorResponse';

    public function test_0_32_0のErrorsを内容ごと復元できる(): void
    {
        $result = self::restoreLegacyResult('errors');

        $this->assertInstanceOf(Errors::class, $result);
        $this->assertSame(404, $result->getResponse()->getStatus());
        $this->assertSame(
            '{"errors":[{"code":404100,"message":"not found","status":404}]}',
            $result->getResponse()->getRawBody(),
        );
        $this->assertSame(['application/json'], $result->getResponse()->getRawHeader()['Content-Type']);
        $this->assertSame('GET', $result->getResponse()->getRequestMeta()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/x', $result->getResponse()->getRequestMeta()->getUri());
        $this->assertCount(1, $result);
        $this->assertContainsOnlyInstancesOf(Error::class, $result->all());
        $this->assertSame('404100', $result[0]->getCode());
        $this->assertSame('not found', $result[0]->getMessage());
        $this->assertSame(404, $result[0]->getStatus());
        $this->assertNull($result[0]->getField());
        $this->assertSame([
            'code' => '404100',
            'message' => 'not found',
            'status' => 404,
        ], $result[0]->getRaw());
    }

    public function test_0_32_0のNoContentを内容ごと復元できる(): void
    {
        $result = self::restoreLegacyResult('nocontent');

        $this->assertInstanceOf(NoContent::class, $result);
        $this->assertSame(204, $result->getResponse()->getStatus());
        $this->assertSame('', $result->getResponse()->getRawBody());
        $this->assertNull($result->getResponse()->getParsedBody());
        $this->assertSame([], $result->getResponse()->getRawHeader());
        $this->assertSame('GET', $result->getResponse()->getRequestMeta()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/x', $result->getResponse()->getRequestMeta()->getUri());
    }

    public function test_0_32_0のOAuth_ErrorResponseを内容ごと復元できる(): void
    {
        $result = self::restoreLegacyResult('oauth');

        $this->assertInstanceOf(ErrorResponse::class, $result);
        $this->assertSame('invalid_client', $result->getError());
        $this->assertSame('x', $result->getErrorDescription());
        $this->assertNull($result->getErrorUri());
        $this->assertSame([
            'error' => 'invalid_client',
            'error_description' => 'x',
        ], $result->getRaw());
        $this->assertSame(401, $result->getResponse()->getStatus());
        $this->assertSame(
            '{"error":"invalid_client","error_description":"x"}',
            $result->getResponse()->getRawBody(),
        );
    }

    public function test_0_18_0のstateを持つOAuth_ErrorResponseを内容ごと復元できる(): void
    {
        $deprecations = [];
        $result = self::restoreSerializedResult(
            'oauth_error_response_0.18.0_serialized.json',
            'oauth_0_18_0_with_state',
            deprecations: $deprecations,
        );

        foreach ($deprecations as $deprecation) {
            $this->assertStringContainsString('::$state', $deprecation);
            $this->assertStringNotContainsString('::$_response', $deprecation);
        }

        $this->assertInstanceOf(ErrorResponse::class, $result);
        $this->assertSame('invalid_grant', $result->getError());
        $this->assertSame('abc', $result->getState());
        $this->assertSame([
            'error' => 'invalid_grant',
            'error_description' => 'x',
            'error_uri' => null,
        ], $result->toArray());
        $this->assertSame([
            'error' => 'invalid_grant',
            'error_description' => 'x',
            'state' => 'abc',
        ], $result->getRaw());

        $response = $result->getResponse();
        $this->assertSame(400, $response->getStatus());
        $this->assertSame(
            '{"error":"invalid_grant","error_description":"x","state":"abc"}',
            $response->getRawBody(),
        );
        $this->assertSame([
            'error' => 'invalid_grant',
            'error_description' => 'x',
            'state' => 'abc',
        ], $response->getParsedBody());
        $this->assertSame('POST', $response->getRequestMeta()->getMethod());
        $this->assertSame(
            'https://api.shop-pro.jp/oauth/token',
            $response->getRequestMeta()->getUri(),
        );

        $properties = \get_mangled_object_vars($result);
        $this->assertArrayHasKey('state', $properties);
        $this->assertSame('abc', $properties['state']);

        \set_error_handler(static fn(int $severity): bool => $severity === \E_DEPRECATED);
        try {
            $roundTripped = \unserialize(\serialize($result), ['allowed_classes' => true]);
        } finally {
            \restore_error_handler();
        }
        $this->assertInstanceOf(ErrorResponse::class, $roundTripped);
        $this->assertSame('abc', $roundTripped->getState());
        $this->assertSame($result->getRaw(), $roundTripped->getRaw());
    }

    /** @return array<string, array{string}> */
    public static function payload032KeyProvider(): array
    {
        return [
            'Errors' => ['errors'],
            'NoContent' => ['nocontent'],
            'OAuth ErrorResponse' => ['oauth'],
        ];
    }

    #[DataProvider('payload032KeyProvider')]
    public function test_0_32_0の復元では非推奨警告を出さない(string $fixtureKey): void
    {
        $deprecations = [];

        self::restoreSerializedResult(
            'results_0.32.0_serialized.json',
            $fixtureKey,
            deprecations: $deprecations,
        );

        $this->assertSame([], $deprecations);
    }

    /** @return array<string, array{string, class-string, int, list<class-string>}> */
    public static function legacyAllowedClassesProvider(): array
    {
        $responseClasses = [Response::class, RequestMeta::class];

        return [
            'Errors' => [
                'errors',
                Errors::class,
                404,
                [self::LEGACY_ERRORS, Error::class, ...$responseClasses],
            ],
            'NoContent' => [
                'nocontent',
                NoContent::class,
                204,
                [self::LEGACY_NO_CONTENT, ...$responseClasses],
            ],
            'OAuth ErrorResponse' => [
                'oauth',
                ErrorResponse::class,
                401,
                [self::LEGACY_OAUTH_ERROR_RESPONSE, ...$responseClasses],
            ],
        ];
    }

    /**
     * @param class-string $expectedClass
     * @param list<class-string> $allowedClasses
     */
    #[DataProvider('legacyAllowedClassesProvider')]
    public function test_allowed_classesに旧名を指定して0_32_0の結果を復元できる(
        string $fixtureKey,
        string $expectedClass,
        int $expectedStatus,
        array $allowedClasses,
    ): void {
        $result = self::restoreLegacyResult($fixtureKey, $allowedClasses);

        $this->assertInstanceOf($expectedClass, $result);
        $this->assertSame($expectedStatus, self::responseOf($result)->getStatus());

        $currentSerialized = \serialize($result);
        $this->assertStringContainsString($expectedClass, $currentSerialized);
        $this->assertStringNotContainsString($allowedClasses[0], $currentSerialized);

        $roundTripped = \unserialize($currentSerialized, ['allowed_classes' => true]);
        $this->assertIsObject($roundTripped);
        $this->assertSame($expectedStatus, self::responseOf($roundTripped)->getStatus());
    }

    public function test_現在のクラス名でserializeした結果を内容ごと往復できる(): void
    {
        $errorsResponse = self::response(
            404,
            '{"errors":[{"code":404100,"message":"not found","status":404}]}',
        );
        $oauthResponse = self::response(
            401,
            '{"error":"invalid_client","error_description":"x"}',
        );
        $results = [
            Errors::build($errorsResponse),
            new NoContent(self::response(204, '')),
            new ErrorResponse([
                'error' => 'invalid_client',
                'error_description' => 'x',
            ], $oauthResponse),
        ];
        $restoredResults = [];

        foreach ($results as $result) {
            $restored = \unserialize(\serialize($result), ['allowed_classes' => true]);
            $restoredResults[] = $restored;

            $this->assertSame($result::class, $restored::class);
            $this->assertSame(
                self::responseOf($result)->getStatus(),
                self::responseOf($restored)->getStatus(),
            );
            $this->assertSame(
                self::responseOf($result)->getRawBody(),
                self::responseOf($restored)->getRawBody(),
            );
        }

        $restoredErrors = $restoredResults[0];
        $this->assertInstanceOf(Errors::class, $restoredErrors);
        $this->assertSame('404100', $restoredErrors[0]->getCode());

        $restoredOAuth = $restoredResults[2];
        $this->assertInstanceOf(ErrorResponse::class, $restoredOAuth);
        $this->assertSame('invalid_client', $restoredOAuth->getError());
        $this->assertSame('x', $restoredOAuth->getErrorDescription());
        $this->assertSame([
            'error' => 'invalid_client',
            'error_description' => 'x',
        ], $restoredOAuth->getRaw());
    }

    /** @return array<string, array{string, string, string}> */
    public static function undeclaredSerializedPropertyProvider(): array
    {
        $properties = [
            'public' => ['legacyPublic', 'legacyPublic'],
            'protected' => ["\0*\0legacyProtected", 'legacyProtected'],
            '別スコープのprivate' => ["\0Legacy\\Scope\0legacyPrivate", 'legacyPrivate'],
        ];
        $cases = [];

        foreach (['Errors', 'NoContent', 'OAuth ErrorResponse'] as $resultType) {
            foreach ($properties as $scope => [$serializedProperty, $dynamicProperty]) {
                $cases[$resultType . ' / ' . $scope] = [
                    $resultType,
                    $serializedProperty,
                    $dynamicProperty,
                ];
            }
        }

        return $cases;
    }

    #[DataProvider('undeclaredSerializedPropertyProvider')]
    public function test_未宣言のserializeキーをPHP標準と同じ動的プロパティとして復元する(
        string $resultType,
        string $serializedProperty,
        string $dynamicProperty,
    ): void {
        $result = self::currentResult($resultType);
        $serialized = self::appendSerializedProperty(
            \serialize($result),
            $serializedProperty,
            'legacy-value',
        );

        \set_error_handler(static fn(int $severity): bool => $severity === \E_DEPRECATED);
        try {
            $restored = \unserialize($serialized, ['allowed_classes' => true]);
        } finally {
            \restore_error_handler();
        }

        $this->assertIsObject($restored);
        $this->assertSame($result::class, $restored::class);
        $properties = \get_mangled_object_vars($restored);
        $this->assertArrayHasKey($dynamicProperty, $properties);
        $this->assertSame('legacy-value', $properties[$dynamicProperty]);
        $this->assertSame(
            self::responseOf($result)->getStatus(),
            self::responseOf($restored)->getStatus(),
        );
        $this->assertStringContainsString($dynamicProperty, \serialize($restored));
    }

    /**
     * @param list<class-string>|true $allowedClasses
     */
    private static function restoreLegacyResult(string $key, array|bool $allowedClasses = true): object
    {
        return self::restoreSerializedResult(
            'results_0.32.0_serialized.json',
            $key,
            $allowedClasses,
        );
    }

    /**
     * @param list<class-string>|true $allowedClasses
     */
    private static function restoreSerializedResult(
        string $fixture,
        string $key,
        array|bool $allowedClasses = true,
        ?array &$deprecations = null,
    ): object {
        $fixtures = self::fixtureArray($fixture);
        $encoded = $fixtures[$key] ?? null;
        if (! \is_string($encoded)) {
            throw new \RuntimeException('serialize フィクスチャが見つかりません: ' . $key);
        }

        $serialized = \base64_decode($encoded, true);
        if ($serialized === false) {
            throw new \RuntimeException('serialize フィクスチャをデコードできません: ' . $key);
        }

        $capturedDeprecations = [];
        \set_error_handler(static function (int $severity, string $message) use (&$capturedDeprecations): bool {
            if ($severity !== \E_DEPRECATED) {
                return false;
            }

            $capturedDeprecations[] = $message;
            return true;
        });
        try {
            $result = \unserialize($serialized, ['allowed_classes' => $allowedClasses]);
        } finally {
            \restore_error_handler();
        }
        $deprecations = $capturedDeprecations;
        if (! \is_object($result)) {
            throw new \RuntimeException('serialize フィクスチャを復元できません: ' . $key);
        }

        return $result;
    }

    private static function responseOf(object $result): Response
    {
        if ($result instanceof Errors || $result instanceof NoContent || $result instanceof ErrorResponse) {
            return $result->getResponse();
        }

        throw new \RuntimeException('Response を保持しない結果型です: ' . $result::class);
    }

    private static function currentResult(string $resultType): object
    {
        return match ($resultType) {
            'Errors' => Errors::build(self::response(404, '{"errors":[]}')),
            'NoContent' => new NoContent(self::response(204, '')),
            'OAuth ErrorResponse' => new ErrorResponse(
                ['error' => 'invalid_grant'],
                self::response(400, '{"error":"invalid_grant"}'),
            ),
            default => throw new \RuntimeException('未知の結果型です: ' . $resultType),
        };
    }

    private static function appendSerializedProperty(
        string $serialized,
        string $property,
        string $value,
    ): string {
        $serialized = \preg_replace_callback(
            '/\\AO:(\\d+):"([^"]+)":(\\d+):\\{/',
            static fn(array $matches): string => \sprintf(
                'O:%s:"%s":%d:{',
                $matches[1],
                $matches[2],
                (int)$matches[3] + 1,
            ),
            $serialized,
            1,
            $count,
        );
        if ($serialized === null || $count !== 1 || ! \str_ends_with($serialized, '}')) {
            throw new \RuntimeException('serialize ペイロードへプロパティを追加できません。');
        }

        return \substr($serialized, 0, -1)
            . \sprintf(
                's:%d:"%s";s:%d:"%s";',
                \strlen($property),
                $property,
                \strlen($value),
                $value,
            )
            . '}';
    }

    private static function response(int $status, string $body): Response
    {
        return new Response(
            new Psr7Response($status, ['Content-Type' => 'application/json'], $body),
            new RequestMeta('GET', 'https://api.shop-pro.jp/v1/x', []),
        );
    }
}
