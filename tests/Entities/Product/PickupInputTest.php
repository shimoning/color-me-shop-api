<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product;

use Shimoning\ColorMeShopApi\Constants\Product\Pickup\Type as PickupType;
use Shimoning\ColorMeShopApi\Constants\Product\DisplayState;
use Shimoning\ColorMeShopApi\Entities\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Product\Pickup\PickupInput;
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

    public function test_未指定はクラス名と必須一覧と欠落一覧を示して拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(PickupInput::class);
        $this->expectExceptionMessage('必須: pickup_type, order_num');
        $this->expectExceptionMessage('未指定: pickup_type, order_num');

        new PickupInput([]);
    }

    public function test_明示したnullは必須指定として扱い送信する(): void
    {
        $this->assertSame(
            ['pickup_type' => null, 'order_num' => null],
            (new PickupInput(['pickup_type' => null, 'order_num' => null]))->toArrayRecursive(),
        );
    }

    public function test_種別はenumインスタンスでも指定できバッキング値で送信する(): void
    {
        $input = new PickupInput(['pickup_type' => PickupType::NEW_ARRIVAL, 'order_num' => 1]);

        $this->assertSame(PickupType::NEW_ARRIVAL, $input->toArray()['pickup_type']);
        $this->assertSame(['pickup_type' => 3, 'order_num' => 1], $input->toArrayRecursive());
    }

    public function test_必須フィールドは現在受け付けているcamelCaseでも指定できる(): void
    {
        $input = new PickupInput(['pickupType' => 3, 'orderNum' => 1]);

        $this->assertSame(['pickup_type' => 3, 'order_num' => 1], $input->toArrayRecursive());
    }

    public function test_別のenumのインスタンスは拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('pickup_type');
        new PickupInput(['pickup_type' => DisplayState::HIDDEN, 'order_num' => 1]);
    }

    public function test_未定義の種別は要求側なので拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('pickup_type');
        new PickupInput(['pickup_type' => 2, 'order_num' => 1]);
    }

    public function test_不正な型を拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('order_num');
        new PickupInput(['pickup_type' => 3, 'order_num' => '1']);
    }
}
