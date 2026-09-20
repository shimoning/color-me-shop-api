<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;

class RequestEntitySerializationTest extends TestCase
{
    public function test_未設定フィールドはignoreNullに関係なく要求に含めない(): void
    {
        $entity = new RequestSerializationRoot([]);

        $this->assertSame([], $entity->toArrayRecursive());
        $this->assertSame([], $entity->toArrayRecursive(false));
    }

    public function test_明示したnullは要求に含める(): void
    {
        $entity = new RequestSerializationRoot(['name' => null]);

        $this->assertSame(['name' => null], $entity->toArrayRecursive());
    }

    public function test_明示した値だけを要求に含める(): void
    {
        $entity = new RequestSerializationRoot(['name' => 'root']);

        $this->assertSame(['name' => 'root'], $entity->toArrayRecursive());
    }

    public function test_明示フィールドはcloneとserializeでも保持する(): void
    {
        $entity = new RequestSerializationRoot(['name' => null]);
        $clone = clone $entity;
        $restored = \unserialize(\serialize($entity), [
            'allowed_classes' => [RequestSerializationRoot::class],
        ]);

        $this->assertSame(['name' => null], $clone->toArrayRecursive());
        $this->assertInstanceOf(RequestSerializationRoot::class, $restored);
        $this->assertSame(['name' => null], $restored->toArrayRecursive());
    }

    public function test_ネストした入力Entityとその配列にも明示フィールド契約を再帰適用する(): void
    {
        $entity = new RequestSerializationRoot([
            'child' => ['name' => null],
            'children' => [
                ['name' => 'first'],
                ['note' => null],
            ],
        ]);

        $this->assertSame([
            'child' => ['name' => null],
            'children' => [
                ['name' => 'first'],
                ['note' => null],
            ],
        ], $entity->toArrayRecursive());
    }

    public function test_応答Entityの既定のnull省略契約は変わらない(): void
    {
        $entity = new ResponseSerializationEntity(['name' => null]);

        $this->assertSame([], $entity->toArrayRecursive());
        $this->assertSame(['name' => null], $entity->toArrayRecursive(false));
    }
}

final class RequestSerializationRoot extends Entity implements RequestEntity
{
    public const OBJECT_FIELDS = [
        'child' => ['entity' => RequestSerializationChild::class],
        'children' => ['array' => true, 'entity' => RequestSerializationChild::class],
    ];

    protected ?string $name;
    protected ?string $note;
    protected ?RequestSerializationChild $child;
    /** @var list<RequestSerializationChild>|null */
    protected ?array $children;
}

final class RequestSerializationChild extends Entity implements RequestEntity
{
    protected ?string $name;
    protected ?string $note;
}

final class ResponseSerializationEntity extends Entity
{
    protected ?string $name;
}
