<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product;

use Shimoning\ColorMeShopApi\Entities\Product\Pickup;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class PickupTest extends TestCase
{
    /** product_id / account_id 追加前の Pickup を serialize() したペイロード。 */
    private const LEGACY_PICKUP_PAYLOAD_BASE64 = 'Tzo0ODoiU2hpbW9uaW5nXENvbG9yTWVTaG9wQXBpXEVudGl0aWVzXFByb2R1Y3RcUGlja3VwIjo1OntzOjQ2OiIAU2hpbW9uaW5nXENvbG9yTWVTaG9wQXBpXEVudGl0aWVzXEVudGl0eQBfcmF3IjthOjQ6e3M6MTE6InBpY2t1cF90eXBlIjtpOjE7czo5OiJvcmRlcl9udW0iO2k6MjtzOjk6Im1ha2VfZGF0ZSI7aToxNzAwMDAwMDAwO3M6MTE6InVwZGF0ZV9kYXRlIjtpOjE3MDAwMDAxMDA7fXM6MTM6IgAqAHBpY2t1cFR5cGUiO2k6MTtzOjExOiIAKgBvcmRlck51bSI7aToyO3M6MTE6IgAqAG1ha2VEYXRlIjtpOjE3MDAwMDAwMDA7czoxMzoiACoAdXBkYXRlRGF0ZSI7aToxNzAwMDAwMTAwO30=';

    public function test_書き込み応答のproduct_idとaccount_idを取得できる(): void
    {
        $pickup = new Pickup(self::fixtureArray('product_pickup.json')['pickup']);

        $this->assertSame(3, $pickup->getPickupType());
        $this->assertSame(101, $pickup->getProductId());
        $this->assertSame('TEST_ACCOUNT', $pickup->getAccountId());
        $this->assertSame(1, $pickup->getOrderNum());
    }

    public function test_商品内pickupsの応答ではproduct_idとaccount_idはnullで配列化から省かれる(): void
    {
        $pickup = new Pickup(['pickup_type' => 1, 'order_num' => null, 'make_date' => 1, 'update_date' => 2]);

        $this->assertNull($pickup->getProductId());
        $this->assertNull($pickup->getAccountId());
        $this->assertSame(['pickup_type' => 1, 'make_date' => 1, 'update_date' => 2], $pickup->toArrayRecursive());
    }

    public function test_フィールド追加前の旧ペイロードを復元してもnullを返す(): void
    {
        $serialized = \base64_decode(self::LEGACY_PICKUP_PAYLOAD_BASE64, true);
        $this->assertNotFalse($serialized);

        $pickup = \unserialize($serialized, ['allowed_classes' => [Pickup::class]]);

        $this->assertInstanceOf(Pickup::class, $pickup);
        $this->assertSame(1, $pickup->getPickupType());
        $this->assertSame(2, $pickup->getOrderNum());
        $this->assertNull($pickup->getProductId());
        $this->assertNull($pickup->getAccountId());
    }

    public function test_product_idの型が不正なら固有例外になる(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('product_id');
        new Pickup(['pickup_type' => 1, 'product_id' => '101']);
    }
}
