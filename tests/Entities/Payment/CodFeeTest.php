<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Payment;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Payment\CodFee;

class CodFeeTest extends TestCase
{
    public function test_意味付きフィールドを取得し配列化する(): void
    {
        $fee = new CodFee(['upper_limit' => 300, 'fee' => 100]);

        $this->assertSame(300, $fee->getUpperLimit());
        $this->assertSame(100, $fee->getFee());
        $this->assertSame(['upper_limit' => 300, 'fee' => 100], $fee->toArray());
        $this->assertSame(['upper_limit' => 300, 'fee' => 100], $fee->toArrayRecursive());
    }
}
