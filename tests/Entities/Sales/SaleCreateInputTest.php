<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities\Sales;

use Shimoning\ColorMeShopApi\Constants\Prefecture;
use Shimoning\ColorMeShopApi\Constants\Sex;
use Shimoning\ColorMeShopApi\Entities\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Sale\SaleCreateInput;
use Shimoning\ColorMeShopApi\Entities\Sale\CustomerCreateInput;
use Shimoning\ColorMeShopApi\Entities\Sale\DeliveryCreateInput;
use Shimoning\ColorMeShopApi\Entities\Sale\DetailCreateInput;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;
use Shimoning\ColorMeShopApi\Values\Furigana;

class SaleCreateInputTest extends TestCase
{
    /** @return array<string, mixed> */
    private static function required(array $overrides = []): array
    {
        return \array_merge([
            'payment_id' => 3,
            'details' => [['product_id' => 101, 'product_num' => 1]],
        ], $overrides);
    }

    public function test_既存顧客のファクトリは顧客IDだけを送信する(): void
    {
        $customer = CustomerCreateInput::existing(501);

        $this->assertInstanceOf(RequestEntity::class, $customer);
        $this->assertSame(['id' => 501], $customer->toArrayRecursive());
    }

    public function test_顧客IDと他の顧客情報を同時に指定できる(): void
    {
        $customer = new CustomerCreateInput([
            'id' => 501,
            'name' => '無視される名前',
            'mail' => 'ignored@example.com',
        ]);

        $this->assertSame([
            'id' => 501,
            'name' => '無視される名前',
            'mail' => 'ignored@example.com',
        ], $customer->toArrayRecursive());
    }

    public function test_ゲスト顧客の型付きフィールドを変換する(): void
    {
        $customer = new CustomerCreateInput([
            'name' => 'カラーミー太郎',
            'furigana' => 'カラーミータロウ',
            'pref_id' => Prefecture::TOKYO,
            'sex' => Sex::MALE,
            'birthday' => '1999-01-01',
        ]);

        $this->assertInstanceOf(Furigana::class, $customer->toArray()['furigana']);
        $this->assertSame([
            'name' => 'カラーミー太郎',
            'furigana' => 'カラーミータロウ',
            'pref_id' => 13,
            'sex' => 'male',
            'birthday' => '1999-01-01',
        ], $customer->toArrayRecursive());
    }

    public function test_性別はmaleとfemaleだけを受け付ける(): void
    {
        $this->assertSame(
            ['sex' => 'female'],
            (new CustomerCreateInput(['sex' => 'female']))->toArrayRecursive(),
        );

        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('sex');
        new CustomerCreateInput(['sex' => Sex::UNKNOWN]);
    }

    public function test_顧客APIでは有効な未回答の性別も受注作成では拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('sex');

        new CustomerCreateInput(['sex' => Sex::NOT_APPLICABLE]);
    }

    public function test_お届け先と明細の配列を子Entityへ変換する(): void
    {
        $input = new SaleCreateInput([
            'sale_deliveries' => [[
                'delivery_id' => 1,
                'name' => '配送先',
                'furigana' => 'ハイソウサキ',
                'postal' => '1508512',
                'pref_id' => 13,
                'address1' => '渋谷区桜丘町26-1',
                'tel' => '03-5456-2622',
                'preferred_date' => '2026-10-10',
            ]],
            'details' => [[
                'product_id' => 101,
                'option1_value' => '赤',
                'product_num' => 2,
                'price' => 1200,
            ]],
            'payment_id' => 3,
        ]);

        $this->assertSame([
            'sale_deliveries' => [[
                'delivery_id' => 1,
                'name' => '配送先',
                'furigana' => 'ハイソウサキ',
                'postal' => '1508512',
                'pref_id' => 13,
                'address1' => '渋谷区桜丘町26-1',
                'tel' => '03-5456-2622',
                'preferred_date' => '2026-10-10',
            ]],
            'details' => [[
                'product_id' => 101,
                'option1_value' => '赤',
                'product_num' => 2,
                'price' => 1200,
            ]],
            'payment_id' => 3,
        ], $input->toArrayRecursive());
    }

    public function test_構築済みの顧客と明細をそのまま利用できる(): void
    {
        $customer = CustomerCreateInput::existing(9);
        $detail = new DetailCreateInput([
            'product_id' => 101,
            'product_num' => 2,
            'price' => null,
        ]);
        $input = new SaleCreateInput([
            'customer' => $customer,
            'details' => [$detail],
            'payment_id' => 3,
        ]);

        $this->assertSame([
            'customer' => ['id' => 9],
            'details' => [[
                'product_id' => 101,
                'product_num' => 2,
                'price' => null,
            ]],
            'payment_id' => 3,
        ], $input->toArrayRecursive());
    }

    public function test_必須フィールドは現在受け付けているcamelCaseでも指定できる(): void
    {
        $input = new SaleCreateInput([
            'paymentId' => 3,
            'details' => [['productId' => 101, 'productNum' => 2]],
            'saleDeliveries' => [[
                'deliveryId' => 1,
                'name' => '配送先',
                'furigana' => 'ハイソウサキ',
                'postal' => '1508512',
                'prefId' => 13,
                'address1' => '渋谷区桜丘町26-1',
                'tel' => '03-5456-2622',
            ]],
        ]);

        $fields = $input->toArrayRecursive();
        $this->assertSame(3, $fields['payment_id']);
        $this->assertSame(101, $fields['details'][0]['product_id']);
        $this->assertSame(2, $fields['details'][0]['product_num']);
        $this->assertSame(1, $fields['sale_deliveries'][0]['delivery_id']);
        $this->assertSame(13, $fields['sale_deliveries'][0]['pref_id']);
    }

    public function test_明細の非リストを拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(
            SaleCreateInput::class . ' の API フィールド『details』が不正です。'
            . 'list<' . DetailCreateInput::class . '> を期待しましたが array でした。',
        );

        new SaleCreateInput(['payment_id' => 3, 'details' => [1 => ['product_id' => 2, 'product_num' => 1]]]);
    }

    public function test_お届け先の非リストを拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(
            SaleCreateInput::class . ' の API フィールド『sale_deliveries』が不正です。'
            . 'list<' . DeliveryCreateInput::class . '> を期待しましたが array でした。',
        );

        new SaleCreateInput(self::required(['sale_deliveries' => [3 => ['name' => 'x']]]));
    }

    public function test_トップレベル必須フィールドの欠落を拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(SaleCreateInput::class);
        $this->expectExceptionMessage('必須: payment_id, details');
        $this->expectExceptionMessage('未指定: payment_id, details');

        new SaleCreateInput([]);
    }

    public function test_detailsは1件以上必要(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('details');
        $this->expectExceptionMessage('1件以上');

        new SaleCreateInput(['payment_id' => 3, 'details' => []]);
    }

    public function test_明細単体の必須フィールド欠落を拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(DetailCreateInput::class);
        $this->expectExceptionMessage('必須: product_id, product_num');
        $this->expectExceptionMessage('未指定: product_num');

        new DetailCreateInput(['product_id' => 101]);
    }

    public function test_お届け先単体の必須フィールド欠落を拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(DeliveryCreateInput::class);
        $this->expectExceptionMessage('未指定: furigana, postal, pref_id, address1, tel');

        new DeliveryCreateInput(['delivery_id' => 1, 'name' => '配送先']);
    }

    public function test_SaleCreateInput経由の明細欠落は要素位置と欠落フィールドを示す(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('details[2]');
        $this->expectExceptionMessage('未指定: product_num');

        new SaleCreateInput(self::required(['details' => [
            ['product_id' => 101, 'product_num' => 1],
            ['product_id' => 102, 'product_num' => 1],
            ['product_id' => 103],
        ]]));
    }

    public function test_SaleCreateInput経由のお届け先欠落は要素位置と欠落フィールドを示す(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('sale_deliveries[1]');
        $this->expectExceptionMessage('未指定: tel');

        $delivery = [
            'delivery_id' => 1,
            'name' => '配送先',
            'furigana' => 'ハイソウサキ',
            'postal' => '1508512',
            'pref_id' => 13,
            'address1' => '渋谷区桜丘町26-1',
            'tel' => '03-5456-2622',
        ];
        $missingTel = $delivery;
        unset($missingTel['tel']);

        new SaleCreateInput(self::required(['sale_deliveries' => [$delivery, $missingTel]]));
    }
}
