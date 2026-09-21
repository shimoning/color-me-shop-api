<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product;

use Shimoning\ColorMeShopApi\Constants\PickupType;
use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Product\PickupInput;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class PickupInputTest extends TestCase
{
    public function test_種別と表示順をトップレベルのボディ形式へ変換する(): void
    {
        $input = new PickupInput(['pickup_type' => 3, 'order_num' => 1]);

        $this->assertInstanceOf(RequestEntity::class, $input);
        $this->assertSame(PickupType::NEW_ARRIVAL, $input->toArray()['pickup_type']);
        $this->assertSame(['pickup_type' => 3, 'order_num' => 1], $input->toArrayRecursive());
    }

    public function test_未指定は送信せず明示したnullは送信する(): void
    {
        $this->assertSame([], (new PickupInput([]))->toArrayRecursive());
        $this->assertSame(
            ['pickup_type' => 0, 'order_num' => null],
            (new PickupInput(['pickup_type' => 0, 'order_num' => null]))->toArrayRecursive(),
        );
    }

    public function test_種別はenumインスタンスでも指定できバッキング値で送信する(): void
    {
        $input = new PickupInput(['pickup_type' => PickupType::NEW_ARRIVAL, 'order_num' => 1]);

        $this->assertSame(PickupType::NEW_ARRIVAL, $input->toArray()['pickup_type']);
        $this->assertSame(['pickup_type' => 3, 'order_num' => 1], $input->toArrayRecursive());
    }

    public function test_別のenumのインスタンスは拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('pickup_type');
        new PickupInput(['pickup_type' => ProductDisplayState::HIDDEN]);
    }

    public function test_未定義の種別は要求側なので拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('pickup_type');
        new PickupInput(['pickup_type' => 2]);
    }

    public function test_不正な型を拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('order_num');
        new PickupInput(['order_num' => '1']);
    }
}
