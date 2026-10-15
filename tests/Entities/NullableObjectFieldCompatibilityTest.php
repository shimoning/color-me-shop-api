<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Customer\Customer;
use Shimoning\ColorMeShopApi\Entities\Sale\Delivery;
use Shimoning\ColorMeShopApi\Entities\Sale\DeliveryUpdateInput;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Values\Furigana;

class NullableObjectFieldCompatibilityTest extends TestCase
{
    /**
     * @param class-string<Customer|Delivery> $class
     */
    #[DataProvider('responseFuriganaProvider')]
    public function test_応答側furiganaは空文字を値オブジェクトとして保持しnullだけをnullとして扱う(
        string $class,
        mixed $value,
    ): void {
        $entity = new $class(['furigana' => $value]);

        if ($value === null) {
            $this->assertNull($entity->getFurigana());
            return;
        }

        $furigana = $entity->getFurigana();
        $this->assertInstanceOf(Furigana::class, $furigana);
        $this->assertSame('', $furigana->get());
    }

    /**
     * @return array<string, array{class-string<Customer|Delivery>, mixed}>
     */
    public static function responseFuriganaProvider(): array
    {
        $cases = [];
        foreach ([
            'Customer' => Customer::class,
            'Delivery' => Delivery::class,
        ] as $name => $class) {
            foreach (['空文字' => '', 'null' => null] as $label => $value) {
                $cases[$name . ' / ' . $label] = [$class, $value];
            }
        }

        return $cases;
    }

    /** @param class-string<Customer|Delivery> $class */
    #[DataProvider('fallbackResponseFuriganaProvider')]
    public function test_応答側furiganaは形式不正な文字列0をフォールバック値として保持する(
        string $class,
    ): void {
        $furigana = (new $class(['furigana' => '0']))->getFurigana();

        $this->assertInstanceOf(Furigana::class, $furigana);
        $this->assertSame('0', $furigana->get());
        $this->assertFalse($furigana->isValid());
    }

    /** @return array<string, array{class-string<Customer|Delivery>}> */
    public static function fallbackResponseFuriganaProvider(): array
    {
        return [
            'Customer' => [Customer::class],
            'Delivery' => [Delivery::class],
        ];
    }

    /** @param class-string<Customer|Delivery> $class */
    #[DataProvider('invalidResponseFuriganaProvider')]
    public function test_応答側furiganaはnull以外の非文字列falsy値を拒否する(
        string $class,
        mixed $value,
    ): void {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('furigana');

        new $class(['furigana' => $value]);
    }

    /** @return array<string, array{class-string<Customer|Delivery>, mixed}> */
    public static function invalidResponseFuriganaProvider(): array
    {
        $cases = [];
        foreach (['Customer' => Customer::class, 'Delivery' => Delivery::class] as $name => $class) {
            foreach (['整数0' => 0, 'float0' => 0.0, 'false' => false, '空配列' => []] as $label => $value) {
                $cases[$name . ' / ' . $label] = [$class, $value];
            }
        }

        return $cases;
    }

    #[DataProvider('deliveryUpdateInputFalsyFuriganaProvider')]
    public function test_DeliveryUpdateInputのfuriganaは従来どおり空文字だけを許可する(
        mixed $value,
    ): void {
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
    }

    /** @return array<string, array{mixed}> */
    public static function deliveryUpdateInputFalsyFuriganaProvider(): array
    {
        return [
            '空文字' => [''],
            '文字列0' => ['0'],
            '整数0' => [0],
            'float0' => [0.0],
            'false' => [false],
            '空配列' => [[]],
        ];
    }
}
