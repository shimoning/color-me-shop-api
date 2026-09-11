<?php

namespace Shimoning\ColorMeShopApi\Tests\Exceptions;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Exceptions\ColorMeApiException;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Exceptions\InvalidPaginationException;
use Shimoning\ColorMeShopApi\Exceptions\MissingPaginationException;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;

class ExceptionTest extends TestCase
{
    public function test_ColorMeApiExceptionはExceptionを継承している(): void
    {
        $this->assertInstanceOf(\Exception::class, new ColorMeApiException());
    }

    public function test_ParameterExceptionはColorMeApiExceptionを継承している(): void
    {
        $this->assertInstanceOf(ColorMeApiException::class, new ParameterException());
    }

    public function test_ページネーション例外はColorMeApiExceptionを継承している(): void
    {
        $this->assertInstanceOf(ColorMeApiException::class, new MissingPaginationException());
        $this->assertInstanceOf(ColorMeApiException::class, new InvalidPaginationException());
    }

    public function test_ライブラリの例外をまとめて捕捉できる(): void
    {
        try {
            throw new ParameterException('メッセージ');
        } catch (ColorMeApiException $e) {
            $this->assertSame('メッセージ', $e->getMessage());
            return;
        }

        $this->fail('ColorMeApiException で捕捉できなかった');
    }

    public function test_配列要素の原因を分類できない場合は内部メッセージを露出しない(): void
    {
        $previous = new \RuntimeException('sensitive method at /tmp/internal.php:123');

        $exception = InvalidFieldException::forArrayElement(
            self::class,
            'items',
            \stdClass::class,
            $previous,
        );

        $this->assertSame(
            self::class . ' の API フィールド『items』が不正です。'
            . '配列要素を ' . \stdClass::class . ' に変換できませんでした。'
            . '原因: 配列要素を変換できませんでした。',
            $exception->getMessage(),
        );
        $this->assertSame($previous, $exception->getPrevious());
    }
}
