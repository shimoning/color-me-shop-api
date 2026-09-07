<?php

namespace Shimoning\ColorMeShopApi\Tests\Constants;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Constants\ErrorCode;

class ErrorCodeTest extends TestCase
{
    public function test_enumとしてロードできる(): void
    {
        $this->assertTrue(\enum_exists(ErrorCode::class));
        $this->assertCount(3, ErrorCode::cases());
    }

    /**
     * Entities\Error::$code が string のため、backed value も string で揃える。
     */
    public function test_backedValueは文字列である(): void
    {
        foreach (ErrorCode::cases() as $case) {
            $this->assertIsString($case->value, $case->name . ' の値が文字列ではない');
        }
    }

    public function test_値はAPIのエラーコードと一致する(): void
    {
        $this->assertSame('401010', ErrorCode::UNAUTHORIZED->value);
        $this->assertSame('404100', ErrorCode::NOT_FOUND->value);
        $this->assertSame('422210', ErrorCode::VALIDATE_ERROR_FIELD->value);
    }

    public function test_APIが返すエラーコードから復元できる(): void
    {
        $this->assertSame(ErrorCode::UNAUTHORIZED, ErrorCode::tryFrom('401010'));
        $this->assertNull(ErrorCode::tryFrom('999999'));
    }

    // --- message() --------------------------------------------------------

    public function test_messageは全ケース分のメッセージを返す(): void
    {
        $messages = ErrorCode::message();

        $this->assertCount(\count(ErrorCode::cases()), $messages);

        foreach (ErrorCode::cases() as $case) {
            $this->assertArrayHasKey($case->value, $messages, $case->name . ' のメッセージが未定義');
            $this->assertNotSame('', $messages[$case->value]);
        }
    }

    public function test_messageはエラーコードで引ける(): void
    {
        $messages = ErrorCode::message();

        $this->assertSame('レコードが見つかりませんでした。', $messages[ErrorCode::NOT_FOUND->value]);
        $this->assertSame('パラメータが指定されていません。', $messages[ErrorCode::VALIDATE_ERROR_FIELD->value]);
        $this->assertStringContainsString('有効なアクセストークンが見つからない', $messages[ErrorCode::UNAUTHORIZED->value]);
    }

    public function test_APIのエラーレスポンスからメッセージを引ける(): void
    {
        $code = ErrorCode::tryFrom('401010');

        $this->assertNotNull($code);
        $this->assertArrayHasKey($code->value, ErrorCode::message());
    }
}
