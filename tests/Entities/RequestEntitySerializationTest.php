<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Entities\Product\SearchParameters;
use Shimoning\ColorMeShopApi\Entities\Sales\SaleUpdateInput;

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

    public function test_明示フィールド追跡追加前の直列化データは従来のnull省略で復元する(): void
    {
        $encoded = \file_get_contents(__DIR__ . '/../Fixtures/request_entity_before_explicit_fields.base64');
        $this->assertNotFalse($encoded);
        $serialized = \base64_decode(\trim($encoded), true);
        $this->assertNotFalse($serialized);

        $restored = \unserialize($serialized, [
            'allowed_classes' => [SearchParameters::class],
        ]);

        $this->assertInstanceOf(SearchParameters::class, $restored);
        $this->assertSame(['fields' => 'id,name'], $restored->toArrayRecursive());
    }

    /**
     * 固定データは 0.14.0 の改名前に旧クラス名 `Sales\SaleUpdater` で直列化したものである。
     * 旧名は Aliases の遅延 autoloader で新クラスへ解決されるため、allowed_classes には旧名を渡す。
     */
    public function test_明示フィールド追跡追加前の直列化データへsetterで値を追加しても既存値を保持する(): void
    {
        $encoded = \file_get_contents(__DIR__ . '/../Fixtures/request_entity_with_setter_before_explicit_fields.base64');
        $this->assertNotFalse($encoded);
        $serialized = \base64_decode(\trim($encoded), true);
        $this->assertNotFalse($serialized);

        $legacy = 'Shimoning\\ColorMeShopApi\\Entities\\Sales\\SaleUpdater';
        $restored = \unserialize($serialized, [
            'allowed_classes' => [$legacy],
        ]);

        $this->assertInstanceOf(SaleUpdateInput::class, $restored);
        $restored->setPaid(true);
        $this->assertSame(['id' => 1001, 'paid' => true], $restored->toArrayRecursive());
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
