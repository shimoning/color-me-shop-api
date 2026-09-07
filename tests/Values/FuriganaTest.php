<?php

namespace Shimoning\ColorMeShopApi\Tests\Values;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Values\Furigana;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;

class FuriganaTest extends TestCase
{
    #[DataProvider('validProvider')]
    public function test_カタカナを受け取りそのまま保持する(string $input): void
    {
        $this->assertSame($input, (new Furigana($input))->get());
    }

    public static function validProvider(): array
    {
        return [
            'カタカナ' => ['ヤマダタロウ'],
            '小書き文字' => ['キャッシュ'],
            '長音符' => ['コーヒー'],
            '半角スペース区切り' => ['ヤマダ タロウ'],
            '全角スペース区切り' => ['ヤマダ　タロウ'],
            '濁点付き' => ['ヴァイオリン'],
            'ヶ' => ['ヶ'],
        ];
    }

    #[DataProvider('invalidProvider')]
    public function test_カタカナ以外はParameterExceptionを投げる(string $input): void
    {
        $this->expectException(ParameterException::class);
        $this->expectExceptionMessage('フリガナはカタカナで入力してください。');

        new Furigana($input);
    }

    public static function invalidProvider(): array
    {
        return [
            '空文字' => [''],
            'ひらがな' => ['やまだたろう'],
            '漢字' => ['山田太郎'],
            '英字' => ['Yamada'],
            '数字' => ['123'],
            '半角カナ' => ['ﾔﾏﾀﾞﾀﾛｳ'],
            'カタカナと漢字の混在' => ['ヤマダ太郎'],
            '中黒' => ['ヤマダ・タロウ'],
        ];
    }

    public function test_validateは判定結果を返す(): void
    {
        $furigana = new Furigana('ヤマダ');

        $this->assertTrue($furigana->validate('タロウ'));
        $this->assertFalse($furigana->validate('たろう'));
    }
}
