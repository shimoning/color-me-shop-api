<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Payment;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Constants\KouzaType;
use Shimoning\ColorMeShopApi\Constants\PaymentType;
use Shimoning\ColorMeShopApi\Entities\Payment\Payment;

class PaymentTest extends TestCase
{
    public function test_条件付き設定がない決済ではネスト要素がnullになる(): void
    {
        $payment = self::makePayment();

        $this->assertNull($payment->getCod());
        $this->assertNull($payment->getCard());
        $this->assertNull($payment->getFinancial());
    }

    public function test_完全な決済設定を従来どおり取得できる(): void
    {
        $payment = self::makePayment([
            'type' => PaymentType::COD->value,
            'cod' => [
                'changeable' => true,
                'fees' => [[3000, 100], [5000, 200]],
                'fee_max' => 500,
                'changeable_by_total' => false,
            ],
            'card' => [
                'brands' => [
                    ['id' => 2, 'name' => 'VISA'],
                ],
            ],
            'financial' => [
                'name' => 'テスト銀行',
                'branch_name' => '本店',
                'kouza_type' => KouzaType::SAVING->value,
                'kouza_number' => '1234567',
                'kouza_name' => 'ヤマダタロウ',
            ],
        ]);

        $this->assertSame(1, $payment->getId());
        $this->assertSame('my-shop', $payment->getAccountId());
        $this->assertSame('代金引換', $payment->getName());
        $this->assertSame(PaymentType::COD, $payment->getType());
        $this->assertTrue($payment->getDisplay());
        $this->assertFalse($payment->getUseMobile());
        $this->assertSame(1700000000, $payment->getMakeDate()->getTimestamp());
        $this->assertSame(1700000001, $payment->getUpdateDate()->getTimestamp());

        $cod = $payment->getCod();
        $this->assertNotNull($cod);
        $this->assertTrue($cod->getChangeable());
        $this->assertSame([[3000, 100], [5000, 200]], $cod->getFees());
        $this->assertSame(500, $cod->getFeeMax());
        $this->assertFalse($cod->getChangeableByTotal());

        $card = $payment->getCard();
        $this->assertNotNull($card);
        $this->assertSame(2, $card->getBrands()[0]->getId());
        $this->assertSame('VISA', $card->getBrands()[0]->getName());

        $financial = $payment->getFinancial();
        $this->assertNotNull($financial);
        $this->assertSame('テスト銀行', $financial->getName());
        $this->assertSame('本店', $financial->getBranchName());
        $this->assertSame(KouzaType::SAVING, $financial->getKouzaType());
        $this->assertSame('1234567', $financial->getKouzaNumber());
        $this->assertSame('ヤマダタロウ', $financial->getKouzaName());
    }

    private static function makePayment(array $overrides = []): Payment
    {
        return new Payment($overrides + [
            'id' => 1,
            'account_id' => 'my-shop',
            'name' => '代金引換',
            'type' => PaymentType::OTHER->value,
            'display' => true,
            'use_mobile' => false,
            'make_date' => 1700000000,
            'update_date' => 1700000001,
        ]);
    }
}
