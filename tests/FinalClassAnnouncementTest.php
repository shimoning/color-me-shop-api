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

    /** @var list<class-string> */
    private const FINAL_ANNOUNCEMENT_CLASSES = [
        Errors::class,
        \Shimoning\ColorMeShopApi\Communicator\Request::class,
        \Shimoning\ColorMeShopApi\Communicator\RequestMeta::class,
        \Shimoning\ColorMeShopApi\Communicator\RequestOptions::class,
        \Shimoning\ColorMeShopApi\Communicator\Response::class,
        \Shimoning\ColorMeShopApi\Entities\Common\MetaTag::class,
        \Shimoning\ColorMeShopApi\Entities\Common\MetaTagInput::class,
        \Shimoning\ColorMeShopApi\Entities\Customer\Customer::class,
        \Shimoning\ColorMeShopApi\Entities\Customer\CustomerCreateInput::class,
        \Shimoning\ColorMeShopApi\Entities\Customer\CustomerUpdateInput::class,
        \Shimoning\ColorMeShopApi\Entities\Customer\ExternalAccount::class,
        \Shimoning\ColorMeShopApi\Entities\Customer\Membership\AggregationPeriod::class,
        \Shimoning\ColorMeShopApi\Entities\Customer\Membership\Membership::class,
        \Shimoning\ColorMeShopApi\Entities\Customer\Membership\NextMembership::class,
        \Shimoning\ColorMeShopApi\Entities\Customer\Membership\Progress::class,
        \Shimoning\ColorMeShopApi\Entities\Customer\Points\Points::class,
        \Shimoning\ColorMeShopApi\Entities\Customer\Points\PointsInput::class,
        \Shimoning\ColorMeShopApi\Entities\Customer\SearchParameters::class,
        \Shimoning\ColorMeShopApi\Entities\Delivery\Area::class,
        \Shimoning\ColorMeShopApi\Entities\Delivery\Charge::class,
        \Shimoning\ColorMeShopApi\Entities\Delivery\Date\Date::class,
        \Shimoning\ColorMeShopApi\Entities\Delivery\Date\Days::class,
        \Shimoning\ColorMeShopApi\Entities\Delivery\Date\Times::class,
        \Shimoning\ColorMeShopApi\Entities\Delivery\Delivery::class,
        \Shimoning\ColorMeShopApi\Entities\Delivery\Price::class,
        \Shimoning\ColorMeShopApi\Entities\Delivery\Weight::class,
        \Shimoning\ColorMeShopApi\Entities\Error::class,
        \Shimoning\ColorMeShopApi\Entities\Gift\Card::class,
        \Shimoning\ColorMeShopApi\Entities\Gift\Gift::class,
        \Shimoning\ColorMeShopApi\Entities\Gift\Noshi::class,
        \Shimoning\ColorMeShopApi\Entities\Gift\Type::class,
        \Shimoning\ColorMeShopApi\Entities\Gift\Wrapping::class,
        \Shimoning\ColorMeShopApi\Entities\OAuth\AccessToken::class,
        \Shimoning\ColorMeShopApi\Entities\OAuth\ErrorResponse::class,
        \Shimoning\ColorMeShopApi\Entities\Page::class,
        \Shimoning\ColorMeShopApi\Entities\Pagination::class,
        \Shimoning\ColorMeShopApi\Entities\Payment\Brand::class,
        \Shimoning\ColorMeShopApi\Entities\Payment\Card::class,
        \Shimoning\ColorMeShopApi\Entities\Payment\Cod::class,
        \Shimoning\ColorMeShopApi\Entities\Payment\CodFee::class,
        \Shimoning\ColorMeShopApi\Entities\Payment\Financial::class,
        \Shimoning\ColorMeShopApi\Entities\Payment\Payment::class,
        \Shimoning\ColorMeShopApi\Entities\Product\Advertising\Advertising::class,
        \Shimoning\ColorMeShopApi\Entities\Product\Advertising\SearchParameters::class,
        \Shimoning\ColorMeShopApi\Entities\Product\Category\BigCategory::class,
        \Shimoning\ColorMeShopApi\Entities\Product\Category\CategoryInput::class,
        \Shimoning\ColorMeShopApi\Entities\Product\Category\ChildInput::class,
        \Shimoning\ColorMeShopApi\Entities\Product\Category\SmallCategory::class,
        \Shimoning\ColorMeShopApi\Entities\Product\CategoryIds::class,
        \Shimoning\ColorMeShopApi\Entities\Product\Group\Group::class,
        \Shimoning\ColorMeShopApi\Entities\Product\Group\GroupInput::class,
        \Shimoning\ColorMeShopApi\Entities\Product\Image::class,
        \Shimoning\ColorMeShopApi\Entities\Product\Image\Image::class,
        \Shimoning\ColorMeShopApi\Entities\Product\Option\Option::class,
        \Shimoning\ColorMeShopApi\Entities\Product\Option\OptionCreateInput::class,
        \Shimoning\ColorMeShopApi\Entities\Product\Option\Value\Value::class,
        \Shimoning\ColorMeShopApi\Entities\Product\Option\Value\ValueCreateInput::class,
        \Shimoning\ColorMeShopApi\Entities\Product\Pickup\Pickup::class,
        \Shimoning\ColorMeShopApi\Entities\Product\Pickup\PickupInput::class,
        \Shimoning\ColorMeShopApi\Entities\Product\Product::class,
        \Shimoning\ColorMeShopApi\Entities\Product\ProductCreateInput::class,
        \Shimoning\ColorMeShopApi\Entities\Product\ProductUpdateInput::class,
        \Shimoning\ColorMeShopApi\Entities\Product\SearchParameters::class,
        \Shimoning\ColorMeShopApi\Entities\Product\Stock\SearchParameters::class,
        \Shimoning\ColorMeShopApi\Entities\Product\Stock\Stock::class,
        \Shimoning\ColorMeShopApi\Entities\Product\StocksIncrementInput::class,
        \Shimoning\ColorMeShopApi\Entities\Product\Variant\Option::class,
        \Shimoning\ColorMeShopApi\Entities\Product\Variant\SearchParameters::class,
        \Shimoning\ColorMeShopApi\Entities\Product\Variant\Variant::class,
        \Shimoning\ColorMeShopApi\Entities\Product\Variant\VariantUpdateInput::class,
        \Shimoning\ColorMeShopApi\Entities\Product\VariantInput::class,
        \Shimoning\ColorMeShopApi\Entities\Sale\Application::class,
        \Shimoning\ColorMeShopApi\Entities\Sale\CustomerCreateInput::class,
        \Shimoning\ColorMeShopApi\Entities\Sale\Customization::class,
        \Shimoning\ColorMeShopApi\Entities\Sale\Delivery::class,
        \Shimoning\ColorMeShopApi\Entities\Sale\DeliveryCreateInput::class,
        \Shimoning\ColorMeShopApi\Entities\Sale\DeliveryUpdateInput::class,
        \Shimoning\ColorMeShopApi\Entities\Sale\Detail::class,
        \Shimoning\ColorMeShopApi\Entities\Sale\DetailCreateInput::class,
        \Shimoning\ColorMeShopApi\Entities\Sale\Sale::class,
        \Shimoning\ColorMeShopApi\Entities\Sale\SaleCreateInput::class,
        \Shimoning\ColorMeShopApi\Entities\Sale\SaleUpdateInput::class,
        \Shimoning\ColorMeShopApi\Entities\Sale\SearchParameters::class,
        \Shimoning\ColorMeShopApi\Entities\Sale\Segment::class,
        \Shimoning\ColorMeShopApi\Entities\Sale\ShopCoupon::class,
        \Shimoning\ColorMeShopApi\Entities\Sale\Stat\Stat::class,
        \Shimoning\ColorMeShopApi\Entities\Sale\Totals::class,
        \Shimoning\ColorMeShopApi\Entities\Shop\Shop::class,
        \Shimoning\ColorMeShopApi\Exceptions\InvalidPaginationException::class,
        \Shimoning\ColorMeShopApi\Exceptions\MissingPaginationException::class,
        \Shimoning\ColorMeShopApi\Exceptions\ParameterException::class,
        \Shimoning\ColorMeShopApi\Services\OAuth\Options::class,
        \Shimoning\ColorMeShopApi\Values\DateTime::class,
        \Shimoning\ColorMeShopApi\Values\Furigana::class,
        \Shimoning\ColorMeShopApi\Values\Scopes::class,
    ];

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

    public function test_既存の具象クラスはfinal化の予告を持ちまだfinalではない(): void
    {
        $this->assertCount(95, self::FINAL_ANNOUNCEMENT_CLASSES);

        foreach (self::FINAL_ANNOUNCEMENT_CLASSES as $class) {
            $reflection = new ReflectionClass($class);
            $this->assertFalse($reflection->isAbstract(), $class);
            $this->assertFalse($reflection->isFinal(), $class);

            $document = $reflection->getDocComment();
            $this->assertIsString($document, $class);
            $this->assertStringContainsString(self::FINAL_ANNOTATION, $document, $class);
        }
    }

    public function test_予告対象にも例外にもない具象クラスはfinalである(): void
    {
        foreach (self::sourceClasses() as $class => $reflection) {
            if (
                $reflection->isAbstract()
                || \in_array($class, self::FINAL_ANNOUNCEMENT_CLASSES, true)
                || \in_array($class, self::FINALITY_EXCEPTIONS, true)
            ) {
                continue;
            }

            $this->assertTrue($reflection->isFinal(), $class);
        }
    }

    public function test_final化しない例外はfinalでも予告対象でもない(): void
    {
        foreach (self::FINALITY_EXCEPTIONS as $class) {
            $reflection = new ReflectionClass($class);
            $this->assertFalse($reflection->isAbstract(), $class);
            $this->assertFalse($reflection->isFinal(), $class);

            $document = $reflection->getDocComment();
            if ($document !== false) {
                $this->assertStringNotContainsString('@final', $document, $class);
            }
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
