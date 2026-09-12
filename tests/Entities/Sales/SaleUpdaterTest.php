<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Sales;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Sales\SaleDeliveryUpdater;
use Shimoning\ColorMeShopApi\Entities\Sales\SaleUpdater;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;

class SaleUpdaterTest extends TestCase
{
    public function test_IDが欠損していればgetter呼び出し時に固有例外になる(): void
    {
        $updater = new SaleUpdater([]);

        $this->expectException(MissingFieldException::class);
        $this->expectExceptionMessage(
            SaleUpdater::class . ' の API フィールド『id』が欠損しています。',
        );

        $updater->getId();
    }

    public function test_更新したい項目だけを設定した部分更新データを配列化できる(): void
    {
        $updater = new SaleUpdater(['id' => 1001]);
        $updater->setPaid(true);

        $this->assertSame(['id' => 1001, 'paid' => true], $updater->toArrayRecursive());
    }

    public function test_お届け先も更新したい項目だけを設定して配列化できる(): void
    {
        $delivery = new SaleDeliveryUpdater([]);
        $delivery->setName('山田太郎');

        $updater = new SaleUpdater(['id' => 1001]);
        $updater->setSaleDeliveries([$delivery]);

        $this->assertSame(
            ['id' => 1001, 'sale_deliveries' => [['name' => '山田太郎']]],
            $updater->toArrayRecursive(),
        );
    }

    public function test_お届け先updaterは親の欠損guardを継承する(): void
    {
        $delivery = new SaleDeliveryUpdater([]);

        $this->expectException(MissingFieldException::class);
        $this->expectExceptionMessage(
            SaleDeliveryUpdater::class . ' の API フィールド『delivery_charge』が欠損しています。',
        );

        $delivery->getDeliveryCharge();
    }
}
