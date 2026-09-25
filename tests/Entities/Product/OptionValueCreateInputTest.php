<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product;

use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Product\OptionValueCreateInput;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class OptionValueCreateInputTest extends TestCase
{
    public function test_名前をoption_valueボディ形式へ変換する(): void
    {
        $input = new OptionValueCreateInput(['name' => 'L']);

        $this->assertInstanceOf(RequestEntity::class, $input);
        $this->assertSame(['name' => 'L'], $input->toArrayRecursive());
        $this->assertSame([], (new OptionValueCreateInput([]))->toArrayRecursive());
    }

    public function test_必須のnameはnullを受け付けない(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('name');
        new OptionValueCreateInput(['name' => null]);
    }
}
