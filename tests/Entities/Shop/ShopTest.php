<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Shop;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Shop\Shop;
use Shimoning\ColorMeShopApi\Constants\ShopState;

class ShopTest extends TestCase
{
    private function makeShop(array $overrides = []): Shop
    {
        return new Shop($overrides + [
            'id' => 'my-shop',
            'state' => 'enabled',
            'name1' => 'テストショップ',
            'hojin' => '株式会社テスト',
            'hojin_kana' => 'カブシキガイシャテスト',
        ]);
    }

    public function test_法人名を取得する(): void
    {
        $shop = $this->makeShop();

        $this->assertSame('株式会社テスト', $shop->getHojin());
        $this->assertSame('カブシキガイシャテスト', $shop->getHojinKana());
    }

    /**
     * 法人ではないショップでは API が null を返す。
     * プロパティが非 null 許容だと、生成の時点で TypeError になっていた。
     */
    public function test_法人名がnullでも生成できる(): void
    {
        $shop = $this->makeShop(['hojin' => null, 'hojin_kana' => null]);

        $this->assertNull($shop->getHojin());
        $this->assertNull($shop->getHojinKana());
    }

    public function test_法人名の項目がなくても生成できる(): void
    {
        $shop = new Shop(['id' => 'my-shop', 'state' => 'enabled']);

        $this->assertNull($shop->getHojin());
        $this->assertNull($shop->getHojinKana());
        $this->assertSame(ShopState::ENABLED, $shop->getState());
    }

    public function test_連絡先メールアドレスはshop_mail_1から取り込まれる(): void
    {
        $shop = $this->makeShop(['shop_mail_1' => 'a@example.test', 'shop_mail_2' => 'b@example.test']);

        $this->assertSame('a@example.test', $shop->toArray()['shop_mail1']);
        $this->assertSame('b@example.test', $shop->toArray()['shop_mail2']);
    }
}
