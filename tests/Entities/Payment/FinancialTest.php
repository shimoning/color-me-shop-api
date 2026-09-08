<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Payment;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Payment\Financial;
use Shimoning\ColorMeShopApi\Constants\KouzaType;

class FinancialTest extends TestCase
{
    private function makeFinancial(array $overrides = []): Financial
    {
        return new Financial($overrides + [
            'name' => 'テスト銀行',
            'branch_name' => '本店',
            'kouza_type' => 'saving',
            'kouza_number' => '1234567',
            'kouza_name' => 'ヤマダタロウ',
        ]);
    }

    public function test_口座種別をenumとして取得する(): void
    {
        $this->assertSame(KouzaType::SAVING, $this->makeFinancial()->getKouzaType());
    }

    public function test_当座も取得できる(): void
    {
        $financial = $this->makeFinancial(['kouza_type' => 'checking']);

        $this->assertSame(KouzaType::CHECKING, $financial->getKouzaType());
    }

    public function test_その他の項目も取得できる(): void
    {
        $financial = $this->makeFinancial();

        $this->assertSame('テスト銀行', $financial->getName());
        $this->assertSame('本店', $financial->getBranchName());
        $this->assertSame('1234567', $financial->getKouzaNumber());
        $this->assertSame('ヤマダタロウ', $financial->getKouzaName());
    }

    public function test_口座種別が配列にも含まれる(): void
    {
        $this->assertSame('saving', $this->makeFinancial()->toArrayRecursive()['kouza_type']);
    }
}
