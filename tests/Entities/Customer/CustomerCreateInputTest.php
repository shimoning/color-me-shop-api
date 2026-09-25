<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities\Customer;

use Shimoning\ColorMeShopApi\Constants\Prefecture;
use Shimoning\ColorMeShopApi\Constants\Sex;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Customer\CustomerCreateInput;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;
use Shimoning\ColorMeShopApi\Values\Furigana;

class CustomerCreateInputTest extends TestCase
{
    /** @return array<string, mixed> */
    private static function required(array $overrides = []): array
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

    public function test_必須6件だけを与えるとそのまま送信できる(): void
    {
        $input = new CustomerCreateInput(self::required());

        $this->assertInstanceOf(RequestEntity::class, $input);
        $this->assertSame([
            'name' => 'カラーミー太郎',
            'mail' => 'taro@example.com',
            'pref_id' => 13,
            'postal' => '1508512',
            'address1' => '渋谷区桜丘町26-1',
            'tel' => '03-5456-2622',
        ], $input->toArrayRecursive());
    }

    public function test_必須フィールドが欠けていても構築はできる(): void
    {
        // 必須の検証は送信前に Services\Customer::create() が行う (ADR 0015)。
        // 入力 Entity は他の RequestEntity と同じく、空の構築を許して直列化しない契約を保つ。
        $this->assertSame([], (new CustomerCreateInput([]))->toArrayRecursive());
    }

    public function test_必須フィールドのnullは型として拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('name');

        new CustomerCreateInput(self::required(['name' => null]));
    }

    public function test_任意フィールドは明示したものだけ送信する(): void
    {
        $input = new CustomerCreateInput(self::required([
            'furigana' => 'カラーミータロウ',
            'add_member' => true,
        ]));

        $fields = $input->toArrayRecursive();
        $this->assertSame('カラーミータロウ', $fields['furigana']);
        $this->assertTrue($fields['add_member']);
        $this->assertArrayNotHasKey('fax', $fields);
        $this->assertArrayNotHasKey('other', $fields);
    }

    public function test_明示したnullは送信する(): void
    {
        $fields = (new CustomerCreateInput(self::required(['address2' => null])))->toArrayRecursive();

        $this->assertArrayHasKey('address2', $fields);
        $this->assertNull($fields['address2']);
    }

    public function test_都道府県はenumインスタンスでも指定できバッキング値で送信する(): void
    {
        $input = new CustomerCreateInput(self::required(['pref_id' => Prefecture::OSAKA]));

        $this->assertSame(Prefecture::OSAKA, $input->toArray()['pref_id']);
        $this->assertSame(29, $input->toArrayRecursive()['pref_id']);
    }

    public function test_フリガナは文字列で指定しValueへ変換して保持する(): void
    {
        $input = new CustomerCreateInput(self::required(['furigana' => 'カラーミータロウ']));

        $this->assertInstanceOf(Furigana::class, $input->toArray()['furigana']);
        $this->assertSame('カラーミータロウ', $input->toArrayRecursive()['furigana']);
    }

    /**
     * Value フィールドは生の値で指定する契約で、Value のインスタンスは受け付けない。
     * Entity::buildObject() が常に Value のコンストラクタへ渡すためで、Customer\SearchParameters など
     * 既存の Value フィールドと同じ挙動である。
     */
    public function test_フリガナにValueのインスタンスは渡せない(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('furigana');

        new CustomerCreateInput(self::required(['furigana' => new Furigana('カラーミータロウ')]));
    }

    public function test_フリガナの明示したnullは送信する(): void
    {
        $fields = (new CustomerCreateInput(self::required(['furigana' => null])))->toArrayRecursive();

        $this->assertArrayHasKey('furigana', $fields);
        $this->assertNull($fields['furigana']);
    }

    public function test_作成では性別と携帯電話番号を送信しない(): void
    {
        $fields = (new CustomerCreateInput(self::required([
            'sex' => Sex::MALE,
            'tel_mobile' => '090-1234-5678',
        ])))->toArrayRecursive();

        $this->assertArrayNotHasKey('sex', $fields);
        $this->assertArrayNotHasKey('tel_mobile', $fields);
    }

    public function test_未定義の都道府県は要求側なので拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('pref_id');

        new CustomerCreateInput(self::required(['pref_id' => 49]));
    }

    public function test_不正な型を拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('pref_id');

        new CustomerCreateInput(self::required(['pref_id' => '13']));
    }
}
