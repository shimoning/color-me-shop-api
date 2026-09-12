<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Sales;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Sales\Stat;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;

class StatTest extends TestCase
{
    public function test_集計値が欠損していればgetter呼び出し時に固有例外になる(): void
    {
        $stat = new Stat([]);

        $this->expectException(MissingFieldException::class);
        $this->expectExceptionMessage(
            Stat::class . ' の API フィールド『amount_today』が欠損しています。',
        );

        $stat->getAmountToday();
    }

    public function test_完全な売上集計を従来どおり取得できる(): void
    {
        $stat = new Stat([
            'account_id' => 'my-shop',
            'date' => 20260912,
            'amount_today' => 12000,
            'count_today' => 3,
            'amount_last7days' => 84000,
            'count_last7days' => 21,
            'amount_this_month' => 360000,
            'count_this_month' => 90,
        ]);

        $this->assertSame('my-shop', $stat->getAccountId());
        $this->assertSame(20260912, $stat->getDate());
        $this->assertSame(12000, $stat->getAmountToday());
        $this->assertSame(3, $stat->getCountToday());
        $this->assertSame(84000, $stat->getAmountLast7days());
        $this->assertSame(21, $stat->getCountLast7days());
        $this->assertSame(360000, $stat->getAmountThisMonth());
        $this->assertSame(90, $stat->getCountThisMonth());
    }
}
