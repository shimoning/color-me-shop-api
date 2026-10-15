<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Customer\Customer;
use Shimoning\ColorMeShopApi\Entities\Sale\Delivery;
use Shimoning\ColorMeShopApi\Entities\Sale\DeliveryUpdateInput;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

class NullableObjectFieldCompatibilityTest extends TestCase
{
    /**
     * @param class-string<Customer|Delivery|DeliveryUpdateInput> $class
     */
    #[DataProvider('nullableFuriganaProvider')]
    public function test_furiganaのnullableとallowNullはそれぞれのfalsy値の扱いを保つ(
        string $class,
        mixed $value,
    ): void {
        if ($class === DeliveryUpdateInput::class) {
            if ($value === '') {
                $this->assertSame(
                    ['furigana' => ''],
                    (new DeliveryUpdateInput(['furigana' => $value]))->toArrayRecursive(),
                );
                return;
            }

            $this->expectException(InvalidFieldException::class);
            $this->expectExceptionMessage('furigana');

            new DeliveryUpdateInput(['furigana' => $value]);
            return;
        }

        $entity = new $class(['furigana' => $value]);
        $this->assertNull($entity->getFurigana());
    }

    /**
     * @return array<string, array{class-string<Customer|Delivery|DeliveryUpdateInput>, mixed}>
     */
    public static function nullableFuriganaProvider(): array
    {
        $cases = [];
        foreach ([
            'Customer' => Customer::class,
            'Delivery' => Delivery::class,
            'DeliveryUpdateInput' => DeliveryUpdateInput::class,
        ] as $name => $class) {
            foreach (['空文字' => '', '文字列0' => '0', '整数0' => 0, 'float0' => 0.0, 'false' => false, '空配列' => []] as $label => $value) {
                $cases[$name . ' / ' . $label] = [$class, $value];
            }
        }

        return $cases;
    }
}
