<?php

namespace Shimoning\ColorMeShopApi\Tests\Exceptions;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Exceptions\ColorMeApiException;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Exceptions\InvalidPaginationException;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;
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

    public function test_MissingPaginationExceptionのファクトリはサブクラス型を返す(): void
    {
        $exception = MissingPaginationException::for(self::class, 'total');

        $this->assertSame(MissingPaginationException::class, $exception::class);
        $this->assertInstanceOf(MissingFieldException::class, $exception);
        $this->assertSame(
            self::class . ' の API フィールド『total』が欠損しています。',
            $exception->getMessage(),
        );
    }

    public function test_InvalidPaginationExceptionのforはサブクラス型を返す(): void
    {
        $exception = InvalidPaginationException::for(self::class, 'limit', 'int', null);

        $this->assertSame(InvalidPaginationException::class, $exception::class);
        $this->assertInstanceOf(InvalidFieldException::class, $exception);
        $this->assertSame(
            self::class . ' の API フィールド『limit』が不正です。'
            . 'int を期待しましたが null でした。',
            $exception->getMessage(),
        );
    }

    public function test_InvalidPaginationExceptionのforArrayElementはサブクラス型を返す(): void
    {
        $previous = new \RuntimeException('internal');

        $exception = InvalidPaginationException::forArrayElement(
            self::class,
            'items',
            \stdClass::class,
            $previous,
        );

        $this->assertSame(InvalidPaginationException::class, $exception::class);
        $this->assertInstanceOf(InvalidFieldException::class, $exception);
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame(
            self::class . ' の API フィールド『items』が不正です。'
            . '配列要素を ' . \stdClass::class . ' に変換できませんでした。'
            . '原因: 配列要素を変換できませんでした。',
            $exception->getMessage(),
        );
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

    public function test_期待型と実型の表示が同じ場合も矛盾したメッセージを生成しない(): void
    {
        $previous = new \RuntimeException('conversion failed');

        $exception = InvalidFieldException::for(
            self::class,
            'items',
            'array',
            [],
            $previous,
        );

        $this->assertSame(
            self::class . ' の API フィールド『items』が不正です。'
            . 'array として扱える値に変換できませんでした。',
            $exception->getMessage(),
        );
        $this->assertDoesNotMatchRegularExpression(
            '/(.+) を期待しましたが \1 でした。/u',
            $exception->getMessage(),
        );
        $this->assertSame($previous, $exception->getPrevious());
    }
}
