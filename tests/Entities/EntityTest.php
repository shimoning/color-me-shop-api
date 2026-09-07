<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Constants\MailState;
use Shimoning\ColorMeShopApi\Values\Limit;
use Shimoning\ColorMeShopApi\Tests\Doubles\PlainEntity;
use Shimoning\ColorMeShopApi\Tests\Doubles\ComplexEntity;
use Shimoning\ColorMeShopApi\Tests\Doubles\NestedEntity;

class EntityTest extends TestCase
{
    // --- キーの変換 -------------------------------------------------------

    public function test_snake_caseのキーをcamelCaseのプロパティに割り当てる(): void
    {
        $entity = new PlainEntity(['some_long_name' => 'value']);

        $this->assertSame('value', $entity->getSomeLongName());
    }

    public function test_単語1つのキーもそのまま割り当てる(): void
    {
        $entity = new PlainEntity(['name' => '山田']);

        $this->assertSame('山田', $entity->getName());
    }

    public function test_定義されていないキーは無視される(): void
    {
        $entity = new PlainEntity(['name' => 'a', 'undefined_key' => 'b']);

        $this->assertSame('a', $entity->getName());
        $this->assertArrayNotHasKey('undefined_key', $entity->toArray());
    }

    public function test_渡されなかったプロパティはtoArrayでnullになる(): void
    {
        $entity = new PlainEntity(['name' => 'a']);

        $this->assertNull($entity->toArray()['count']);
    }

    // --- getRaw -----------------------------------------------------------

    public function test_getRawは渡された生データをそのまま返す(): void
    {
        $raw = ['name' => 'a', 'undefined_key' => 'b'];

        $this->assertSame($raw, (new PlainEntity($raw))->getRaw());
    }

    public function test_getRawは空配列も保持する(): void
    {
        $this->assertSame([], (new PlainEntity([]))->getRaw());
    }

    // --- OBJECT_FIELDS: entity -------------------------------------------

    public function test_entity指定のフィールドはエンティティに変換される(): void
    {
        $entity = new ComplexEntity(['child' => ['label' => 'c']]);

        $this->assertInstanceOf(NestedEntity::class, $entity->getChild());
        $this->assertSame('c', $entity->getChild()->getLabel());
    }

    public function test_array指定のentityフィールドはエンティティの配列に変換される(): void
    {
        $entity = new ComplexEntity(['children' => [['label' => 'x'], ['label' => 'y']]]);

        $this->assertCount(2, $entity->getChildren());
        $this->assertContainsOnlyInstancesOf(NestedEntity::class, $entity->getChildren());
        $this->assertSame(['x', 'y'], \array_map(fn($c) => $c->getLabel(), $entity->getChildren()));
    }

    public function test_array指定でも連想配列が来たら単一要素の配列に包む(): void
    {
        $entity = new ComplexEntity(['children' => ['label' => 'solo']]);

        $this->assertCount(1, $entity->getChildren());
        $this->assertSame('solo', $entity->getChildren()[0]->getLabel());
    }

    public function test_配列指定でないobjectFieldは単体のエンティティを生成する(): void
    {
        $entity = new ComplexEntity(['bare' => ['label' => 'b']]);

        $this->assertInstanceOf(NestedEntity::class, $entity->getBare());
        $this->assertSame('b', $entity->getBare()->getLabel());
    }

    // --- OBJECT_FIELDS: nullable -----------------------------------------

    public function test_nullable指定でnullならnullになる(): void
    {
        $entity = new ComplexEntity(['nullable_child' => null]);

        $this->assertNull($entity->getNullableChild());
    }

    public function test_nullableかつarray指定でnullなら空配列になる(): void
    {
        $entity = new ComplexEntity(['nullable_children' => null]);

        $this->assertSame([], $entity->getNullableChildren());
    }

    // --- OBJECT_FIELDS: value --------------------------------------------

    public function test_value指定のフィールドは値オブジェクトに変換される(): void
    {
        $entity = new ComplexEntity(['limit' => 20]);

        $this->assertInstanceOf(Limit::class, $entity->getLimit());
        $this->assertSame(20, $entity->getLimit()->get());
    }

    public function test_array指定のvalueフィールドは値オブジェクトの配列に変換される(): void
    {
        $entity = new ComplexEntity(['limits' => [1, 100]]);

        $this->assertContainsOnlyInstancesOf(Limit::class, $entity->getLimits());
        $this->assertSame([1, 100], \array_map(fn($l) => $l->get(), $entity->getLimits()));
    }

    // --- OBJECT_FIELDS: enum ---------------------------------------------

    public function test_enum指定のフィールドはenumに変換される(): void
    {
        $entity = new ComplexEntity(['state' => 'sent']);

        $this->assertSame(MailState::SENT, $entity->getState());
    }

    public function test_enum指定でnullならnullになる(): void
    {
        $entity = new ComplexEntity(['state' => null]);

        $this->assertNull($entity->getState());
    }

    public function test_enumに存在しない値はnullになる(): void
    {
        $entity = new ComplexEntity(['state' => 'unknown']);

        $this->assertNull($entity->getState());
    }

    public function test_array指定のenumフィールドはenumの配列に変換される(): void
    {
        $entity = new ComplexEntity(['states' => ['sent', 'not_yet', 'unknown']]);

        $this->assertSame([MailState::SENT, MailState::NOT_YET, null], $entity->getStates());
    }

    // --- isHash -----------------------------------------------------------

    #[DataProvider('isHashProvider')]
    public function test_isHashは文字列キーの有無を判定する(array $input, bool $expected): void
    {
        $this->assertSame($expected, Entity::isHash($input));
    }

    public static function isHashProvider(): array
    {
        return [
            '空配列' => [[], false],
            '連番配列' => [[1, 2, 3], false],
            '連想配列' => [['a' => 1], true],
            '混在' => [[0 => 1, 'a' => 2], true],
            '飛び番の数値キー' => [[3 => 'a', 7 => 'b'], false],
        ];
    }

    // --- toArray ----------------------------------------------------------

    public function test_toArrayはキーをsnake_caseに戻す(): void
    {
        $entity = new PlainEntity(['name' => 'a', 'count' => 1, 'some_long_name' => 'b']);

        $this->assertSame(
            ['name' => 'a', 'count' => 1, 'some_long_name' => 'b'],
            $entity->toArray(),
        );
    }

    public function test_toArrayは生データのキーを含めない(): void
    {
        $entity = new PlainEntity(['name' => 'a']);

        $this->assertArrayNotHasKey('_raw', $entity->toArray());
    }

    public function test_toArrayはオブジェクトを変換せずそのまま返す(): void
    {
        $entity = new ComplexEntity(['child' => ['label' => 'c']]);

        $this->assertInstanceOf(NestedEntity::class, $entity->toArray()['child']);
    }

    // --- toArrayRecursive -------------------------------------------------

    public function test_toArrayRecursiveはデフォルトでnullのキーを除外する(): void
    {
        $entity = new PlainEntity(['name' => 'a']);

        $this->assertSame(['name' => 'a'], $entity->toArrayRecursive());
    }

    public function test_toArrayRecursiveはignoreNullをfalseにするとnullも残す(): void
    {
        $entity = new PlainEntity(['name' => 'a']);

        $this->assertSame(
            ['name' => 'a', 'count' => null, 'some_long_name' => null],
            $entity->toArrayRecursive(false),
        );
    }

    public function test_toArrayRecursiveはネストしたエンティティを配列に変換する(): void
    {
        $entity = new ComplexEntity([
            'child' => ['label' => 'c'],
            'children' => [['label' => 'x'], ['label' => 'y']],
        ]);

        $this->assertSame(
            [
                'child' => ['label' => 'c'],
                'children' => [['label' => 'x'], ['label' => 'y']],
            ],
            $entity->toArrayRecursive(),
        );
    }

    public function test_toArrayRecursiveは値オブジェクトを素の値に変換する(): void
    {
        $entity = new ComplexEntity(['limit' => 20, 'limits' => [1, 100]]);

        $this->assertSame(
            ['limit' => 20, 'limits' => [1, 100]],
            $entity->toArrayRecursive(),
        );
    }

    public function test_toArrayRecursiveはenumをbackedValueに変換する(): void
    {
        $entity = new ComplexEntity(['state' => 'sent', 'states' => ['sent', 'not_yet']]);

        $this->assertSame(
            ['state' => 'sent', 'states' => ['sent', 'not_yet']],
            $entity->toArrayRecursive(),
        );
    }

    public function test_toArrayRecursiveは生データのキーを含めない(): void
    {
        $entity = new PlainEntity(['name' => 'a']);

        $this->assertArrayNotHasKey('_raw', $entity->toArrayRecursive(false));
    }

    // --- parse ------------------------------------------------------------

    public function test_parseはスカラー値をそのまま返す(): void
    {
        $entity = new PlainEntity([]);

        $this->assertSame('a', $entity->parse('a'));
        $this->assertSame(1, $entity->parse(1));
        $this->assertNull($entity->parse(null));
        $this->assertTrue($entity->parse(true));
    }

    public function test_parseはエンティティを配列に変換する(): void
    {
        $entity = new PlainEntity([]);

        $this->assertSame(['label' => 'c'], $entity->parse(new NestedEntity(['label' => 'c'])));
    }

    public function test_parseはenumをbackedValueに変換する(): void
    {
        $entity = new PlainEntity([]);

        $this->assertSame('sent', $entity->parse(MailState::SENT));
    }

    public function test_parseは値オブジェクトを素の値に変換する(): void
    {
        $entity = new PlainEntity([]);

        $this->assertSame(10, $entity->parse(new Limit(10)));
    }
}
