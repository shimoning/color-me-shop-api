<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Product\MetaTag;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

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

    #[DataProvider('nullableFieldProvider')]
    public function test_公式スキーマでnullableな項目はnullを取得できる(
        string $field,
        string $getter,
    ): void {
        $data = [
            'title' => '',
            'keywords' => '',
            'description' => '',
        ];
        $data[$field] = null;

        $metaTag = new MetaTag($data);

        $this->assertNull($metaTag->{$getter}());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function nullableFieldProvider(): array
    {
        return [
            'title' => ['title', 'getTitle'],
            'keywords' => ['keywords', 'getKeywords'],
            'description' => ['description', 'getDescription'],
        ];
    }

    #[DataProvider('missingFieldProvider')]
    public function test_公式スキーマでnullableな項目は欠損時にnullを返す(
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

        // 公式スキーマ上 nullable のため、従来の欠損例外ではなく Entity 共通契約の null を期待する。
        $this->assertNull($metaTag->{$getter}());
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
            // null は公式スキーマ上有効になったため、型不正の期待値を配列に変更する。
            'title が配列' => ['title', []],
            'title が整数' => ['title', 1],
            'keywords が配列' => ['keywords', []],
            'description が整数' => ['description', 1],
        ];
    }
}
