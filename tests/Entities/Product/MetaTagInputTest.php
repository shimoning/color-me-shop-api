<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product;

use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Product\MetaTagInput;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class MetaTagInputTest extends TestCase
{
    public function test_タイトルとキーワードと説明をmeta_tagボディ形式へ変換する(): void
    {
        $input = new MetaTagInput(['title' => '夏物特集', 'keywords' => '夏物,衣類', 'description' => '夏物の一覧']);

        $this->assertInstanceOf(RequestEntity::class, $input);
        $this->assertSame(
            ['title' => '夏物特集', 'keywords' => '夏物,衣類', 'description' => '夏物の一覧'],
            $input->toArrayRecursive(),
        );
    }

    public function test_未指定は送信せず明示したnullは送信する(): void
    {
        $this->assertSame([], (new MetaTagInput([]))->toArrayRecursive());
        $this->assertSame(
            ['title' => null, 'keywords' => ''],
            (new MetaTagInput(['title' => null, 'keywords' => '']))->toArrayRecursive(),
        );
    }

    #[DataProvider('invalidFieldProvider')]
    public function test_文字列以外を拒否する(string $field, mixed $value): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage($field);
        new MetaTagInput([$field => $value]);
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidFieldProvider(): array
    {
        return [
            'title int' => ['title', 1],
            'keywords array' => ['keywords', ['a']],
            'description bool' => ['description', true],
        ];
    }
}
