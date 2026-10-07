<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests;

use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use ReflectionType;
use Shimoning\ColorMeShopApi\Client;
use Shimoning\ColorMeShopApi\Constants\MailType;
use Shimoning\ColorMeShopApi\Constants\PickupType;
use Shimoning\ColorMeShopApi\Entities\Customer\SearchParameters as CustomerSearchParameters;
use Shimoning\ColorMeShopApi\Entities\OAuth\Options;
use Shimoning\ColorMeShopApi\Entities\Product\Advertising\SearchParameters as AdvertisingSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\SearchParameters as ProductSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\Category\CategoryInput;
use Shimoning\ColorMeShopApi\Entities\Product\Category\ChildInput;
use Shimoning\ColorMeShopApi\Entities\Product\Group\GroupInput;
use Shimoning\ColorMeShopApi\Entities\Product\Option\OptionCreateInput;
use Shimoning\ColorMeShopApi\Entities\Product\Option\Value\ValueCreateInput;
use Shimoning\ColorMeShopApi\Entities\Product\Pickup\PickupInput;
use Shimoning\ColorMeShopApi\Entities\Product\Stock\SearchParameters as StockSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\Variant\SearchParameters as VariantSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\Variant\VariantUpdateInput;
use Shimoning\ColorMeShopApi\Entities\Sale\SearchParameters as SaleSearchParameters;
use Shimoning\ColorMeShopApi\Services\OAuth;
use Shimoning\ColorMeShopApi\Services\Product;
use Shimoning\ColorMeShopApi\Services\Product\Advertising as ProductAdvertising;
use Shimoning\ColorMeShopApi\Services\Product\Category as ProductCategory;
use Shimoning\ColorMeShopApi\Services\Product\Group as ProductGroup;
use Shimoning\ColorMeShopApi\Services\Product\Image as ProductImage;
use Shimoning\ColorMeShopApi\Services\Product\Option as ProductOption;
use Shimoning\ColorMeShopApi\Services\Product\Option\Value as ProductOptionValue;
use Shimoning\ColorMeShopApi\Services\Product\Pickup as ProductPickup;
use Shimoning\ColorMeShopApi\Services\Product\Stock as ProductStock;
use Shimoning\ColorMeShopApi\Services\Product\Variant as ProductVariant;
use Shimoning\ColorMeShopApi\Tests\Support\HttpMock;

class DeprecatedMethodAliasesTest extends TestCase
{
    /**
     * @param class-string $newClass
     * @param list<mixed> $arguments
     */
    #[DataProvider('splitProductMethodProvider')]
    public function test_Productの分割済みdeprecatedメソッドはサブServiceと同じリクエストと結果を返す(
        string $oldName,
        string $newClass,
        string $newName,
        array $arguments,
        string $responseBody,
    ): void {
        $oldMock = HttpMock::json(200, $responseBody);
        $newMock = HttpMock::json(200, $responseBody);
        $oldResult = (new Product('constructor-token', $oldMock->client()))->$oldName(...$arguments);
        $newResult = (new $newClass('constructor-token', $newMock->client()))->$newName(...$arguments);

        $this->assertEquals($newResult, $oldResult);
        $this->assertSame($newMock->request()->getMethod(), $oldMock->request()->getMethod());
        $this->assertSame($newMock->uri(), $oldMock->uri());
        $this->assertSame(
            self::normalizeMultipartBoundary($newMock->body()),
            self::normalizeMultipartBoundary($oldMock->body()),
        );
        $this->assertSame($newMock->header('Authorization'), $oldMock->header('Authorization'));
    }

    /** @param class-string $newClass */
    #[DataProvider('splitProductMethodProvider')]
    public function test_Productの分割済みdeprecatedメソッドはサブServiceとシグネチャが一致する(
        string $oldName,
        string $newClass,
        string $newName,
    ): void {
        $this->assertSameSignature(
            new ReflectionMethod(Product::class, $oldName),
            new ReflectionMethod($newClass, $newName),
        );
    }

    /** @param class-string $newClass */
    #[DataProvider('splitProductMethodProvider')]
    public function test_Productの分割済みdeprecatedメソッドのPHPDocが移行先とADRを示す(
        string $oldName,
        string $newClass,
        string $newName,
    ): void {
        $document = (new ReflectionMethod(Product::class, $oldName))->getDocComment();
        $migration = \str_replace('Shimoning\\ColorMeShopApi\\', '', $newClass) . '::' . $newName . '()';

        $this->assertIsString($document);
        $this->assertStringContainsString('@deprecated 0.26.0 ' . $migration . ' を使うこと。', $document);
        $this->assertStringContainsString('@see docs/adr/0034-split-product-service.md', $document);
    }

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

    /** @return array<string, array{string, class-string, string, list<mixed>, string}> */
    public static function splitProductMethodProvider(): array
    {
        return [
            'stockPage' => ['stockPage', ProductStock::class, 'page', [new StockSearchParameters(['limit' => 1]), 'argument-token'], '{"stocks":[],"meta":{"total":0,"limit":1,"offset":0}}'],
            'variantPage' => ['variantPage', ProductVariant::class, 'page', [101, new VariantSearchParameters(['limit' => 1]), 'argument-token'], '{"variants":[],"meta":{"total":0,"limit":1,"offset":0}}'],
            'variantOne' => ['variantOne', ProductVariant::class, 'one', [101, 301, 'argument-token'], '{"variant":{"id":301}}'],
            'updateVariant' => ['updateVariant', ProductVariant::class, 'update', [101, 301, new VariantUpdateInput(['price' => 100]), 'argument-token'], '{"variant":{"id":301}}'],
            'createOption' => ['createOption', ProductOption::class, 'create', [101, new OptionCreateInput(['name' => '色']), 'argument-token'], self::fixture('product_option_created.json')],
            'deleteOption' => ['deleteOption', ProductOption::class, 'delete', [101, 201, 'argument-token'], ''],
            'createOptionValue' => ['createOptionValue', ProductOptionValue::class, 'create', [101, 201, new ValueCreateInput(['name' => '赤']), 'argument-token'], self::fixture('product_option_value_created.json')],
            'deleteOptionValue' => ['deleteOptionValue', ProductOptionValue::class, 'delete', [101, 201, 301, 'argument-token'], ''],
            'createPickup' => ['createPickup', ProductPickup::class, 'create', [101, new PickupInput(['pickup_type' => 1, 'order_num' => 2]), 'argument-token'], self::fixture('product_pickup.json')],
            'updatePickup' => ['updatePickup', ProductPickup::class, 'update', [101, new PickupInput(['pickup_type' => 1, 'order_num' => 2]), 'argument-token'], self::fixture('product_pickup.json')],
            'deletePickup' => ['deletePickup', ProductPickup::class, 'delete', [101, PickupType::RECOMMENDED, 'argument-token'], self::fixture('product_pickup.json')],
            'imageAll' => ['imageAll', ProductImage::class, 'all', [101, 'argument-token'], '{"product":{"images":[]}}'],
            'createImage' => ['createImage', ProductImage::class, 'create', [101, __DIR__ . '/Fixtures/product_image_created.json', 1, 'argument-token', 'image.jpg'], self::fixture('product_image_created.json')],
            'deleteImage' => ['deleteImage', ProductImage::class, 'delete', [101, 1, 'argument-token'], ''],
            'groupOne' => ['groupOne', ProductGroup::class, 'one', [401, 'argument-token'], '{"group":{"id":401}}'],
            'groupAll' => ['groupAll', ProductGroup::class, 'all', ['argument-token'], '{"groups":[]}'],
            'createGroup' => ['createGroup', ProductGroup::class, 'create', [new GroupInput(['name' => '新着']), 'argument-token'], self::fixture('group_created.json')],
            'updateGroup' => ['updateGroup', ProductGroup::class, 'update', [401, new GroupInput(['name' => '新着']), 'argument-token'], self::fixture('group_created.json')],
            'categoryAll' => ['categoryAll', ProductCategory::class, 'all', ['argument-token'], '{"categories":[]}'],
            'createCategory' => ['createCategory', ProductCategory::class, 'create', [new CategoryInput(['name' => '親']), 'argument-token'], self::fixture('category_created.json')],
            'updateCategory' => ['updateCategory', ProductCategory::class, 'update', [501, new CategoryInput(['name' => '親']), 'argument-token'], self::fixture('category_created.json')],
            'createCategoryChild' => ['createCategoryChild', ProductCategory::class, 'createChild', [501, new ChildInput(['name' => '子']), 'argument-token'], self::fixture('category_child_created.json')],
            'updateCategoryChild' => ['updateCategoryChild', ProductCategory::class, 'updateChild', [501, 502, new ChildInput(['name' => '子']), 'argument-token'], self::fixture('category_child_created.json')],
            'advertisingPage' => ['advertisingPage', ProductAdvertising::class, 'page', [new AdvertisingSearchParameters(['limit' => 1]), 'argument-token'], '{"product_advertisings":[],"meta":{"total":0,"limit":1,"offset":0}}'],
        ];
    }

    private function assertSameSignature(ReflectionMethod $oldMethod, ReflectionMethod $newMethod): void
    {
        $oldParameters = $oldMethod->getParameters();
        $newParameters = $newMethod->getParameters();
        $this->assertCount(\count($newParameters), $oldParameters);

        foreach ($oldParameters as $position => $oldParameter) {
            $newParameter = $newParameters[$position];
            $this->assertSame($newParameter->getName(), $oldParameter->getName());
            $this->assertSame($newParameter->getPosition(), $oldParameter->getPosition());
            $this->assertSame(self::reflectionTypeToString($newParameter->getType()), self::reflectionTypeToString($oldParameter->getType()));
            $this->assertSame($newParameter->isDefaultValueAvailable(), $oldParameter->isDefaultValueAvailable());
            if ($oldParameter->isDefaultValueAvailable() && $newParameter->isDefaultValueAvailable()) {
                $this->assertSame($newParameter->getDefaultValue(), $oldParameter->getDefaultValue());
                $this->assertSame($newParameter->isDefaultValueConstant(), $oldParameter->isDefaultValueConstant());
                if ($oldParameter->isDefaultValueConstant() && $newParameter->isDefaultValueConstant()) {
                    $this->assertSame($newParameter->getDefaultValueConstantName(), $oldParameter->getDefaultValueConstantName());
                }
            }
            $this->assertSame($newParameter->isVariadic(), $oldParameter->isVariadic());
            $this->assertSame($newParameter->isPassedByReference(), $oldParameter->isPassedByReference());
        }
        $this->assertSame(
            self::reflectionTypeToString($newMethod->getReturnType()),
            self::reflectionTypeToString($oldMethod->getReturnType()),
        );
    }

    private static function oauthOptions(): Options
    {
        return new Options('client-id', 'client-secret', 'https://example.test/callback');
    }

    private static function normalizeMultipartBoundary(string $body): string
    {
        return \preg_replace('/--[a-f0-9]{40}/', '--boundary', $body) ?? $body;
    }

    private static function reflectionTypeToString(?ReflectionType $type): ?string
    {
        return $type === null ? null : (string) $type;
    }
}
