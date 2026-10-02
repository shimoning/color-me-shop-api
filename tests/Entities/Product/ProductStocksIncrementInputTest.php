<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product;

use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Product\ProductStocksIncrementInput;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class ProductStocksIncrementInputTest extends TestCase
{
    public function test_incrementだけを受け付けて直列化する(): void
    {
        $input = new ProductStocksIncrementInput(['increment' => -2]);

        $this->assertInstanceOf(RequestEntity::class, $input);
        $this->assertSame(['increment' => -2], $input->toArrayRecursive());
    }

    #[DataProvider('invalidInputProvider')]
    public function test_未知キー欠損不正型を拒否する(array $data): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('increment');

        new ProductStocksIncrementInput($data);
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function invalidInputProvider(): array
    {
        return [
            'empty' => [[]],
            'unknown only' => [['incr' => 1]],
            'extra key' => [['increment' => 1, 'extra' => 2]],
            'string' => [['increment' => '1']],
            'null' => [['increment' => null]],
        ];
    }
}
