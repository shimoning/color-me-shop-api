<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Sales;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Sales\Sale;
use Shimoning\ColorMeShopApi\Constants\MailState;
use Shimoning\ColorMeShopApi\Constants\PointState;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;

class SaleTest extends TestCase
{
    /**
     * PR1 時点の空の Sale を PHP 8.1 で serialize() したペイロード。
     *
     * untyped な $customer は null として含まれるため、型宣言の追加による
     * unserialize() の後方互換性破壊を検出できる。
     */
    private const PR1_EMPTY_SALE_PAYLOAD_BASE64 = 'Tzo0NDoiU2hpbW9uaW5nXENvbG9yTWVTaG9wQXBpXEVudGl0aWVzXFNhbGVzXFNhbGUiOjg6e3M6NDY6IgBTaGltb25pbmdcQ29sb3JNZVNob3BBcGlcRW50aXRpZXNcRW50aXR5AF9yYXciO2E6MDp7fXM6NzoiACoAbWVtbyI7TjtzOjIzOiIAKgBhY2NlcHRlZE1haWxTZW50RGF0ZSI7TjtzOjE5OiIAKgBwYWlkTWFpbFNlbnREYXRlIjtOO3M6MjQ6IgAqAGRlbGl2ZXJlZE1haWxTZW50RGF0ZSI7TjtzOjE2OiIAKgBnbW9Qb2ludFN0YXRlIjtOO3M6MTg6IgAqAHlhaG9vUG9pbnRTdGF0ZSI7TjtzOjExOiIAKgBjdXN0b21lciI7Tjt9';

    private function makeSale(array $overrides = []): Sale
    {
        return new Sale($overrides + [
            'id' => 1001,
            'account_id' => 'my-shop',
            'payment_id' => 42,
            'paid' => true,
            'delivered' => false,
            'canceled' => false,
            'accepted_mail_state' => 'sent',
            'point_state' => 'fixed',
            'total_price' => 3300,
        ]);
    }

    /**
     * 決済方法 ID は Payment::getId() と対応する数値のため int で返す。
     * 他の数値 ID のゲッター (getId() など) とも型を揃える。
     */
    public function test_決済方法IDをintで取得する(): void
    {
        $this->assertSame(42, $this->makeSale()->getPaymentId());
    }

    public function test_受注IDをintで取得する(): void
    {
        $this->assertSame(1001, $this->makeSale()->getId());
    }

    public function test_ショップアカウントIDはstringで取得する(): void
    {
        $this->assertSame('my-shop', $this->makeSale()->getAccountId());
    }

    public function test_状態を取得する(): void
    {
        $sale = $this->makeSale();

        $this->assertTrue($sale->isPaid());
        $this->assertFalse($sale->isDelivered());
        $this->assertFalse($sale->isCanceled());
        $this->assertSame(MailState::SENT, $sale->getAcceptedMailState());
        $this->assertSame(PointState::FIXED, $sale->getPointState());
    }

    public function test_IDだけの部分応答で顧客が欠損していれば固有例外になる(): void
    {
        $sale = new Sale(['id' => 1001]);

        $this->expectException(MissingFieldException::class);
        $this->expectExceptionMessage(
            Sale::class . ' の API フィールド『customer』が欠損しています。',
        );

        $sale->getCustomer();
    }

    public function test_顧客がある応答ではCustomerに変換して取得できる(): void
    {
        $sale = new Sale(['customer' => ['id' => 501]]);

        $this->assertSame(501, $sale->getCustomer()->getId());
    }

    public function test_PR1時点のserializeペイロードをunserializeできる(): void
    {
        $sale = \unserialize(self::pr1EmptySalePayload());

        $this->assertInstanceOf(Sale::class, $sale);
    }

    public function test_PR1時点と空のSaleのserialize表現が同じ(): void
    {
        $this->assertSame(self::pr1EmptySalePayload(), \serialize(new Sale([])));
    }

    public function test_顧客プロパティはサブクラス互換のため型宣言を持たない(): void
    {
        $this->assertNull((new \ReflectionProperty(Sale::class, 'customer'))->getType());
    }

    public function test_サブクラスは継承した顧客プロパティへ従来どおり任意の値を代入できる(): void
    {
        $sale = new class([]) extends Sale {
            public function assignCustomer(mixed $customer): void
            {
                $this->customer = $customer;
            }

            public function rawCustomer(): mixed
            {
                return $this->customer;
            }
        };

        $sale->assignCustomer(null);
        $this->assertNull($sale->rawCustomer());

        $customer = new \stdClass();
        $sale->assignCustomer($customer);
        $this->assertSame($customer, $sale->rawCustomer());
    }

    private static function pr1EmptySalePayload(): string
    {
        $payload = \base64_decode(self::PR1_EMPTY_SALE_PAYLOAD_BASE64, true);
        if ($payload === false) {
            self::fail('PR1 時点の serialize ペイロードをデコードできません。');
        }

        return $payload;
    }
}
