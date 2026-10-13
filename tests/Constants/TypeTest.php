<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Constants;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Constants\FallbackEnum;
use Shimoning\ColorMeShopApi\Constants\Product\Pickup\Type;

class TypeTest extends TestCase
{
    public function test_公式OpenAPIのpickup_typeと同じ整数値を持つ(): void
    {
        $this->assertSame(0, Type::RECOMMENDED->value);
        $this->assertSame(1, Type::BEST_SELLER->value);
        $this->assertSame(3, Type::NEW_ARRIVAL->value);
        $this->assertSame(4, Type::FEATURED->value);
        $this->assertSame([0, 1, 3, 4], \array_map(static fn(Type $type): int => $type->value, Type::cases()));
    }

    public function test_未定義の2は要求側なのでフォールバックしない(): void
    {
        $this->assertNull(Type::tryFrom(2));
        $this->assertNotInstanceOf(FallbackEnum::class, Type::RECOMMENDED);
    }
}
