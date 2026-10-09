<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities\Customer;

use Shimoning\ColorMeShopApi\Entities\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Customer\Points\PointsInput;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class CustomerPointsInputTest extends TestCase
{
    public function test_増減ポイント数をトップレベルのボディ形式へ変換する(): void
    {
        $input = new PointsInput(['points' => 100]);

        $this->assertInstanceOf(RequestEntity::class, $input);
        $this->assertSame(['points' => 100], $input->toArrayRecursive());
    }

    public function test_負の値は減算として送信する(): void
    {
        $this->assertSame(['points' => -50], (new PointsInput(['points' => -50]))->toArrayRecursive());
    }

    public function test_未指定は送信しない(): void
    {
        $this->assertSame([], (new PointsInput([]))->toArrayRecursive());
    }

    /**
     * 公式 OpenAPI で `points` は required かつ `nullable: false` のため、明示した null も拒否する。
     */
    public function test_nullは拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('points');

        new PointsInput(['points' => null]);
    }

    public function test_整数以外は拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('points');

        new PointsInput(['points' => '100']);
    }
}
