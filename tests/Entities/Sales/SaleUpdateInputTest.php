<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Sales;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Entities\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Sale\Sale;
use Shimoning\ColorMeShopApi\Entities\Sale\Delivery;
use Shimoning\ColorMeShopApi\Entities\Sale\DeliveryUpdateInput;
use Shimoning\ColorMeShopApi\Entities\Sale\SaleUpdateInput;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

class SaleUpdateInputTest extends TestCase
{
    public function test_お届け先更新入力の構築と直列化の特性を保つ(): void
    {
        $data = [
            'id' => 21,
            'sale_id' => 1001,
            'account_id' => 'my-shop',
            'delivery_id' => 10,
            'detail_ids' => [11],
            'name' => '山田太郎',
            'furigana' => 'ヤマダタロウ',
            'postal' => null,
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

        $delivery = new DeliveryUpdateInput($data);

        $this->assertSame($data, $delivery->toArrayRecursive());
        $this->assertSame(
            ['sale_deliveries' => [$data]],
            (new SaleUpdateInput(['sale_deliveries' => [$data]]))->toArrayRecursive(),
        );
    }

    public function test_お届け先更新入力は応答EntityではなくRequestEntityである(): void
    {
        $delivery = new DeliveryUpdateInput([]);

        $this->assertNotInstanceOf(Delivery::class, $delivery);
        $this->assertInstanceOf(RequestEntity::class, $delivery);
        $parent = (new \ReflectionClass($delivery))->getParentClass();

        $this->assertNotFalse($parent);
        $this->assertSame(Entity::class, $parent->getName());
    }

    public function test_お届け先更新入力は更新対象のフィールドだけを持つ(): void
    {
        $reflection = new \ReflectionClass(DeliveryUpdateInput::class);
        $properties = \array_map(
            static fn (\ReflectionProperty $property): string => $property->getName(),
            \array_filter(
                $reflection->getProperties(),
                static fn (\ReflectionProperty $property): bool =>
                    $property->getDeclaringClass()->getName() === DeliveryUpdateInput::class,
            ),
        );
        \sort($properties);

        $expected = [
            'id',
            'saleId',
            'accountId',
            'deliveryId',
            'detailIds',
            'name',
            'furigana',
            'postal',
            'prefId',
            'prefName',
            'address1',
            'address2',
            'tel',
            'preferredDate',
            'preferredPeriod',
            'slipNumber',
            'noshiText',
            'noshiCharge',
            'cardName',
            'cardText',
            'cardCharge',
            'wrappingName',
            'wrappingCharge',
            'deliveryCharge',
            'totalCharge',
            'trackingUrl',
            'memo',
            'delivered',
        ];
        \sort($expected);

        $this->assertSame($expected, $properties);
    }

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
        $delivery = new DeliveryUpdateInput([]);
        $delivery->setName('山田太郎');

        $updater = new SaleUpdateInput([]);
        $updater->setSaleDeliveries([$delivery]);

        $this->assertSame(
            ['sale_deliveries' => [['name' => '山田太郎']]],
            $updater->toArrayRecursive(),
        );
    }

    public function test_setterはお届け先の非リストを拒否する(): void
    {
        $updater = new SaleUpdateInput([]);

        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(
            SaleUpdateInput::class . ' の API フィールド『sale_deliveries』が不正です。'
            . 'list<' . DeliveryUpdateInput::class . '> を期待しましたが array でした。',
        );

        $updater->setSaleDeliveries([
            3 => new DeliveryUpdateInput(['name' => 'x']),
        ]);
    }

    public function test_setterはお届け先の文字列キー配列を拒否する(): void
    {
        $updater = new SaleUpdateInput([]);

        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(
            SaleUpdateInput::class . ' の API フィールド『sale_deliveries』が不正です。'
            . 'list<' . DeliveryUpdateInput::class . '> を期待しましたが array でした。',
        );

        $updater->setSaleDeliveries(['name' => 'x']);
    }

    public function test_setterにリストを渡すとJSON配列になる(): void
    {
        $updater = new SaleUpdateInput([]);
        $updater->setSaleDeliveries([
            new DeliveryUpdateInput(['name' => 'x']),
        ]);

        $this->assertSame(
            '{"sale_deliveries":[{"name":"x"}]}',
            \json_encode($updater->toArrayRecursive(), \JSON_THROW_ON_ERROR),
        );
    }

    public function test_コンストラクタはお届け先の文字列キー配列を1要素として取り込む(): void
    {
        $updater = new SaleUpdateInput([
            'sale_deliveries' => ['name' => 'x'],
        ]);

        $this->assertSame(
            ['sale_deliveries' => [['name' => 'x']]],
            $updater->toArrayRecursive(),
        );
    }

    public function test_お届け先に明示したnullは更新データに含める(): void
    {
        $delivery = new DeliveryUpdateInput(['memo' => null]);
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
            . 'list<' . DeliveryUpdateInput::class . '> を期待しましたが array でした。',
        );

        new SaleUpdateInput(['sale_deliveries' => [3 => ['name' => 'x']]]);
    }

    public function test_お届け先更新入力は応答由来のgetterを公開しない(): void
    {
        $this->assertFalse(\method_exists(DeliveryUpdateInput::class, 'getDeliveryCharge'));
        $this->assertFalse(\method_exists(DeliveryUpdateInput::class, 'getSaleId'));
    }
}
