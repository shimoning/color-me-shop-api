<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Shimoning\ColorMeShopApi\Client;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Exceptions\ColorMeApiException;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;
use Shimoning\ColorMeShopApi\Services\Customer as CustomerService;
use Shimoning\ColorMeShopApi\Services\Delivery as DeliveryService;
use Shimoning\ColorMeShopApi\Services\Gift as GiftService;
use Shimoning\ColorMeShopApi\Services\OAuth;
use Shimoning\ColorMeShopApi\Services\Payment as PaymentService;
use Shimoning\ColorMeShopApi\Services\Product as ProductService;
use Shimoning\ColorMeShopApi\Services\Product\Advertising as ProductAdvertisingService;
use Shimoning\ColorMeShopApi\Services\Product\Category as ProductCategoryService;
use Shimoning\ColorMeShopApi\Services\Product\Group as ProductGroupService;
use Shimoning\ColorMeShopApi\Services\Product\Image as ProductImageService;
use Shimoning\ColorMeShopApi\Services\Product\Option as ProductOptionService;
use Shimoning\ColorMeShopApi\Services\Product\Option\Value as ProductOptionValueService;
use Shimoning\ColorMeShopApi\Services\Product\Pickup as ProductPickupService;
use Shimoning\ColorMeShopApi\Services\Product\Stock as ProductStockService;
use Shimoning\ColorMeShopApi\Services\Product\Variant as ProductVariantService;
use Shimoning\ColorMeShopApi\Services\Sale as SaleService;
use Shimoning\ColorMeShopApi\Services\Shop as ShopService;
use Shimoning\ColorMeShopApi\Services\Stock as StockService;
use Shimoning\ColorMeShopApi\Values\Limit;
use Shimoning\ColorMeShopApi\Values\Sort;

class FinalClassAnnouncementTest extends TestCase
{
    private const FINAL_ANNOTATION = '@final 0.32.0 で予告。次のメジャーな版で final にする。';

    /**
     * 利用者によるモックまたはライブラリ内の継承を維持するため、final 化しない具象クラス。
     *
     * @var list<class-string>
     */
    private const FINALITY_EXCEPTIONS = [
        Client::class,
        CustomerService::class,
        DeliveryService::class,
        GiftService::class,
        OAuth::class,
        PaymentService::class,
        ProductService::class,
        ProductAdvertisingService::class,
        ProductCategoryService::class,
        ProductGroupService::class,
        ProductImageService::class,
        ProductOptionService::class,
        ProductOptionValueService::class,
        ProductPickupService::class,
        ProductStockService::class,
        ProductVariantService::class,
        SaleService::class,
        ShopService::class,
        StockService::class,
        Collection::class,
        Limit::class,
        Sort::class,
        ColorMeApiException::class,
        InvalidFieldException::class,
        MissingFieldException::class,
    ];

    /** @var list<class-string> */
    private const EXTENDED_CONCRETE_CLASSES = [
        Collection::class,
        Limit::class,
        Sort::class,
        ColorMeApiException::class,
        InvalidFieldException::class,
        MissingFieldException::class,
    ];

    public function test_例外でない具象クラスはfinalまたはfinal化の予告を持つ(): void
    {
        foreach (self::sourceClasses() as $class => $reflection) {
            if (
                $reflection->isAbstract()
                || $reflection->isFinal()
                || \in_array($class, self::FINALITY_EXCEPTIONS, true)
            ) {
                continue;
            }

            $document = $reflection->getDocComment();
            $this->assertIsString($document, $class);
            $this->assertStringContainsString(self::FINAL_ANNOTATION, $document, $class);
        }
    }

    public function test_src内で継承される具象クラスと例外リストが一致する(): void
    {
        $sourceClasses = self::sourceClasses();
        $extendedConcreteClasses = [];

        foreach ($sourceClasses as $reflection) {
            $parent = $reflection->getParentClass();
            if (
                $parent !== false
                && isset($sourceClasses[$parent->getName()])
                && ! $parent->isAbstract()
            ) {
                $extendedConcreteClasses[] = $parent->getName();
            }
        }

        $extendedConcreteClasses = \array_values(\array_unique($extendedConcreteClasses));
        \sort($extendedConcreteClasses);
        $expected = self::EXTENDED_CONCRETE_CLASSES;
        \sort($expected);

        $this->assertSame($expected, $extendedConcreteClasses);
    }

    /**
     * @return array<class-string, ReflectionClass<object>>
     */
    private static function sourceClasses(): array
    {
        $base = \realpath(__DIR__ . '/../src');
        self::assertIsString($base);

        $classes = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base));
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relative = \substr($file->getPathname(), \strlen($base) + 1, -4);
            $class = 'Shimoning\\ColorMeShopApi\\'
                . \str_replace(\DIRECTORY_SEPARATOR, '\\', $relative);
            if (! \class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);
            if ($reflection->isEnum()) {
                continue;
            }

            $classes[$class] = $reflection;
        }

        \ksort($classes);
        return $classes;
    }
}
