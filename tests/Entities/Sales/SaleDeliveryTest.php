<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Sales;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Constants\Prefecture;
use Shimoning\ColorMeShopApi\Entities\Sales\SaleDelivery;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;

class SaleDeliveryTest extends TestCase
{
    public function test_配送方法IDが欠損していればgetter呼び出し時に固有例外になる(): void
    {
        $delivery = new SaleDelivery([]);

        $this->expectException(MissingFieldException::class);
        $this->expectExceptionMessage(
            SaleDelivery::class . ' の API フィールド『delivery_id』が欠損しています。',
        );

        $delivery->getDeliveryId();
    }

    public function test_nullableフィールドの欠損は従来どおりnullになる(): void
    {
        $delivery = new SaleDelivery([]);

        $this->assertNull($delivery->getPostal());
        $this->assertNull($delivery->getTrackingUrl());
        $this->assertNull($delivery->getMemo());
    }

    public function test_完全なお届け先を従来どおり取得できる(): void
    {
        $delivery = self::makeDelivery();

        $this->assertSame(21, $delivery->getId());
        $this->assertSame(1001, $delivery->getSaleId());
        $this->assertSame('my-shop', $delivery->getAccountId());
        $this->assertSame(10, $delivery->getDeliveryId());
        $this->assertSame([11], $delivery->getDetailIds());
        $this->assertSame('山田太郎', $delivery->getName());
        $this->assertSame(Prefecture::TOKYO, $delivery->getPrefId());
        $this->assertSame('東京都', $delivery->getPrefName());
        $this->assertSame(500, $delivery->getDeliveryCharge());
        $this->assertSame(2700, $delivery->getTotalCharge());
        $this->assertTrue($delivery->isDelivered());
    }

    /** @return array<string, mixed> */
    public static function completeData(): array
    {
        return [
            'id' => 21,
            'sale_id' => 1001,
            'account_id' => 'my-shop',
            'delivery_id' => 10,
            'detail_ids' => [11],
            'name' => '山田太郎',
            'furigana' => 'ヤマダタロウ',
            'postal' => '1000001',
            'pref_id' => 13,
            'pref_name' => '東京都',
            'address1' => '千代田区',
            'address2' => '1-1',
            'tel' => '0312345678',
            'preferred_date' => '2026-09-13',
            'preferred_period' => '午前中',
            'slip_number' => 'SLIP-1',
            'noshi_text' => '御礼',
            'noshi_charge' => 100,
            'card_name' => 'カード',
            'card_text' => 'ありがとう',
            'card_charge' => 50,
            'wrapping_name' => '包装',
            'wrapping_charge' => 150,
            'delivery_charge' => 500,
            'total_charge' => 2700,
            'tracking_url' => 'https://example.com/track',
            'memo' => '玄関前',
            'delivered' => true,
        ];
    }

    private static function makeDelivery(): SaleDelivery
    {
        return new SaleDelivery(self::completeData());
    }
}
