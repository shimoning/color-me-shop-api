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

    public function test_公式フィールド名から完全な売上集計を取得できる(): void
    {
        $stat = new Stat([
            'account_id' => 'my-shop',
            'date' => 20260912,
            'amount_today' => 12000,
            'count_today' => 3,
            // 公式 API では数字の前にもアンダースコアが入る。
            'amount_last_7days' => 84000,
            'count_last_7days' => 21,
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

    public function test_公式フィールド名で取り込んだ値を同じキーで配列化できる(): void
    {
        $stat = new Stat([
            'amount_last_7days' => 84000,
            'count_last_7days' => 21,
        ]);

        foreach ([$stat->toArray(), $stat->toArrayRecursive()] as $array) {
            $this->assertArrayHasKey('amount_last_7days', $array);
            $this->assertArrayHasKey('count_last_7days', $array);
            $this->assertSame(84000, $array['amount_last_7days']);
            $this->assertSame(21, $array['count_last_7days']);
            $this->assertArrayNotHasKey('amount_last7days', $array);
            $this->assertArrayNotHasKey('count_last7days', $array);
        }
    }

    public function test_apiFieldNameは数字前の区切りを含む公式フィールド名を返す(): void
    {
        $this->assertSame('amount_last_7days', Stat::apiFieldName('amountLast7days'));
        $this->assertSame('count_last_7days', Stat::apiFieldName('countLast7days'));
    }

    public function test_旧誤フィールド名も後方互換性のため取り込める(): void
    {
        $stat = new Stat([
            'amount_last7days' => 84000,
            'count_last7days' => 21,
        ]);

        $this->assertSame(84000, $stat->getAmountLast7days());
        $this->assertSame(21, $stat->getCountLast7days());
    }
}
