<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Services;

use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Customer\Customer as CustomerEntity;
use Shimoning\ColorMeShopApi\Entities\Customer\CustomerCreateInput;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use Shimoning\ColorMeShopApi\Services\Customer;
use Shimoning\ColorMeShopApi\Tests\Support\HttpMock;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class CustomerWriteTest extends TestCase
{
    /** @return array<string, mixed> */
    private static function requiredFields(array $overrides = []): array
    {
        return \array_merge([
            'name' => 'カラーミー太郎',
            'mail' => 'taro@example.com',
            'pref_id' => 13,
            'postal' => '1508512',
            'address1' => '渋谷区桜丘町26-1',
            'tel' => '03-5456-2622',
        ], $overrides);
    }

    // --- create -----------------------------------------------------------

    public function test_顧客作成はcustomerキーのJSONをPOSTし顧客Entityを返す(): void
    {
        $mock = HttpMock::json(200, self::fixture('customer.json'));

        $customer = (new Customer('my-token', $mock->client()))
            ->create(new CustomerCreateInput(self::requiredFields()));

        $this->assertInstanceOf(CustomerEntity::class, $customer);
        $this->assertSame(501, $customer->getId());
    }

    public function test_顧客作成は正しいエンドポイントへPOSTする(): void
    {
        $mock = HttpMock::json(200, self::fixture('customer.json'));

        (new Customer('my-token', $mock->client()))
            ->create(new CustomerCreateInput(self::requiredFields()));

        $this->assertSame('POST', $mock->request()->getMethod());
        $this->assertSame('https://api.shop-pro.jp/v1/customers', $mock->uri());
        $this->assertSame('Bearer my-token', $mock->header('Authorization'));
        $this->assertSame('application/json; charset=utf-8', $mock->header('Content-Type'));
    }

    public function test_顧客作成は明示したフィールドだけをcustomerに載せる(): void
    {
        $mock = HttpMock::json(200, self::fixture('customer.json'));

        (new Customer('my-token', $mock->client()))->create(new CustomerCreateInput(
            self::requiredFields(['address2' => null, 'add_member' => true]),
        ));

        $this->assertSame([
            'customer' => [
                'name' => 'カラーミー太郎',
                'mail' => 'taro@example.com',
                'pref_id' => 13,
                'postal' => '1508512',
                'address1' => '渋谷区桜丘町26-1',
                'tel' => '03-5456-2622',
                'address2' => null,
                'add_member' => true,
            ],
        ], $mock->jsonBody());
    }

    public function test_顧客作成の422はErrorsとして返す(): void
    {
        $mock = HttpMock::json(422, self::fixture('errors_422.json'));

        $errors = (new Customer('my-token', $mock->client()))
            ->create(new CustomerCreateInput(self::requiredFields()));

        $this->assertInstanceOf(Errors::class, $errors);
        $this->assertSame(422, $errors->getResponse()->getStatus());
    }

    public function test_必須フィールドが欠けた入力は送信前に拒否する(): void
    {
        $mock = HttpMock::json(200, self::fixture('customer.json'));
        $data = self::requiredFields();
        unset($data['mail']);

        try {
            (new Customer('my-token', $mock->client()))->create(new CustomerCreateInput($data));
            $this->fail('必須フィールドの欠落が拒否されなかった');
        } catch (ParameterException $e) {
            $this->assertStringContainsString('未指定: mail', $e->getMessage());
        }

        $this->assertSame(0, $mock->countRequests(), 'API へ送信してはいけない');
    }

    public function test_欠落した必須フィールドをすべて列挙して拒否する(): void
    {
        $mock = HttpMock::json(200, self::fixture('customer.json'));

        $this->expectException(ParameterException::class);
        $this->expectExceptionMessage('未指定: name, mail, pref_id, postal, address1, tel');

        (new Customer('my-token', $mock->client()))->create(new CustomerCreateInput([]));
    }

    public function test_明示したnullは必須フィールドの指定として扱う(): void
    {
        // 必須フィールドは非 null のプロパティなので、明示した null は型として拒否される。
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('name');

        new CustomerCreateInput(self::requiredFields(['name' => null]));
    }

    public function test_顧客作成はアクセストークンが空だと拒否する(): void
    {
        $this->expectException(ParameterException::class);

        (new Customer('', (new HttpMock([]))->client()))
            ->create(new CustomerCreateInput(self::requiredFields()));
    }
}
