<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities\Delivery;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Delivery\Date\Date;
use Shimoning\ColorMeShopApi\Entities\Delivery\Date\Days;
use Shimoning\ColorMeShopApi\Entities\Delivery\Date\Times;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;

class DeliveryDateTest extends TestCase
{
    public function test_配送日時設定を子Entityと日時へ変換する(): void
    {
        $deliveryDate = new Date([
            'account_id' => 'my-shop',
            'days' => [
                'enabled' => true,
                'default' => 3,
                'min' => 2,
                'max' => 14,
                'comment' => '',
            ],
            'times' => [
                'enabled' => true,
                'periods' => ['午前中', '14時から16時'],
                'comment' => '配送時間帯を選択してください',
            ],
            'make_date' => 1725148800,
            'update_date' => 1725235200,
        ]);

        $this->assertSame('my-shop', $deliveryDate->getAccountId());
        $this->assertInstanceOf(Days::class, $deliveryDate->getDays());
        $this->assertTrue($deliveryDate->getDays()->getEnabled());
        $this->assertSame(3, $deliveryDate->getDays()->getDefault());
        $this->assertSame(2, $deliveryDate->getDays()->getMin());
        $this->assertSame(14, $deliveryDate->getDays()->getMax());
        $this->assertSame('', $deliveryDate->getDays()->getComment());
        $this->assertInstanceOf(Times::class, $deliveryDate->getTimes());
        $this->assertTrue($deliveryDate->getTimes()->getEnabled());
        $this->assertSame(['午前中', '14時から16時'], $deliveryDate->getTimes()->getPeriods());
        $this->assertSame('配送時間帯を選択してください', $deliveryDate->getTimes()->getComment());
        $this->assertSame(1725148800, $deliveryDate->getMakeDate()?->getTimestamp());
        $this->assertSame(1725235200, $deliveryDate->getUpdateDate()?->getTimestamp());
    }

    public function test_nullableフィールドが欠損していればnullを返す(): void
    {
        $deliveryDate = new Date([
            'account_id' => 'my-shop',
            'days' => ['min' => 2, 'max' => 14],
            'times' => ['periods' => []],
        ]);

        $this->assertNull($deliveryDate->getDays()->getEnabled());
        $this->assertNull($deliveryDate->getDays()->getDefault());
        $this->assertNull($deliveryDate->getDays()->getComment());
        $this->assertNull($deliveryDate->getTimes()->getEnabled());
        $this->assertSame([], $deliveryDate->getTimes()->getPeriods());
        $this->assertNull($deliveryDate->getTimes()->getComment());
        $this->assertNull($deliveryDate->getMakeDate());
        $this->assertNull($deliveryDate->getUpdateDate());
    }

    public function test_必須フィールドが欠損していればgetter呼び出し時に固有例外になる(): void
    {
        $deliveryDate = new Date([]);

        $this->expectException(MissingFieldException::class);
        $this->expectExceptionMessage(
            Date::class . ' の API フィールド『account_id』が欠損しています。',
        );

        $deliveryDate->getAccountId();
    }

    public function test_配送時間帯に文字列以外があれば固有例外になる(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(
            Times::class . ' の API フィールド『periods』が不正です。',
        );

        new Times(['periods' => ['午前中', 123]]);
    }
}
