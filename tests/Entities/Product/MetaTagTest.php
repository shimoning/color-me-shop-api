<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Product\MetaTag;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;

class MetaTagTest extends TestCase
{
    /**
     * 保存済みの実 API レスポンスでは、3項目すべてが空文字のケースが観測された。
     */
    public function test_実APIで観測した空文字をそのまま取得できる(): void
    {
        $data = [
            'title' => '',
            'keywords' => '',
            'description' => '',
        ];
        $metaTag = new MetaTag($data);

        $this->assertSame('', $metaTag->getTitle());
        $this->assertSame('', $metaTag->getKeywords());
        $this->assertSame('', $metaTag->getDescription());
        $this->assertSame($data, $metaTag->getRaw());
        $this->assertSame($data, $metaTag->toArray());
        $this->assertSame($data, $metaTag->toArrayRecursive());
    }

    #[DataProvider('missingFieldProvider')]
    public function test_一部キーが欠損していればgetter呼び出し時に固有例外になる(
        string $field,
        string $getter,
    ): void {
        $data = [
            'title' => '',
            'keywords' => '',
            'description' => '',
        ];
        unset($data[$field]);

        $metaTag = new MetaTag($data);

        $this->expectException(MissingFieldException::class);
        $this->expectExceptionMessage(
            MetaTag::class . " の API フィールド『{$field}』が欠損しています。",
        );

        $metaTag->{$getter}();
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function missingFieldProvider(): array
    {
        return [
            'title' => ['title', 'getTitle'],
            'keywords' => ['keywords', 'getKeywords'],
            'description' => ['description', 'getDescription'],
        ];
    }

    #[DataProvider('invalidFieldProvider')]
    public function test_フィールドの型が不正なら固有例外になる(string $field, mixed $value): void
    {
        $data = [
            'title' => '',
            'keywords' => '',
            'description' => '',
        ];
        $data[$field] = $value;

        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(
            MetaTag::class . " の API フィールド『{$field}』が不正です。string を期待しましたが",
        );

        new MetaTag($data);
    }

    /**
     * @return array<string, array{string, mixed}>
     */
    public static function invalidFieldProvider(): array
    {
        return [
            'title が null' => ['title', null],
            'keywords が配列' => ['keywords', []],
            'description が整数' => ['description', 1],
        ];
    }
}
