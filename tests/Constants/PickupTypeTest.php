<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Constants;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Constants\FallbackEnum;
use Shimoning\ColorMeShopApi\Constants\PickupType;

class PickupTypeTest extends TestCase
{
    public function test_公式OpenAPIのpickup_typeと同じ整数値を持つ(): void
    {
        $this->assertSame(0, PickupType::RECOMMENDED->value);
        $this->assertSame(1, PickupType::BEST_SELLER->value);
        $this->assertSame(3, PickupType::NEW_ARRIVAL->value);
        $this->assertSame(4, PickupType::FEATURED->value);
        $this->assertSame([0, 1, 3, 4], \array_map(static fn(PickupType $type): int => $type->value, PickupType::cases()));
    }

    public function test_未定義の2は要求側なのでフォールバックしない(): void
    {
        $this->assertNull(PickupType::tryFrom(2));
        $this->assertNotInstanceOf(FallbackEnum::class, PickupType::RECOMMENDED);
    }
}
