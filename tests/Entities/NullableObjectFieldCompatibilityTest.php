<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Customer\Customer;
use Shimoning\ColorMeShopApi\Entities\Sales\SaleDelivery;
use Shimoning\ColorMeShopApi\Entities\Sales\SaleDeliveryUpdater;

class NullableObjectFieldCompatibilityTest extends TestCase
{
    /**
     * @param class-string<Customer|SaleDelivery|SaleDeliveryUpdater> $class
     */
    #[DataProvider('nullableFuriganaProvider')]
    public function test_nullableなOBJECT_FIELDSは従来どおりfalsy値をnullとして扱う(
        string $class,
        mixed $value,
    ): void {
        $entity = new $class(['furigana' => $value]);

        $this->assertNull($entity->getFurigana());
    }

    /**
     * @return array<string, array{class-string<Customer|SaleDelivery|SaleDeliveryUpdater>, mixed}>
     */
    public static function nullableFuriganaProvider(): array
    {
        $cases = [];
        foreach ([
            'Customer' => Customer::class,
            'SaleDelivery' => SaleDelivery::class,
            'SaleDeliveryUpdater' => SaleDeliveryUpdater::class,
        ] as $name => $class) {
            foreach (['空文字' => '', '文字列0' => '0', '整数0' => 0, 'float0' => 0.0, 'false' => false, '空配列' => []] as $label => $value) {
                $cases[$name . ' / ' . $label] = [$class, $value];
            }
        }

        return $cases;
    }
}
