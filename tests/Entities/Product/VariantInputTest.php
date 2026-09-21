<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product;

use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Product\VariantInput;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class VariantInputTest extends TestCase
{
    public function test_全フィールドをvariantボディ形式へ変換する(): void
    {
        $input = new VariantInput([
            'stocks' => 5,
            'few_num' => 2,
            'model_number' => 'T-223-S',
            'weight' => 300,
            'option_price' => 1600,
            'option_members_price' => 1500,
            'option_market_price' => 2000,
            'option_cost' => 800,
        ]);

        $this->assertInstanceOf(RequestEntity::class, $input);
        $this->assertSame([
            'stocks' => 5,
            'few_num' => 2,
            'model_number' => 'T-223-S',
            'weight' => 300,
            'option_price' => 1600,
            'option_members_price' => 1500,
            'option_market_price' => 2000,
            'option_cost' => 800,
        ], $input->toArrayRecursive());
    }

    public function test_未指定は送信せず明示したnullは未設定へ戻す要求として送信する(): void
    {
        $this->assertSame([], (new VariantInput([]))->toArrayRecursive());
        $this->assertSame(
            ['stocks' => 3, 'weight' => null],
            (new VariantInput(['stocks' => 3, 'weight' => null]))->toArrayRecursive(),
        );
    }

    public function test_不正な型を拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('option_price');
        new VariantInput(['option_price' => '1600']);
    }
}
