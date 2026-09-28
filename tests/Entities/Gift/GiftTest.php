<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities\Gift;

use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Entities\Gift\Gift;
use Shimoning\ColorMeShopApi\Entities\Gift\GiftCard;
use Shimoning\ColorMeShopApi\Entities\Gift\GiftNoshi;
use Shimoning\ColorMeShopApi\Entities\Gift\GiftType;
use Shimoning\ColorMeShopApi\Entities\Gift\GiftWrapping;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class GiftTest extends TestCase
{
    public function test_ギフト設定を子Entityと日時へ変換する(): void
    {
        $response = self::fixtureArray('gift.json');
        $gift = new Gift($response['gift']);

        $this->assertSame('my-shop', $gift->getAccountId());
        $this->assertTrue($gift->getEnabled());
        $this->assertInstanceOf(GiftNoshi::class, $gift->getNoshi());
        $this->assertTrue($gift->getNoshi()->getEnabled());
        $this->assertTrue($gift->getNoshi()->getTextEnabled());
        $this->assertSame(100, $gift->getNoshi()->getTextCharge());
        $this->assertCount(1, $gift->getNoshi()->getTypes());
        $this->assertContainsOnlyInstancesOf(GiftType::class, $gift->getNoshi()->getTypes());
        $this->assertSame('お祝い用のし', $gift->getNoshi()->getTypes()[0]->getName());
        $this->assertSame(200, $gift->getNoshi()->getTypes()[0]->getCharge());
        $this->assertSame('用途に合わせてお選びください', $gift->getNoshi()->getComment());
        $this->assertInstanceOf(GiftCard::class, $gift->getCard());
        $this->assertTrue($gift->getCard()->getEnabled());
        $this->assertNull($gift->getCard()->getTextEnabled());
        $this->assertSame([], $gift->getCard()->getTypes());
        $this->assertNull($gift->getCard()->getComment());
        $this->assertInstanceOf(GiftWrapping::class, $gift->getWrapping());
        $this->assertTrue($gift->getWrapping()->getEnabled());
        $this->assertSame([], $gift->getWrapping()->getTypes());
        $this->assertNull($gift->getWrapping()->getComment());
        $this->assertSame(1725148800, $gift->getMakeDate()?->getTimestamp());
        $this->assertSame(1725235200, $gift->getUpdateDate()?->getTimestamp());
    }

    public function test_nullableフィールドが欠損していればnullを返す(): void
    {
        $gift = new Gift([
            'account_id' => 'my-shop',
            'noshi' => ['types' => []],
            'card' => ['types' => []],
            'wrapping' => ['types' => []],
        ]);

        $this->assertNull($gift->getEnabled());
        $this->assertNull($gift->getNoshi()->getEnabled());
        $this->assertNull($gift->getNoshi()->getTextEnabled());
        $this->assertNull($gift->getNoshi()->getTextCharge());
        $this->assertNull($gift->getNoshi()->getComment());
        $this->assertNull($gift->getCard()->getEnabled());
        $this->assertNull($gift->getCard()->getTextEnabled());
        $this->assertNull($gift->getCard()->getComment());
        $this->assertNull($gift->getWrapping()->getEnabled());
        $this->assertNull($gift->getWrapping()->getComment());
        $this->assertNull($gift->getMakeDate());
        $this->assertNull($gift->getUpdateDate());
    }

    /**
     * @return array<string, array{object, string, string}>
     */
    public static function missingRequiredFieldProvider(): array
    {
        return [
            'Gift.account_id' => [new Gift([]), 'getAccountId', 'account_id'],
            'Gift.noshi' => [new Gift([]), 'getNoshi', 'noshi'],
            'Gift.card' => [new Gift([]), 'getCard', 'card'],
            'Gift.wrapping' => [new Gift([]), 'getWrapping', 'wrapping'],
            'GiftNoshi.types' => [new GiftNoshi([]), 'getTypes', 'types'],
            'GiftCard.types' => [new GiftCard([]), 'getTypes', 'types'],
            'GiftWrapping.types' => [new GiftWrapping([]), 'getTypes', 'types'],
            'GiftType.name' => [new GiftType([]), 'getName', 'name'],
            'GiftType.charge' => [new GiftType([]), 'getCharge', 'charge'],
        ];
    }

    #[DataProvider('missingRequiredFieldProvider')]
    public function test_必須フィールドが欠損していればgetter呼び出し時に固有例外になる(
        object $entity,
        string $getter,
        string $field,
    ): void {
        $this->expectException(MissingFieldException::class);
        $this->expectExceptionMessage('API フィールド『' . $field . '』が欠損しています。');

        $entity->{$getter}();
    }

    public function test_GiftWrappingは仕様にないテキスト設定を持たない(): void
    {
        $this->assertFalse(\method_exists(GiftWrapping::class, 'getTextEnabled'));
        $this->assertFalse(\method_exists(GiftWrapping::class, 'getTextCharge'));
    }

    public function test_GiftCardは仕様にないテキスト料金を持たない(): void
    {
        $this->assertFalse(\method_exists(GiftCard::class, 'getTextCharge'));
    }
}
