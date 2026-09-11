<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Pagination;
use Shimoning\ColorMeShopApi\Exceptions\InvalidPaginationException;
use Shimoning\ColorMeShopApi\Exceptions\MissingPaginationException;

class PaginationTest extends TestCase
{
    public function test_APIのmetaからページング情報を組み立てる(): void
    {
        $pagination = new Pagination(['total' => 123, 'limit' => 50, 'offset' => 100]);

        $this->assertSame(123, $pagination->getTotal());
        $this->assertSame(50, $pagination->getLimit());
        $this->assertSame(100, $pagination->getOffset());
    }

    public function test_ゼロも保持する(): void
    {
        $pagination = new Pagination(['total' => 0, 'limit' => 0, 'offset' => 0]);

        $this->assertSame(0, $pagination->getTotal());
        $this->assertSame(0, $pagination->getLimit());
        $this->assertSame(0, $pagination->getOffset());
    }

    public function test_未定義のキーは無視される(): void
    {
        $pagination = new Pagination(['total' => 1, 'limit' => 1, 'offset' => 0, 'unknown' => 'x']);

        $this->assertArrayNotHasKey('unknown', $pagination->toArray());
    }

    #[DataProvider('missingPaginationProvider')]
    public function test_欠損したページング値はgetter呼び出し時に固有例外を投げる(
        array $meta,
        string $getter,
        string $message,
    ): void {
        $pagination = new Pagination($meta);

        $this->expectException(MissingPaginationException::class);
        $this->expectExceptionMessage($message);

        $pagination->{$getter}();
    }

    /**
     * @return array<string, array{array<string, mixed>, string, string}>
     */
    public static function missingPaginationProvider(): array
    {
        return [
            '空配列のtotal' => [
                [],
                'getTotal',
                'API レスポンスにページネーション情報「meta.total」がありません。ページング値を取得できません。',
            ],
            '空配列のlimit' => [
                [],
                'getLimit',
                'API レスポンスにページネーション情報「meta.limit」がありません。ページング値を取得できません。',
            ],
            '空配列のoffset' => [
                [],
                'getOffset',
                'API レスポンスにページネーション情報「meta.offset」がありません。ページング値を取得できません。',
            ],
            'totalのみ欠損' => [
                ['limit' => 10, 'offset' => 0],
                'getTotal',
                'API レスポンスにページネーション情報「meta.total」がありません。ページング値を取得できません。',
            ],
            'limitのみ欠損' => [
                ['total' => 1, 'offset' => 0],
                'getLimit',
                'API レスポンスにページネーション情報「meta.limit」がありません。ページング値を取得できません。',
            ],
            'offsetのみ欠損' => [
                ['total' => 1, 'limit' => 10],
                'getOffset',
                'API レスポンスにページネーション情報「meta.offset」がありません。ページング値を取得できません。',
            ],
            '複数キー欠損' => [
                ['total' => 1],
                'getLimit',
                'API レスポンスにページネーション情報「meta.limit」がありません。ページング値を取得できません。',
            ],
        ];
    }

    #[DataProvider('invalidPaginationProvider')]
    public function test_不正なmetaは固有例外で早期に失敗する(
        mixed $meta,
        string $message,
    ): void {
        $this->expectException(InvalidPaginationException::class);
        $this->expectExceptionMessage($message);

        new Pagination($meta);
    }

    /**
     * @return array<string, array{mixed, string}>
     */
    public static function invalidPaginationProvider(): array
    {
        return [
            'null' => [
                null,
                'API レスポンスのページネーション情報「meta」が不正です。array を期待しましたが null でした。',
            ],
            'totalがnull' => [
                ['total' => null, 'limit' => 10, 'offset' => 0],
                'API レスポンスのページネーション情報「meta.total」が不正です。int を期待しましたが null でした。',
            ],
            'limitがnull' => [
                ['total' => 1, 'limit' => null, 'offset' => 0],
                'API レスポンスのページネーション情報「meta.limit」が不正です。int を期待しましたが null でした。',
            ],
            'offsetがnull' => [
                ['total' => 1, 'limit' => 10, 'offset' => null],
                'API レスポンスのページネーション情報「meta.offset」が不正です。int を期待しましたが null でした。',
            ],
            '数値文字列' => [
                ['total' => '1', 'limit' => 10, 'offset' => 0],
                'API レスポンスのページネーション情報「meta.total」が不正です。int を期待しましたが string でした。',
            ],
            '非数値文字列' => [
                ['total' => 1, 'limit' => 'ten', 'offset' => 0],
                'API レスポンスのページネーション情報「meta.limit」が不正です。int を期待しましたが string でした。',
            ],
            'bool' => [
                ['total' => 1, 'limit' => 10, 'offset' => false],
                'API レスポンスのページネーション情報「meta.offset」が不正です。int を期待しましたが bool でした。',
            ],
            'float' => [
                ['total' => 1.0, 'limit' => 10, 'offset' => 0],
                'API レスポンスのページネーション情報「meta.total」が不正です。int を期待しましたが float でした。',
            ],
            '配列' => [
                ['total' => 1, 'limit' => [], 'offset' => 0],
                'API レスポンスのページネーション情報「meta.limit」が不正です。int を期待しましたが array でした。',
            ],
        ];
    }

    public function test_負数はAPIの整数値を改変せず保持する(): void
    {
        $pagination = new Pagination(['total' => -1, 'limit' => -10, 'offset' => -20]);

        $this->assertSame(-1, $pagination->getTotal());
        $this->assertSame(-10, $pagination->getLimit());
        $this->assertSame(-20, $pagination->getOffset());
    }
}
