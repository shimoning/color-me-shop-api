<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Sales;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Sales\Sale;
use Shimoning\ColorMeShopApi\Entities\Sales\SaleDeliveryUpdateInput;
use Shimoning\ColorMeShopApi\Entities\Sales\SaleUpdateInput;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;

class SaleUpdateInputTest extends TestCase
{
    public function test_更新したい項目だけを設定した部分更新データを配列化できる(): void
    {
        $updater = new SaleUpdateInput([]);
        $updater->setPaid(true);

        $this->assertSame(['paid' => true], $updater->toArrayRecursive());
    }

    public function test_未宣言のIDは生データに保持するが更新データには含めない(): void
    {
        $updater = new SaleUpdateInput(['id' => 1001, 'paid' => true]);

        $this->assertSame(['paid' => true], $updater->toArrayRecursive());
        $this->assertSame(['id' => 1001, 'paid' => true], $updater->getRaw());
    }

    public function test_お届け先も更新したい項目だけを設定して配列化できる(): void
    {
        $delivery = new SaleDeliveryUpdateInput([]);
        $delivery->setName('山田太郎');

        $updater = new SaleUpdateInput([]);
        $updater->setSaleDeliveries([$delivery]);

        $this->assertSame(
            ['sale_deliveries' => [['name' => '山田太郎']]],
            $updater->toArrayRecursive(),
        );
    }

    public function test_お届け先に明示したnullは更新データに含める(): void
    {
        $delivery = new SaleDeliveryUpdateInput(['memo' => null]);
        $updater = new SaleUpdateInput([
            'sale_deliveries' => [$delivery->toArrayRecursive()],
        ]);

        $this->assertSame(
            ['sale_deliveries' => [['memo' => null]]],
            $updater->toArrayRecursive(),
        );
    }

    public function test_受注から変換した更新データにIDを含めない(): void
    {
        $sale = new Sale([
            'id' => 1001,
            'paid' => true,
            'point_state' => 'fixed',
            'sale_deliveries' => [],
        ]);

        $this->assertSame(
            ['paid' => true, 'point_state' => 'fixed', 'sale_deliveries' => []],
            SaleUpdateInput::convert($sale)->toArrayRecursive(),
        );
    }

    public function test_お届け先の非リストを拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(
            SaleUpdateInput::class . ' の API フィールド『sale_deliveries』が不正です。'
            . 'list<' . SaleDeliveryUpdateInput::class . '> を期待しましたが array でした。',
        );

        new SaleUpdateInput(['sale_deliveries' => [3 => ['name' => 'x']]]);
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
