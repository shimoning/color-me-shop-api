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
use Shimoning\ColorMeShopApi\Services\Payment;
use Shimoning\ColorMeShopApi\Services\Product;
use Shimoning\ColorMeShopApi\Services\Sales;
use Shimoning\ColorMeShopApi\Services\Shop;

class AuthScopeDocumentationTest extends TestCase
{
    /**
     * @return array<string, array{class-string, string, string, list<AuthScope>}>
     */
    public static function apiMethodProvider(): array
    {
        return [
            'Customer::page' => [Customer::class, 'page', 'getCustomers', [AuthScope::READ_SALES]],
            'Customer::one' => [Customer::class, 'one', 'getCustomer', [AuthScope::READ_SALES]],
            'Customer::create' => [Customer::class, 'create', 'createCustomer', [AuthScope::WRITE_SALES]],
            'Customer::update' => [Customer::class, 'update', 'updateCustomer', [AuthScope::WRITE_SALES]],
            'Customer::changePoints' => [Customer::class, 'changePoints', 'changeCustomerPoints', [AuthScope::WRITE_SALES]],
            'Delivery::all' => [Delivery::class, 'all', 'getDeliveries', []],
            'Payment::all' => [Payment::class, 'all', 'getPayments', []],
            'Product::products' => [Product::class, 'products', 'getProducts', [AuthScope::READ_PRODUCTS]],
            'Product::product' => [Product::class, 'product', 'getProduct', [AuthScope::READ_PRODUCTS]],
            'Product::variants' => [Product::class, 'variants', 'getProductVariants', [AuthScope::READ_PRODUCTS]],
            'Product::variant' => [Product::class, 'variant', 'getProductVariant', [AuthScope::READ_PRODUCTS]],
            'Product::images' => [Product::class, 'images', 'getProductImages', [AuthScope::READ_PRODUCTS]],
            'Product::advertisings' => [Product::class, 'advertisings', 'getProductAdvertisings', [AuthScope::READ_PRODUCTS]],
            'Product::group' => [Product::class, 'group', 'getProductGroup', []],
            'Product::groups' => [Product::class, 'groups', 'getProductGroups', []],
            'Product::categories' => [Product::class, 'categories', 'getProductCategories', []],
            'Product::createGroup' => [Product::class, 'createGroup', 'createProductGroup', [AuthScope::WRITE_PRODUCTS]],
            'Product::updateGroup' => [Product::class, 'updateGroup', 'updateProductGroup', [AuthScope::WRITE_PRODUCTS]],
            'Product::createCategory' => [Product::class, 'createCategory', 'createProductCategory', [AuthScope::WRITE_PRODUCTS]],
            'Product::updateCategory' => [Product::class, 'updateCategory', 'updateProductCategory', [AuthScope::WRITE_PRODUCTS]],
            'Product::createCategoryChild' => [Product::class, 'createCategoryChild', 'createProductCategoryChild', [AuthScope::WRITE_PRODUCTS]],
            'Product::updateCategoryChild' => [Product::class, 'updateCategoryChild', 'updateProductCategoryChild', [AuthScope::WRITE_PRODUCTS]],
            'Product::create' => [Product::class, 'create', 'createProduct', [AuthScope::WRITE_PRODUCTS]],
            'Product::update' => [Product::class, 'update', 'updateProduct', [AuthScope::WRITE_PRODUCTS]],
            'Product::updateVariant' => [Product::class, 'updateVariant', 'updateProductVariant', [AuthScope::WRITE_PRODUCTS]],
            'Product::createOption' => [Product::class, 'createOption', 'createProductOption', [AuthScope::WRITE_PRODUCTS]],
            'Product::deleteOption' => [Product::class, 'deleteOption', 'deleteProductOption', [AuthScope::WRITE_PRODUCTS]],
            'Product::createOptionValue' => [Product::class, 'createOptionValue', 'createProductOptionValue', [AuthScope::WRITE_PRODUCTS]],
            'Product::deleteOptionValue' => [Product::class, 'deleteOptionValue', 'deleteProductOptionValue', [AuthScope::WRITE_PRODUCTS]],
            'Product::createPickup' => [Product::class, 'createPickup', 'createProductPickup', [AuthScope::WRITE_PRODUCTS]],
            'Product::updatePickup' => [Product::class, 'updatePickup', 'updateProductPickup', [AuthScope::WRITE_PRODUCTS]],
            'Product::deletePickup' => [Product::class, 'deletePickup', 'deleteProductPickup', [AuthScope::WRITE_PRODUCTS]],
            'Product::createImage' => [Product::class, 'createImage', 'createProductImage', [AuthScope::READ_PRODUCTS, AuthScope::WRITE_PRODUCTS]],
            'Product::deleteImage' => [Product::class, 'deleteImage', 'deleteProductImage', [AuthScope::WRITE_PRODUCTS]],
            'Sales::page' => [Sales::class, 'page', 'getSales', [AuthScope::READ_SALES]],
            'Sales::one' => [Sales::class, 'one', 'getSale', [AuthScope::READ_SALES]],
            'Sales::stat' => [Sales::class, 'stat', 'statSales', [AuthScope::READ_SALES]],
            'Sales::update' => [Sales::class, 'update', 'updateSale', [AuthScope::WRITE_SALES]],
            'Sales::cancel' => [Sales::class, 'cancel', 'cancelSale', [AuthScope::WRITE_SALES]],
            'Sales::sendMail' => [Sales::class, 'sendMail', 'sendSalesMail', [AuthScope::WRITE_SALES]],
            'Shop::get' => [Shop::class, 'get', 'getShop', []],
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
        $providedServices = [];
        $providedClientMethods = [];
        foreach (self::apiMethodProvider() as [$serviceClass, $serviceMethod, $clientMethod]) {
            $providedServices[] = $serviceClass . '::' . $serviceMethod;
            $providedClientMethods[] = $clientMethod;
        }

        $declaredServices = [];
        foreach ([Customer::class, Delivery::class, Payment::class, Product::class, Sales::class, Shop::class] as $class) {
            foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() === $class) {
                    $declaredServices[] = $class . '::' . $method->getName();
                }
            }
        }

        $declaredClientMethods = [];
        foreach ((new ReflectionClass(Client::class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // OAuth 認証フローとコンストラクタは Service API のファサードではないため対象外。
            if (! \in_array($method->getName(), ['__construct', 'getOAuthUrl', 'exchangeCode2Token'], true)) {
                $declaredClientMethods[] = $method->getName();
            }
        }

        \sort($providedServices);
        \sort($declaredServices);
        \sort($providedClientMethods);
        \sort($declaredClientMethods);

        $this->assertSame($declaredServices, $providedServices);
        $this->assertSame($declaredClientMethods, $providedClientMethods);
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
