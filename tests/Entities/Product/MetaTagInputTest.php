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

    public function test_親の入力に公式キーだけの配列とnullと配列以外は通す(): void
    {
        MetaTagInput::assertOwnerField(MetaTagInput::class, ['title' => 'x', 'keywords' => null, 'description' => '']);
        MetaTagInput::assertOwnerField(MetaTagInput::class, null);
        MetaTagInput::assertOwnerField(MetaTagInput::class, 'string'); // 型は親の構築時に検証する

        $this->assertTrue(true);
    }

    /**
     * 公式 OpenAPI の meta_tag は additionalProperties: false。公式キーと並んだ未知キーも
     * 黙って捨てずに拒否し、利用者の誤り (typo など) を隠さない。
     */
    #[DataProvider('unknownKeyProvider')]
    public function test_親の入力に公式キー以外のキーがあれば拒否する(array $value): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage('meta_tag');
        MetaTagInput::assertOwnerField(MetaTagInput::class, $value);
    }

    /** @return array<string, array{array<mixed>}> */
    public static function unknownKeyProvider(): array
    {
        return [
            'typo beside valid key' => [['title' => 'x', 'titel' => 'typo']],
            'unknown keys only' => [['titel' => 'x']],
            'empty array' => [[]],
            'list' => [['title']],
            'valid key and list element' => [['title' => 'x', 'y']],
        ];
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
