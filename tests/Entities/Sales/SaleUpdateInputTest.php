<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Sales;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Sales\SaleDeliveryUpdateInput;
use Shimoning\ColorMeShopApi\Entities\Sales\SaleUpdateInput;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;

class SaleUpdateInputTest extends TestCase
{
    public function test_IDが欠損していればgetter呼び出し時に固有例外になる(): void
    {
        $updater = new SaleUpdateInput([]);

        $this->expectException(MissingFieldException::class);
        $this->expectExceptionMessage(
            SaleUpdateInput::class . ' の API フィールド『id』が欠損しています。',
        );

        $updater->getId();
    }

    public function test_更新したい項目だけを設定した部分更新データを配列化できる(): void
    {
        $updater = new SaleUpdateInput(['id' => 1001]);
        $updater->setPaid(true);

        $this->assertSame(['id' => 1001, 'paid' => true], $updater->toArrayRecursive());
    }

    public function test_お届け先も更新したい項目だけを設定して配列化できる(): void
    {
        $delivery = new SaleDeliveryUpdateInput([]);
        $delivery->setName('山田太郎');

        $updater = new SaleUpdateInput(['id' => 1001]);
        $updater->setSaleDeliveries([$delivery]);

        $this->assertSame(
            ['id' => 1001, 'sale_deliveries' => [['name' => '山田太郎']]],
            $updater->toArrayRecursive(),
        );
    }

    public function test_お届け先に明示したnullは更新データに含める(): void
    {
        $delivery = new SaleDeliveryUpdateInput(['memo' => null]);
        $updater = new SaleUpdateInput([
            'id' => 1001,
            'sale_deliveries' => [$delivery->toArrayRecursive()],
        ]);

        $this->assertSame(
            ['id' => 1001, 'sale_deliveries' => [['memo' => null]]],
            $updater->toArrayRecursive(),
        );
    }

    public function test_お届け先updaterは親の欠損guardを継承する(): void
    {
        $delivery = new SaleDeliveryUpdateInput([]);

        $this->expectException(MissingFieldException::class);
        $this->expectExceptionMessage(
            SaleDeliveryUpdateInput::class . ' の API フィールド『delivery_charge』が欠損しています。',
        );

        $delivery->getDeliveryCharge();
    }
}
