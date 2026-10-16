<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\RequestEntity;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Tests\Doubles\AutomaticRequiredFieldsRequestEntity;
use Shimoning\ColorMeShopApi\Tests\Doubles\DefaultRequiredFieldsRequestEntity;
use Shimoning\ColorMeShopApi\Tests\Doubles\RequiredEntity;

class RequestEntityRequiredFieldsTest extends TestCase
{
    public function test_コンストラクタを持たない要求Entityも必須フィールドを検証する(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(AutomaticRequiredFieldsRequestEntity::class);
        $this->expectExceptionMessage('必須: required_field');
        $this->expectExceptionMessage('未指定: required_field');

        new AutomaticRequiredFieldsRequestEntity([]);
    }

    public function test_REQUIRED_FIELDSを宣言しない要求Entityは空配列で生成できる(): void
    {
        $entity = new DefaultRequiredFieldsRequestEntity([]);

        $this->assertSame([], RequestEntity::REQUIRED_FIELDS);
        $this->assertSame([], $entity->toArrayRecursive());
    }

    public function test_応答側Entityは必須フィールド検証の対象にならない(): void
    {
        $entity = new RequiredEntity([]);

        $this->assertFalse(\defined(RequiredEntity::class . '::REQUIRED_FIELDS'));
        $this->assertSame([], $entity->getRaw());
    }
}
