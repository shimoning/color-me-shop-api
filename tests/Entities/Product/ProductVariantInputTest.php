<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product;

use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Product\ProductStocksIncrementInput;
use Shimoning\ColorMeShopApi\Entities\Product\ProductVariantInput;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class ProductVariantInputTest extends TestCase
{
    public function test_配列と既存instanceを受け付け再帰的に直列化する(): void
    {
        $increment = new ProductStocksIncrementInput(['increment' => 2]);

        $fromArray = new ProductVariantInput([
            'option1_value' => 'S',
            'option2_value' => '赤',
            'stocks' => ['increment' => 1],
        ]);
        $fromInstance = new ProductVariantInput(['stocks' => $increment]);

        $this->assertInstanceOf(RequestEntity::class, $fromArray);
        $this->assertSame([
            'option1_value' => 'S',
            'option2_value' => '赤',
            'stocks' => ['increment' => 1],
        ], $fromArray->toArrayRecursive());
        $this->assertSame(['stocks' => ['increment' => 2]], $fromInstance->toArrayRecursive());
    }

    public function test_stocks整数をそのまま直列化する(): void
    {
        $this->assertSame(
            ['stocks' => 3],
            (new ProductVariantInput(['stocks' => 3]))->toArrayRecursive(),
        );
    }

    #[DataProvider('invalidInputProvider')]
    public function test_未知キー空要素stocks_nullを拒否する(array $data): void
    {
        $this->expectException(InvalidFieldException::class);

        new ProductVariantInput($data);
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function invalidInputProvider(): array
    {
        return [
            'empty' => [[]],
            'unknown only' => [['weight' => 1]],
            'extra key' => [['option1_value' => 'S', 'weight' => 1]],
            'stocks null' => [['stocks' => null]],
        ];
    }
}
