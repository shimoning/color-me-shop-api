<?php

namespace Shimoning\ColorMeShopApi\Tests\Constants;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Constants\ExternalAccountProvider;
use Shimoning\ColorMeShopApi\Constants\FallbackEnum;
use Shimoning\ColorMeShopApi\Constants\MailType;
use Shimoning\ColorMeShopApi\Constants\MailState;
use Shimoning\ColorMeShopApi\Constants\PointState;
use Shimoning\ColorMeShopApi\Constants\Prefecture;
use Shimoning\ColorMeShopApi\Constants\AuthScope;
use Shimoning\ColorMeShopApi\Constants\Sex;

/**
 * API のリクエスト・レスポンスに直接現れる enum の値を固定する。
 * ここが変わると API との互換性が壊れるため、値そのものをテストで固定している。
 */
class DomainEnumTest extends TestCase
{
    public function test_ExternalAccountProviderは未知値用のフォールバックを公開する(): void
    {
        $this->assertInstanceOf(FallbackEnum::class, ExternalAccountProvider::UNKNOWN);
        $this->assertInstanceOf(\BackedEnum::class, ExternalAccountProvider::UNKNOWN);
        $this->assertSame(0, ExternalAccountProvider::LINE->value);
        $this->assertSame(-1, ExternalAccountProvider::UNKNOWN->value);
        $this->assertSame(ExternalAccountProvider::UNKNOWN, ExternalAccountProvider::fallbackCase());
    }

    public function test_FallbackEnumはBackedEnumの契約を継承する(): void
    {
        $reflection = new \ReflectionClass(FallbackEnum::class);

        $this->assertTrue($reflection->implementsInterface(\BackedEnum::class));
    }

    public function test_Sexの値と日本語名はAPIの仕様どおり(): void
    {
        $this->assertSame(
            ['male', 'female', 'not_applicable'],
            \array_map(fn(Sex $case): string => $case->value, Sex::cases()),
        );
        $this->assertSame('男性', Sex::MALE->name());
        $this->assertSame('女性', Sex::FEMALE->name());
        $this->assertSame('未回答', Sex::NOT_APPLICABLE->name());
    }

    public function test_MailTypeの値はAPIの仕様どおり(): void
    {
        $this->assertSame('accepted', MailType::ACCEPTED->value);
        $this->assertSame('paid', MailType::PAID->value);
        $this->assertSame('delivered', MailType::DELIVERED->value);
        $this->assertCount(3, MailType::cases());
    }

    public function test_MailStateの値はAPIの仕様どおり(): void
    {
        $this->assertSame('not_yet', MailState::NOT_YET->value);
        $this->assertSame('sent', MailState::SENT->value);
        $this->assertSame('pass', MailState::PASS->value);
        $this->assertCount(3, MailState::cases());
    }

    public function test_PointStateの値はAPIの仕様どおり(): void
    {
        $this->assertSame('assumed', PointState::ASSUMED->value);
        $this->assertSame('fixed', PointState::FIXED->value);
        $this->assertSame('canceled', PointState::CANCELED->value);
        $this->assertCount(3, PointState::cases());
    }

    public function test_AuthScopeの値はAPIの仕様どおり(): void
    {
        $this->assertSame('read_products', AuthScope::READ_PRODUCTS->value);
        $this->assertSame('write_products', AuthScope::WRITE_PRODUCTS->value);
        $this->assertSame('read_sales', AuthScope::READ_SALES->value);
        $this->assertSame('write_sales', AuthScope::WRITE_SALES->value);
        $this->assertSame('read_shop_coupons', AuthScope::READ_SHOP_COUPONS->value);
        $this->assertSame('write_shop_coupons', AuthScope::WRITE_SHOP_COUPONS->value);
        $this->assertSame('read_templates', AuthScope::READ_TEMPLATES->value);
        $this->assertSame('write_templates', AuthScope::WRITE_TEMPLATES->value);
        $this->assertSame('read_analytics', AuthScope::READ_ANALYTICS->value);
        $this->assertCount(9, AuthScope::cases());
    }

    public function test_Prefectureは47都道府県と海外の48件で1から連番になっている(): void
    {
        $cases = Prefecture::cases();

        $this->assertCount(48, $cases);
        $this->assertSame(\range(1, 48), \array_map(fn($c) => $c->value, $cases));
        $this->assertSame(Prefecture::HOKKAIDO, $cases[0]);
        $this->assertSame(13, Prefecture::TOKYO->value);
    }

    public function test_nameメソッドは日本語表記を返す(): void
    {
        $this->assertSame('受注メール', MailType::ACCEPTED->name());
        $this->assertSame('未送信', MailState::NOT_YET->name());
        $this->assertSame('東京都', Prefecture::TOKYO->name());
    }
}
