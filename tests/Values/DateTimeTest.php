<?php

namespace Shimoning\ColorMeShopApi\Tests\Values;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Values\DateTime;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;

class DateTimeTest extends TestCase
{
    #[DataProvider('validStringProvider')]
    public function test_文字列を受け取りそのまま保持する(string $input): void
    {
        $this->assertSame($input, (new DateTime($input))->get());
    }

    public static function validStringProvider(): array
    {
        return [
            '日付のみ' => ['2024-01-01'],
            '日時' => ['2024-01-01 12:34:56'],
            '月末' => ['2024-12-31 23:59:59'],
            // 実在しない日付でも形式が合っていれば通る (書式のみを検証する仕様)
            '実在しない日付' => ['2024-13-45'],
        ];
    }

    #[DataProvider('invalidStringProvider')]
    public function test_不正な形式の文字列はParameterExceptionを投げる(string $input): void
    {
        $this->expectException(ParameterException::class);
        $this->expectExceptionMessage('日付は "文字列" で "YYYY-MM-DD" もしくは "YYYY-MM-DD hh:mm:ss" の形式で入力してください。');

        new DateTime($input);
    }

    public static function invalidStringProvider(): array
    {
        return [
            '空文字' => [''],
            'スラッシュ区切り' => ['2024/01/01'],
            'ゼロ埋めなし' => ['2024-1-1'],
            '秒がない' => ['2024-01-01 12:34'],
            'ISO8601' => ['2024-01-01T12:34:56'],
            '年が5桁' => ['20240-01-01'],
            '前後に余分な文字' => ['date: 2024-01-01'],
        ];
    }

    public function test_DateTimeインターフェースを受け取り整形する(): void
    {
        $source = new \DateTime('2024-01-01 12:34:56');

        $this->assertSame('2024-01-01 12:34:56', (new DateTime($source))->get());
    }

    public function test_DateTimeImmutableも受け取れる(): void
    {
        $source = new \DateTimeImmutable('2024-06-15 00:00:00');

        $this->assertSame('2024-06-15 00:00:00', (new DateTime($source))->get());
    }

    public function test_Valueインターフェースを実装している(): void
    {
        $this->assertInstanceOf(\Shimoning\ColorMeShopApi\Values\Value::class, new DateTime('2024-01-01'));
    }

    /**
     * validate() は現状 TODO のまま常に true を返す (仕様化テスト)。
     * バリデーションはコンストラクタ側で行われている。
     */
    public function test_validateは常にtrueを返す(): void
    {
        $dateTime = new DateTime('2024-01-01');

        $this->assertTrue($dateTime->validate('まったく日付ではない文字列'));
    }
}
