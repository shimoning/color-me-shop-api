<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Delivery;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Constants\Prefecture;
use Shimoning\ColorMeShopApi\Entities\Delivery\Charge;
use Shimoning\ColorMeShopApi\Entities\Delivery\Weight;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;

class ChargeTest extends TestCase
{
    public function test_重量別配送料が欠損していればgetter呼び出し時に固有例外になる(): void
    {
        $charge = new Charge([]);

        $this->expectException(MissingFieldException::class);
        $this->expectExceptionMessage(
            Charge::class . ' の API フィールド『charge_ranges_by_weight』が欠損しています。',
        );

        $charge->getChargeRangesByWeight();
    }

    public function test_重量別配送料の不正な行は添字付きの固有例外になる(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(
            Charge::class . ' の API フィールド『charge_ranges_by_weight[1]』が不正です。',
        );

        new Charge([
            'charge_ranges_by_weight' => [
                [1000, [['pref_id' => 1, 'pref_name' => '北海道', 'charge' => 500]]],
                [2000],
            ],
        ]);
    }

    public function test_完全な配送料設定を従来どおり取得できる(): void
    {
        $charge = self::makeCharge();

        $this->assertSame(10, $charge->getDeliveryId());
        $this->assertSame('my-shop', $charge->getAccountId());
        $this->assertNull($charge->getChargeFixed());
        $this->assertSame([[3000, 500]], $charge->getChargeRangesByPrice());
        $this->assertSame(900, $charge->getChargeMaxPrice());
        $this->assertSame(Prefecture::HOKKAIDO, $charge->getChargeRangesByArea()[0]->getPrefId());

        $weight = $charge->getChargeRangesByWeight()[0];
        $this->assertInstanceOf(Weight::class, $weight);
        $this->assertSame(1000, $weight->getWeight());
        $this->assertSame(500, $weight->getAreas()[0]->getCharge());
        $this->assertSame(900, $charge->getChargeRangesMaxWeight()[0]->getCharge());
    }

    private static function makeCharge(): Charge
    {
        return new Charge([
            'delivery_id' => 10,
            'account_id' => 'my-shop',
            'charge_fixed' => null,
            'charge_ranges_by_price' => [[3000, 500]],
            'charge_max_price' => 900,
            'charge_ranges_by_area' => [
                ['pref_id' => 1, 'pref_name' => '北海道', 'charge' => 500],
            ],
            'charge_ranges_by_weight' => [
                [1000, [['pref_id' => 1, 'pref_name' => '北海道', 'charge' => 500]]],
            ],
            'charge_ranges_max_weight' => [
                ['pref_id' => 1, 'pref_name' => '北海道', 'charge' => 900],
            ],
        ]);
    }
}
