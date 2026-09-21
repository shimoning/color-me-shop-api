<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities\Product;

use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Product\OptionInput;
use Shimoning\ColorMeShopApi\Entities\Product\OptionValueInput;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class OptionInputTest extends TestCase
{
    public function test_名前と値の配列をoptionボディ形式へ変換する(): void
    {
        $input = new OptionInput([
            'name' => 'サイズ',
            'values' => [['name' => 'S'], ['name' => 'M']],
        ]);

        $this->assertInstanceOf(RequestEntity::class, $input);
        $this->assertContainsOnlyInstancesOf(OptionValueInput::class, $input->toArray()['values']);
        $this->assertSame([
            'name' => 'サイズ',
            'values' => [['name' => 'S'], ['name' => 'M']],
        ], $input->toArrayRecursive());
    }

    public function test_未指定のフィールドは送信しない(): void
    {
        $this->assertSame([], (new OptionInput([]))->toArrayRecursive());
        $this->assertSame(['name' => '色'], (new OptionInput(['name' => '色']))->toArrayRecursive());
    }

    #[DataProvider('invalidFieldProvider')]
    public function test_必須フィールドのnullと不正な型を拒否する(string $field, mixed $value): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage($field);
        new OptionInput([$field => $value]);
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidFieldProvider(): array
    {
        return [
            'name null' => ['name', null],
            'name int' => ['name', 1],
            'values null' => ['values', null],
            'values string' => ['values', 'S'],
            'values element string' => ['values', ['S']],
            'values hash instead of list' => ['values', [1 => ['name' => 'S']]],
            'values string keyed hash' => ['values', ['s' => ['name' => 'S']]],
            'values element name int' => ['values', [['name' => 1]]],
            'values element empty array' => ['values', [[]]],
            'values element without name' => ['values', [['value' => 'S']]],
            'values element unknown key only' => ['values', [['nam' => 'S']]],
            'values element null' => ['values', [null]],
            'values second element empty' => ['values', [['name' => 'S'], []]],
        ];
    }
}
