<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Shimoning\ColorMeShopApi\Client;
use Shimoning\ColorMeShopApi\Constants\AuthScope;
use Shimoning\ColorMeShopApi\Services\Customer;
use Shimoning\ColorMeShopApi\Services\Delivery;
use Shimoning\ColorMeShopApi\Services\Gift;
use Shimoning\ColorMeShopApi\Services\OAuth;
use Shimoning\ColorMeShopApi\Services\Payment;
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
use Shimoning\ColorMeShopApi\Services\Sale;
use Shimoning\ColorMeShopApi\Services\Service;
use Shimoning\ColorMeShopApi\Services\Shop;
use Shimoning\ColorMeShopApi\Services\Stock;

class AuthScopeDocumentationTest extends TestCase
{
    /**
     * @return array<string, array{class-string, string, string, list<AuthScope>}>
     */
    public static function apiMethodProvider(): array
    {
        return [
            'Customer::page' => [Customer::class, 'page', 'getCustomerPage', [AuthScope::READ_SALES]],
            'Customer::one' => [Customer::class, 'one', 'getCustomer', [AuthScope::READ_SALES]],
            'Customer::create' => [Customer::class, 'create', 'createCustomer', [AuthScope::WRITE_SALES]],
            'Customer::update' => [Customer::class, 'update', 'updateCustomer', [AuthScope::WRITE_SALES]],
            'Customer::changePoints' => [Customer::class, 'changePoints', 'changeCustomerPoints', [AuthScope::WRITE_SALES]],
            'Delivery::all' => [Delivery::class, 'all', 'getDeliveries', []],
            'Delivery::dateSetting' => [Delivery::class, 'dateSetting', 'getDeliveryDateSetting', []],
            'Gift::get' => [Gift::class, 'get', 'getGift', []],
            'Payment::all' => [Payment::class, 'all', 'getPayments', []],
            'Product::page' => [Product::class, 'page', 'getProductPage', [AuthScope::READ_PRODUCTS]],
            'Product::one' => [Product::class, 'one', 'getProduct', [AuthScope::READ_PRODUCTS]],
            'Product::create' => [Product::class, 'create', 'createProduct', [AuthScope::WRITE_PRODUCTS]],
            'Product::update' => [Product::class, 'update', 'updateProduct', [AuthScope::WRITE_PRODUCTS]],
            'ProductAdvertising::page' => [ProductAdvertising::class, 'page', 'getProductAdvertisingPage', [AuthScope::READ_PRODUCTS]],
            'ProductCategory::all' => [ProductCategory::class, 'all', 'getProductCategories', []],
            'ProductCategory::create' => [ProductCategory::class, 'create', 'createProductCategory', [AuthScope::WRITE_PRODUCTS]],
            'ProductCategory::update' => [ProductCategory::class, 'update', 'updateProductCategory', [AuthScope::WRITE_PRODUCTS]],
            'ProductCategory::createChild' => [ProductCategory::class, 'createChild', 'createProductCategoryChild', [AuthScope::WRITE_PRODUCTS]],
            'ProductCategory::updateChild' => [ProductCategory::class, 'updateChild', 'updateProductCategoryChild', [AuthScope::WRITE_PRODUCTS]],
            'ProductGroup::one' => [ProductGroup::class, 'one', 'getProductGroup', []],
            'ProductGroup::all' => [ProductGroup::class, 'all', 'getProductGroups', []],
            'ProductGroup::create' => [ProductGroup::class, 'create', 'createProductGroup', [AuthScope::WRITE_PRODUCTS]],
            'ProductGroup::update' => [ProductGroup::class, 'update', 'updateProductGroup', [AuthScope::WRITE_PRODUCTS]],
            'ProductImage::all' => [ProductImage::class, 'all', 'getProductImages', [AuthScope::READ_PRODUCTS]],
            'ProductImage::create' => [ProductImage::class, 'create', 'createProductImage', [AuthScope::READ_PRODUCTS, AuthScope::WRITE_PRODUCTS]],
            'ProductImage::delete' => [ProductImage::class, 'delete', 'deleteProductImage', [AuthScope::WRITE_PRODUCTS]],
            'ProductOption::create' => [ProductOption::class, 'create', 'createProductOption', [AuthScope::WRITE_PRODUCTS]],
            'ProductOption::delete' => [ProductOption::class, 'delete', 'deleteProductOption', [AuthScope::WRITE_PRODUCTS]],
            'ProductOptionValue::create' => [ProductOptionValue::class, 'create', 'createProductOptionValue', [AuthScope::WRITE_PRODUCTS]],
            'ProductOptionValue::delete' => [ProductOptionValue::class, 'delete', 'deleteProductOptionValue', [AuthScope::WRITE_PRODUCTS]],
            'ProductPickup::create' => [ProductPickup::class, 'create', 'createProductPickup', [AuthScope::WRITE_PRODUCTS]],
            'ProductPickup::update' => [ProductPickup::class, 'update', 'updateProductPickup', [AuthScope::WRITE_PRODUCTS]],
            'ProductPickup::delete' => [ProductPickup::class, 'delete', 'deleteProductPickup', [AuthScope::WRITE_PRODUCTS]],
            'ProductStock::page' => [ProductStock::class, 'page', 'getProductStockPage', []],
            'ProductVariant::page' => [ProductVariant::class, 'page', 'getProductVariantPage', [AuthScope::READ_PRODUCTS]],
            'ProductVariant::one' => [ProductVariant::class, 'one', 'getProductVariant', [AuthScope::READ_PRODUCTS]],
            'ProductVariant::update' => [ProductVariant::class, 'update', 'updateProductVariant', [AuthScope::WRITE_PRODUCTS]],
            'Sale::page' => [Sale::class, 'page', 'getSalePage', [AuthScope::READ_SALES]],
            'Sale::one' => [Sale::class, 'one', 'getSale', [AuthScope::READ_SALES]],
            'Sale::stat' => [Sale::class, 'stat', 'getSaleStat', [AuthScope::READ_SALES]],
            'Sale::create' => [Sale::class, 'create', 'createSale', [AuthScope::WRITE_SALES]],
            'Sale::update' => [Sale::class, 'update', 'updateSale', [AuthScope::WRITE_SALES]],
            'Sale::cancel' => [Sale::class, 'cancel', 'cancelSale', [AuthScope::WRITE_SALES]],
            'Sale::sendMail' => [Sale::class, 'sendMail', 'sendSaleMail', [AuthScope::WRITE_SALES]],
            'Shop::get' => [Shop::class, 'get', 'getShop', []],
            'Stock::page' => [Stock::class, 'page', 'getProductStockPage', []],
        ];
    }

    /**
     * OpenAPI に scope 指定がないメソッドは空配列とし、記載がないことを明示的に固定する。
     *
     * @param class-string $serviceClass
     * @param list<AuthScope> $scopes
     */
    #[DataProvider('apiMethodProvider')]
    public function test_ServiceとClientのPHPDocに必要なOAuthスコープが記載されている(
        string $serviceClass,
        string $serviceMethod,
        string $clientMethod,
        array $scopes,
    ): void {
        $this->assertScopeDocumentation(new ReflectionMethod($serviceClass, $serviceMethod), $scopes);
        $this->assertScopeDocumentation(new ReflectionMethod(Client::class, $clientMethod), $scopes);
    }

    public function test_全Service公開APIとClientファサードが対応表に含まれる(): void
    {
        $providedServiceClasses = [];
        $providedServices = [];
        $providedClientMethods = [];
        foreach (self::apiMethodProvider() as [$serviceClass, $serviceMethod, $clientMethod]) {
            $providedServiceClasses[] = $serviceClass;
            $providedServices[] = $serviceClass . '::' . $serviceMethod;
            $providedClientMethods[] = $clientMethod;
        }
        $providedServiceClasses = \array_values(\array_unique($providedServiceClasses));

        $declaredServices = [];
        $declaredServiceClasses = self::sourceServiceClasses();
        foreach ($declaredServiceClasses as $class) {
            foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() === $class && ! self::isDeprecated($method)) {
                    $declaredServices[] = $class . '::' . $method->getName();
                }
            }
        }

        $declaredClientMethods = [];
        foreach ((new ReflectionClass(Client::class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // OAuth 認証フローとコンストラクタは Service API のファサードではないため対象外。
            if (
                ! \in_array($method->getName(), ['__construct', 'getOAuthUrl', 'exchangeCodeForToken'], true)
                && ! self::isDeprecated($method)
            ) {
                $declaredClientMethods[] = $method->getName();
            }
        }

        \sort($providedServiceClasses);
        \sort($providedServices);
        \sort($declaredServices);
        $providedClientMethods = \array_values(\array_unique($providedClientMethods));
        \sort($providedClientMethods);
        \sort($declaredClientMethods);

        $this->assertSame($declaredServiceClasses, $providedServiceClasses);
        $this->assertSame($declaredServices, $providedServices);
        $this->assertSame($declaredClientMethods, $providedClientMethods);
    }

    private static function isDeprecated(ReflectionMethod $method): bool
    {
        $document = $method->getDocComment();
        return \is_string($document) && \str_contains($document, '@deprecated');
    }

    /**
     * src/Services 配下の全 Service サブクラスを集める。
     *
     * @return list<class-string<Service>>
     */
    private static function sourceServiceClasses(): array
    {
        $classes = [];
        $base = \realpath(__DIR__ . '/../src/Services');
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base));
        $excluded = [
            // Service は各 API サービスの共通処理を持つ抽象基底クラスであり、公開 API ではない。
            Service::class,
            // OAuth はトークンエンドポイントを扱い、通常の API とはスコープの概念が異なる。
            OAuth::class,
        ];

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $relative = \substr($file->getPathname(), \strlen($base) + 1);
            $class = 'Shimoning\\ColorMeShopApi\\Services\\'
                . \str_replace([\DIRECTORY_SEPARATOR, '.php'], ['\\', ''], $relative);

            if (\in_array($class, $excluded, true) || ! \class_exists($class)) {
                continue;
            }
            $reflection = new ReflectionClass($class);
            if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Service::class)) {
                continue;
            }
            $classes[] = $class;
        }

        \sort($classes);
        return $classes;
    }

    /** @param list<AuthScope> $scopes */
    private function assertScopeDocumentation(ReflectionMethod $method, array $scopes): void
    {
        $document = $method->getDocComment();
        $this->assertIsString($document, $method->class . '::' . $method->name);

        if ($scopes === []) {
            $this->assertStringNotContainsString('必要な scope:', $document, $method->class . '::' . $method->name);
            return;
        }

        $references = \array_map(
            static fn(AuthScope $scope): string => '{@see \\' . AuthScope::class . '::' . $scope->name . '}',
            $scopes,
        );
        if (\count($scopes) === 1) {
            $scope = $scopes[0];
            $expected = \sprintf('必要な scope: `%s` (%s)', $scope->value, $references[0]);
        } else {
            $expected = \sprintf(
                "必要な scope: `%s` と `%s` の両方\n     * (%s)",
                $scopes[0]->value,
                $scopes[1]->value,
                \implode('、', $references),
            );
        }

        $message = $method->class . '::' . $method->name;
        $this->assertSame(1, \substr_count($document, $expected), $message);
        $this->assertStringContainsString("\n     *\n     * 必要な scope:", $document, $message);

        $scopePosition = \strpos($document, '必要な scope:');
        $this->assertNotFalse($scopePosition, $message);
        $this->assertDoesNotMatchRegularExpression(
            '/^\s*\*\s+@(?!see\b)/m',
            \substr($document, 0, $scopePosition),
            $message,
        );
    }
}
