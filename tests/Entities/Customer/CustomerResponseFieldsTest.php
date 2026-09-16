<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Customer;

use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Constants\ExternalAccountProvider;
use Shimoning\ColorMeShopApi\Entities\Customer\Customer;
use Shimoning\ColorMeShopApi\Entities\Customer\ExternalAccount;
use Shimoning\ColorMeShopApi\Entities\Customer\Membership;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class CustomerResponseFieldsTest extends TestCase
{
    public function test_顧客レスポンスの追加フィールドを取得する(): void
    {
        $customer = new Customer(self::fixtureData('customer_with_values'));

        $this->assertSame(1465784944, $customer->getMakeDate());
        $this->assertSame(1494496809, $customer->getUpdateDate());

        $membership = $customer->getMembership();
        $this->assertInstanceOf(Membership::class, $membership);
        $this->assertSame('gold_member', $membership->getMembershipId());
        $this->assertSame('ゴールド会員', $membership->getName());
        $this->assertSame([
            'score' => 3000,
            'aggregation_period' => [
                'start_date' => 1780239600,
                'end_date' => 1782831599,
            ],
            'next_membership' => [
                'membership_id' => 'platinum',
                'name' => 'プラチナ会員',
                'required_score' => 4000,
                'remaining_score' => 1000,
            ],
        ], $membership->getProgress());

        $accounts = $customer->getExternalAccounts();
        $this->assertCount(1, $accounts);
        $this->assertContainsOnlyInstancesOf(ExternalAccount::class, $accounts);
        $this->assertSame(ExternalAccountProvider::LINE, $accounts[0]->getProvider());
        $this->assertSame('6cfd46d2fd18eb15d01ce0a00d4b0349', $accounts[0]->getUid());
        $this->assertSame(1465784934, $accounts[0]->getCreatedAt());
    }

    public function test_一覧取得でmembershipのprogressが省略されてもnullとして取得できる(): void
    {
        $customer = new Customer(self::fixtureData('customer_list_membership_without_progress'));

        $membership = $customer->getMembership();
        $this->assertInstanceOf(Membership::class, $membership);
        $this->assertSame('gold_member', $membership->getMembershipId());
        $this->assertSame('ゴールド会員', $membership->getName());
        $this->assertNull($membership->getProgress());
    }

    public function test_単体取得でmembershipのprogressが明示的なnullでも保持する(): void
    {
        $customer = new Customer(self::fixtureData('customer_membership_with_null_progress'));

        $membership = $customer->getMembership();
        $this->assertInstanceOf(Membership::class, $membership);
        $this->assertNull($membership->getProgress());
    }

    public function test_nullableな顧客フィールドが欠損していればnullになる(): void
    {
        $customer = new Customer(self::fixtureData('customer_with_missing_fields'));

        $this->assertNull($customer->getMembership());
        $this->assertNull($customer->getExternalAccounts());
    }

    public function test_nullableな顧客フィールドの明示的なnullを保持する(): void
    {
        $customer = new Customer(self::fixtureData('customer_with_nulls'));

        $this->assertNull($customer->getMembership());
        $this->assertNull($customer->getExternalAccounts());
    }

    public function test_external_accountsの空配列をnullと区別して保持する(): void
    {
        $customer = new Customer(self::fixtureData('customer_with_empty_external_accounts'));

        $this->assertSame([], $customer->getExternalAccounts());
    }

    #[DataProvider('missingTimestampProvider')]
    public function test_timestampフィールドが欠損していれば固有例外になる(
        string $field,
        string $getter,
    ): void {
        $customer = new Customer(self::fixtureData('customer_with_missing_fields'));

        $this->expectException(MissingFieldException::class);
        $this->expectExceptionMessage(
            Customer::class . ' の API フィールド『' . $field . '』が欠損しています。',
        );

        $customer->{$getter}();
    }

    #[DataProvider('invalidCustomerFieldProvider')]
    public function test_顧客フィールドの不正型は固有例外になる(string $field, mixed $value): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(
            Customer::class . ' の API フィールド『' . $field . '』が不正です。',
        );

        new Customer([$field => $value]);
    }

    #[DataProvider('invalidMembershipFieldProvider')]
    public function test_membership内部フィールドの不正型は実際のフィールドを示す固有例外になる(
        string $field,
        mixed $value,
    ): void {
        try {
            new Customer(['membership' => [$field => $value]]);
            $this->fail('InvalidFieldException が送出されませんでした。');
        } catch (InvalidFieldException $exception) {
            $this->assertStringContainsString(
                Customer::class . ' の API フィールド『membership』が不正です。',
                $exception->getMessage(),
            );

            $cause = $exception->getPrevious();
            $this->assertInstanceOf(InvalidFieldException::class, $cause);
            $this->assertStringContainsString(
                Membership::class . ' の API フィールド『' . $field . '』が不正です。',
                $cause->getMessage(),
            );
        }
    }

    #[DataProvider('invalidExternalAccountFieldProvider')]
    public function test_external_accounts要素の内部フィールド不正は要素変換と実フィールドを示す固有例外になる(
        string $field,
        mixed $value,
    ): void {
        try {
            new Customer(['external_accounts' => [[$field => $value]]]);
            $this->fail('InvalidFieldException が送出されませんでした。');
        } catch (InvalidFieldException $exception) {
            $this->assertSame(
                Customer::class
                    . ' の API フィールド『external_accounts』が不正です。'
                    . '配列要素を ' . ExternalAccount::class
                    . ' に変換できませんでした。原因: 配列要素を変換できませんでした。',
                $exception->getMessage(),
            );

            $cause = $exception->getPrevious();
            $this->assertInstanceOf(InvalidFieldException::class, $cause);
            $this->assertStringContainsString(
                ExternalAccount::class . ' の API フィールド『' . $field . '』が不正です。',
                $cause->getMessage(),
            );
        }
    }

    public function test_external_accounts配列要素の型が不正なら要素変換の固有例外になる(): void
    {
        $elements = self::fixtureData('invalid_external_account_elements');

        try {
            new Customer(['external_accounts' => $elements]);
            $this->fail('InvalidFieldException が送出されませんでした。');
        } catch (InvalidFieldException $exception) {
            $this->assertSame(
                Customer::class
                    . ' の API フィールド『external_accounts』が不正です。'
                    . '配列要素を ' . ExternalAccount::class
                    . ' に変換できませんでした。原因: 配列要素の型が不正です。',
                $exception->getMessage(),
            );
            $this->assertInstanceOf(\TypeError::class, $exception->getPrevious());
        }
    }

    /** @return array<string, array{string, string}> */
    public static function missingTimestampProvider(): array
    {
        return [
            'make_date' => ['make_date', 'getMakeDate'],
            'update_date' => ['update_date', 'getUpdateDate'],
        ];
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidCustomerFieldProvider(): array
    {
        $fields = self::fixtureData('invalid_customer_fields');
        $cases = [];
        foreach ($fields as $field => $value) {
            $cases[$field] = [$field, $value];
        }

        return $cases;
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidMembershipFieldProvider(): array
    {
        $fields = self::fixtureData('invalid_membership_fields');
        $cases = [];
        foreach ($fields as $field => $value) {
            $cases[$field] = [$field, $value];
        }

        return $cases;
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidExternalAccountFieldProvider(): array
    {
        $fields = self::fixtureData('invalid_external_account_fields');
        $cases = [];
        foreach ($fields as $field => $value) {
            $cases[$field] = [$field, $value];
        }

        return $cases;
    }

    /** @return array<string, mixed> */
    private static function fixtureData(string $key): array
    {
        $fixture = self::fixtureArray('customer_response_fields.json');
        $data = $fixture[$key] ?? null;
        if (! \is_array($data)) {
            throw new \RuntimeException('Customer fixture に配列データがありません: ' . $key);
        }

        return $data;
    }
}
