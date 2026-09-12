<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Sales;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Sales\Sale;
use Shimoning\ColorMeShopApi\Constants\MailState;
use Shimoning\ColorMeShopApi\Constants\PointState;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;

class SaleTest extends TestCase
{
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

        $sale->getCustomer();
    }

    public function test_顧客がある応答ではCustomerに変換して取得できる(): void
    {
        $sale = new Sale(['customer' => ['id' => 501]]);

        $this->assertSame(501, $sale->getCustomer()->getId());
    }
}
