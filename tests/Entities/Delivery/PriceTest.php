<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Delivery;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Delivery\Price;

class PriceTest extends TestCase
{
    public function test_意味付きフィールドを取得し配列化する(): void
    {
        $priceCharge = new Price(['upper_limit' => 3000, 'charge' => 500]);

        $this->assertSame(3000, $priceCharge->getUpperLimit());
        $this->assertSame(500, $priceCharge->getCharge());
        $this->assertSame(['upper_limit' => 3000, 'charge' => 500], $priceCharge->toArray());
        $this->assertSame(['upper_limit' => 3000, 'charge' => 500], $priceCharge->toArrayRecursive());
    }
}
