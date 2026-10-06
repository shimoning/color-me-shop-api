<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests;

use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use ReflectionType;
use Shimoning\ColorMeShopApi\Client;
use Shimoning\ColorMeShopApi\Constants\MailType;
use Shimoning\ColorMeShopApi\Entities\Customer\SearchParameters as CustomerSearchParameters;
use Shimoning\ColorMeShopApi\Entities\OAuth\Options;
use Shimoning\ColorMeShopApi\Entities\Product\Advertising\SearchParameters as AdvertisingSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\SearchParameters as ProductSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\Variant\SearchParameters as VariantSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Sale\SearchParameters as SaleSearchParameters;
use Shimoning\ColorMeShopApi\Services\OAuth;
use Shimoning\ColorMeShopApi\Services\Product;
use Shimoning\ColorMeShopApi\Tests\Support\HttpMock;

class DeprecatedMethodAliasesTest extends TestCase
{
    /**
     * @param \Closure(ClientInterface): object $factory
     * @param list<mixed> $arguments
     */
    #[DataProvider('deprecatedMethodProvider')]
    public function test_deprecatedメソッドは新名と同じ引数で同じリクエストと結果を返す(
        \Closure $factory,
        string $oldName,
        string $newName,
        array $arguments,
        string $responseBody,
    ): void {
        $oldMock = HttpMock::json(200, $responseBody);
        $newMock = HttpMock::json(200, $responseBody);

        $oldResult = ($factory($oldMock->client()))->$oldName(...$arguments);
        $newResult = ($factory($newMock->client()))->$newName(...$arguments);

        $this->assertEquals($newResult, $oldResult);
        $this->assertSame($newMock->request()->getMethod(), $oldMock->request()->getMethod());
        $this->assertSame($newMock->uri(), $oldMock->uri());
        $this->assertSame($newMock->body(), $oldMock->body());
        $this->assertSame($newMock->header('Authorization'), $oldMock->header('Authorization'));
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('deprecatedPhpDocProvider')]
    public function test_deprecatedメソッドのPHPDocが移行先とADRを示す(
        string $class,
        string $oldName,
        string $newName,
    ): void {
        $document = (new ReflectionMethod($class, $oldName))->getDocComment();

        $this->assertIsString($document);
        $this->assertStringContainsString('@deprecated 0.25.0 ' . $newName . '() を使うこと。', $document);
        $this->assertStringContainsString('@see docs/adr/0033-unify-client-and-service-method-names.md', $document);
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('deprecatedPhpDocProvider')]
    public function test_deprecatedメソッドのシグネチャは新名と一致する(
        string $class,
        string $oldName,
        string $newName,
    ): void {
        $oldMethod = new ReflectionMethod($class, $oldName);
        $newMethod = new ReflectionMethod($class, $newName);
        $oldParameters = $oldMethod->getParameters();
        $newParameters = $newMethod->getParameters();
        $methodContext = \sprintf('%s::%s() と %s()', $class, $oldName, $newName);

        $this->assertCount(
            \count($newParameters),
            $oldParameters,
            $methodContext . ' の引数の数が一致しません。',
        );

        foreach ($oldParameters as $position => $oldParameter) {
            $newParameter = $newParameters[$position];
            $parameterContext = \sprintf(
                '%s の引数 #%d ($%s / $%s)',
                $methodContext,
                $position,
                $oldParameter->getName(),
                $newParameter->getName(),
            );

            $this->assertSame(
                $newParameter->getName(),
                $oldParameter->getName(),
                $parameterContext . ' の名前が一致しません。',
            );
            $this->assertSame(
                $newParameter->getPosition(),
                $oldParameter->getPosition(),
                $parameterContext . ' の位置が一致しません。',
            );
            $this->assertSame(
                self::reflectionTypeToString($newParameter->getType()),
                self::reflectionTypeToString($oldParameter->getType()),
                $parameterContext . ' の型が一致しません。',
            );
            $this->assertSame(
                $newParameter->isDefaultValueAvailable(),
                $oldParameter->isDefaultValueAvailable(),
                $parameterContext . ' の既定値の有無が一致しません。',
            );

            if ($oldParameter->isDefaultValueAvailable() && $newParameter->isDefaultValueAvailable()) {
                $this->assertSame(
                    $newParameter->getDefaultValue(),
                    $oldParameter->getDefaultValue(),
                    $parameterContext . ' の既定値が一致しません。',
                );
                $this->assertSame(
                    $newParameter->isDefaultValueConstant(),
                    $oldParameter->isDefaultValueConstant(),
                    $parameterContext . ' の既定値が定数かどうか一致しません。',
                );

                if ($oldParameter->isDefaultValueConstant() && $newParameter->isDefaultValueConstant()) {
                    $this->assertSame(
                        $newParameter->getDefaultValueConstantName(),
                        $oldParameter->getDefaultValueConstantName(),
                        $parameterContext . ' の既定値の定数名が一致しません。',
                    );
                }
            }

            $this->assertSame(
                $newParameter->isVariadic(),
                $oldParameter->isVariadic(),
                $parameterContext . ' の可変長指定が一致しません。',
            );
            $this->assertSame(
                $newParameter->isPassedByReference(),
                $oldParameter->isPassedByReference(),
                $parameterContext . ' の参照渡し指定が一致しません。',
            );
        }

        $this->assertSame(
            self::reflectionTypeToString($newMethod->getReturnType()),
            self::reflectionTypeToString($oldMethod->getReturnType()),
            $methodContext . ' の戻り値の型が一致しません。',
        );
    }

    public function test_Clientのdeprecatedメソッドは名前付き引数で新名と同じリクエストになる(): void
    {
        $responseBody = '{"products":[],"meta":{"total":0,"limit":1,"offset":0}}';
        $oldMock = HttpMock::json(200, $responseBody);
        $newMock = HttpMock::json(200, $responseBody);

        $oldResult = (new Client('constructor-token', $oldMock->client()))->getProducts(
            parameters: new ProductSearchParameters(['limit' => 1]),
            accessToken: 'argument-token',
        );
        $newResult = (new Client('constructor-token', $newMock->client()))->getProductPage(
            parameters: new ProductSearchParameters(['limit' => 1]),
            accessToken: 'argument-token',
        );

        $this->assertEquals($newResult, $oldResult);
        $this->assertSame($newMock->request()->getMethod(), $oldMock->request()->getMethod());
        $this->assertSame($newMock->uri(), $oldMock->uri());
        $this->assertSame($newMock->body(), $oldMock->body());
        $this->assertSame($newMock->header('Authorization'), $oldMock->header('Authorization'));
    }

    public function test_OAuthのdeprecatedメソッドは名前付き引数で新名と同じリクエストになる(): void
    {
        $responseBody = self::fixture('oauth_token.json');
        $oldMock = HttpMock::json(200, $responseBody);
        $newMock = HttpMock::json(200, $responseBody);

        $oldResult = (new OAuth(self::oauthOptions(), $oldMock->client()))->exchangeCode2Token(
            code: 'auth-code',
        );
        $newResult = (new OAuth(self::oauthOptions(), $newMock->client()))->exchangeCodeForToken(
            code: 'auth-code',
        );

        $this->assertEquals($newResult, $oldResult);
        $this->assertSame($newMock->request()->getMethod(), $oldMock->request()->getMethod());
        $this->assertSame($newMock->uri(), $oldMock->uri());
        $this->assertSame($newMock->body(), $oldMock->body());
        $this->assertSame($newMock->header('Authorization'), $oldMock->header('Authorization'));
    }

    /** @return array<string, array{\Closure(ClientInterface): object, string, string, list<mixed>, string}> */
    public static function deprecatedMethodProvider(): array
    {
        $client = static fn(ClientInterface $httpClient): Client => new Client('constructor-token', $httpClient);
        $product = static fn(ClientInterface $httpClient): Product => new Product('constructor-token', $httpClient);
        $oauth = static fn(ClientInterface $httpClient): OAuth => new OAuth(self::oauthOptions(), $httpClient);

        return [
            'Client::getProducts' => [$client, 'getProducts', 'getProductPage', [new ProductSearchParameters(['limit' => 1])], '{"products":[],"meta":{"total":0,"limit":1,"offset":0}}'],
            'Client::getStocks' => [$client, 'getStocks', 'getProductStockPage', [new \Shimoning\ColorMeShopApi\Entities\Product\Stock\SearchParameters(['limit' => 1])], '{"stocks":[],"meta":{"total":0,"limit":1,"offset":0}}'],
            'Client::getProductVariants' => [$client, 'getProductVariants', 'getProductVariantPage', [101, new VariantSearchParameters(['limit' => 1]), 'argument-token'], '{"variants":[],"meta":{"total":0,"limit":1,"offset":0}}'],
            'Client::getProductAdvertisings' => [$client, 'getProductAdvertisings', 'getProductAdvertisingPage', [new AdvertisingSearchParameters(['limit' => 1])], '{"product_advertisings":[],"meta":{"total":0,"limit":1,"offset":0}}'],
            'Client::getSales' => [$client, 'getSales', 'getSalePage', [new SaleSearchParameters(['limit' => 1])], '{"sales":[],"meta":{"total":0,"limit":1,"offset":0}}'],
            'Client::getCustomers' => [$client, 'getCustomers', 'getCustomerPage', [new CustomerSearchParameters(['limit' => 1])], '{"customers":[],"meta":{"total":0,"limit":1,"offset":0}}'],
            'Client::statSales' => [$client, 'statSales', 'getSaleStat', [new \DateTimeImmutable('2024-01-01')], '{"sales_stat":{}}'],
            'Client::sendSalesMail' => [$client, 'sendSalesMail', 'sendSaleMail', [1001, MailType::PAID], '{}'],
            'Client::exchangeCode2Token' => [$client, 'exchangeCode2Token', 'exchangeCodeForToken', [self::oauthOptions(), 'auth-code'], self::fixture('oauth_token.json')],
            'Product::products' => [$product, 'products', 'page', [new ProductSearchParameters(['limit' => 1])], '{"products":[],"meta":{"total":0,"limit":1,"offset":0}}'],
            'Product::product' => [$product, 'product', 'one', [101], '{"product":{"id":101}}'],
            'Product::variants' => [$product, 'variants', 'variantPage', [101, new VariantSearchParameters(['limit' => 1]), 'argument-token'], '{"variants":[],"meta":{"total":0,"limit":1,"offset":0}}'],
            'Product::variant' => [$product, 'variant', 'variantOne', [101, 301], '{"variant":{"id":301}}'],
            'Product::images' => [$product, 'images', 'imageAll', [101], '{"product":{"id":101,"images":[]}}'],
            'Product::advertisings' => [$product, 'advertisings', 'advertisingPage', [new AdvertisingSearchParameters(['limit' => 1])], '{"product_advertisings":[],"meta":{"total":0,"limit":1,"offset":0}}'],
            'Product::group' => [$product, 'group', 'groupOne', [401], '{"group":{"id":401}}'],
            'Product::groups' => [$product, 'groups', 'groupAll', ['argument-token'], '{"groups":[]}'],
            'Product::categories' => [$product, 'categories', 'categoryAll', ['argument-token'], '{"categories":[]}'],
            'OAuth::exchangeCode2Token' => [$oauth, 'exchangeCode2Token', 'exchangeCodeForToken', ['auth-code'], self::fixture('oauth_token.json')],
        ];
    }

    /** @return array<string, array{class-string, string, string}> */
    public static function deprecatedPhpDocProvider(): array
    {
        $cases = [];
        foreach (self::deprecatedMethodProvider() as $name => [, $oldName, $newName]) {
            $class = \str_starts_with($name, 'Client::')
                ? Client::class
                : (\str_starts_with($name, 'Product::') ? Product::class : OAuth::class);
            $cases[$name] = [$class, $oldName, $newName];
        }
        return $cases;
    }

    private static function oauthOptions(): Options
    {
        return new Options('client-id', 'client-secret', 'https://example.test/callback');
    }

    private static function reflectionTypeToString(?ReflectionType $type): ?string
    {
        return $type === null ? null : (string) $type;
    }
}
