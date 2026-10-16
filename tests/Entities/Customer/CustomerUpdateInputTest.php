<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities\Customer;

use Shimoning\ColorMeShopApi\Constants\Prefecture;
use Shimoning\ColorMeShopApi\Constants\Sex;
use Shimoning\ColorMeShopApi\Entities\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Customer\CustomerUpdateInput;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;
use Shimoning\ColorMeShopApi\Values\Furigana;

class CustomerUpdateInputTest extends TestCase
{
    /** @return array<string, mixed> */
    private static function required(array $overrides = []): array
    {
        return \array_merge([
            'name' => 'カラーミー花子',
            'address1' => '渋谷区',
            'address2' => '桜丘町26-1',
        ], $overrides);
    }

    public function test_明示したフィールドだけを送信する(): void
    {
        $input = new CustomerUpdateInput(self::required());

        $this->assertInstanceOf(RequestEntity::class, $input);
        $this->assertSame(self::required(), $input->toArrayRecursive());
    }

    public function test_必須フィールドが欠けているとクラス名と必須一覧と欠落一覧を示して拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(CustomerUpdateInput::class);
        $this->expectExceptionMessage('必須: name, address1, address2');
        $this->expectExceptionMessage('未指定: address1, address2');

        new CustomerUpdateInput(['name' => 'カラーミー花子']);
    }

    public function test_nullableなフィールドの明示したnullは送信する(): void
    {
        $fields = (new CustomerUpdateInput(self::required([
            'address2' => null,
            'fax' => null,
            'sex' => null,
            'birthday' => null,
            'furigana' => null,
            'tel_mobile' => null,
            'other' => null,
            'receive_mail_magazine' => null,
            'answer_free_form1' => null,
        ])))->toArrayRecursive();

        $this->assertSame([
            'name' => 'カラーミー花子',
            'address1' => '渋谷区',
            'furigana' => null,
            'address2' => null,
            'fax' => null,
            'sex' => null,
            'birthday' => null,
            'receive_mail_magazine' => null,
            'answer_free_form1' => null,
            'other' => null,
        ], $fields);
    }

    /**
     * 公式 OpenAPI で nullable 指定のないフィールドは、クリアする意味を持たないため
     * 明示した null を型として拒否する (ADR 0015)。
     */
    public function test_nullable指定のないフィールドのnullは拒否する(): void
    {
        foreach (['name', 'mail', 'pref_id', 'postal', 'address1', 'tel'] as $field) {
            try {
                new CustomerUpdateInput(self::required([$field => null]));
                $this->fail($field . ' の null が拒否されなかった');
            } catch (InvalidFieldException $e) {
                $this->assertStringContainsString($field, $e->getMessage());
            }
        }
    }

    public function test_性別は送信でき携帯電話番号は送信しない(): void
    {
        $input = new CustomerUpdateInput(self::required([
            'sex' => 'female', 'tel_mobile' => '090-1234-5678',
        ]));

        $this->assertSame(Sex::FEMALE, $input->toArray()['sex']);
        $this->assertSame('female', $input->toArrayRecursive()['sex']);
    }

    public function test_性別はenumインスタンスでも指定できバッキング値で送信する(): void
    {
        $input = new CustomerUpdateInput(self::required(['sex' => Sex::NOT_APPLICABLE]));

        $this->assertSame('not_applicable', $input->toArrayRecursive()['sex']);
    }

    public function test_性別の番兵値は要求側なので拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('sex');

        new CustomerUpdateInput(self::required(['sex' => Sex::UNKNOWN]));
    }

    public function test_未知の性別は要求側なので拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('sex');

        new CustomerUpdateInput(self::required(['sex' => 'unknown-value']));
    }

    public function test_都道府県はenumインスタンスでも指定できバッキング値で送信する(): void
    {
        $input = new CustomerUpdateInput(self::required(['pref_id' => Prefecture::OSAKA]));

        $this->assertSame(29, $input->toArrayRecursive()['pref_id']);
    }

    public function test_フリガナは文字列で指定しValueへ変換して保持する(): void
    {
        $input = new CustomerUpdateInput(self::required(['furigana' => 'カラーミーハナコ']));

        $this->assertInstanceOf(Furigana::class, $input->toArray()['furigana']);
        $this->assertSame('カラーミーハナコ', $input->toArrayRecursive()['furigana']);
    }

    public function test_更新では会員登録フラグを送信しない(): void
    {
        $fields = (new CustomerUpdateInput(self::required(['add_member' => true])))->toArrayRecursive();

        $this->assertArrayNotHasKey('add_member', $fields);
    }

    public function test_不正な型を拒否する(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('pref_id');

        new CustomerUpdateInput(self::required(['pref_id' => '13']));
    }
}
