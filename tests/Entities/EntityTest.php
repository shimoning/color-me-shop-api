<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Constants\MailState;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Exceptions\InvalidPaginationException;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;
use Shimoning\ColorMeShopApi\Exceptions\MissingPaginationException;
use Shimoning\ColorMeShopApi\Values\Limit;
use Shimoning\ColorMeShopApi\Tests\Doubles\PlainEntity;
use Shimoning\ColorMeShopApi\Tests\Doubles\PrivateFieldEntity;
use Shimoning\ColorMeShopApi\Tests\Doubles\ComplexEntity;
use Shimoning\ColorMeShopApi\Tests\Doubles\NestedEntity;
use Shimoning\ColorMeShopApi\Tests\Doubles\RequiredEntity;
use Shimoning\ColorMeShopApi\Tests\Doubles\RelativeTypeEntity;
use Shimoning\ColorMeShopApi\Tests\Doubles\RelativeTypeParentEntity;
use Shimoning\ColorMeShopApi\Tests\Doubles\DnfIntersectionValue;
use Shimoning\ColorMeShopApi\Tests\Doubles\HydratedTypeMismatchEntity;

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

    public function test_array指定のentityフィールドに非配列が来たらフィールドの型不一致を示す(): void
    {
        try {
            new ComplexEntity(['children' => 'invalid']);
        } catch (InvalidFieldException $exception) {
            $this->assertSame(
                ComplexEntity::class . ' の API フィールド『children』が不正です。array を期待しましたが string でした。',
                $exception->getMessage(),
            );
            $this->assertStringNotContainsString('Entity::isHash()', $exception->getMessage());
            $this->assertStringNotContainsString(\dirname(__DIR__, 2), $exception->getMessage());

            return;
        }

        $this->fail(InvalidFieldException::class . ' が投げられませんでした。');
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

    public function test_enumに存在しない値は汎用例外で早期に失敗する(): void
    {
        // 未知の enum を null として扱っていた旧仕様を、不正値として扱う契約へ更新する。
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(
            ComplexEntity::class . ' の API フィールド『state』が不正です。'
            . MailState::class . ' を期待しましたが string でした。',
        );

        new ComplexEntity(['state' => 'unknown']);
    }

    public function test_enumのbacking型と異なる値は汎用例外で早期に失敗する(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(
            ComplexEntity::class . ' の API フィールド『state』が不正です。'
            . MailState::class . ' を期待しましたが int でした。',
        );

        new ComplexEntity(['state' => 1]);
    }

    public function test_array指定のenumフィールドはenumの配列に変換される(): void
    {
        $entity = new ComplexEntity(['states' => ['sent', 'not_yet']]);

        $this->assertSame([MailState::SENT, MailState::NOT_YET], $entity->getStates());
    }

    public function test_array指定のenumフィールドに非配列が来たらフィールドの型不一致を示す(): void
    {
        try {
            new ComplexEntity(['states' => 'sent']);
        } catch (InvalidFieldException $exception) {
            $this->assertSame(
                ComplexEntity::class . ' の API フィールド『states』が不正です。array を期待しましたが string でした。',
                $exception->getMessage(),
            );
            $this->assertStringNotContainsString('array_map()', $exception->getMessage());
            $this->assertStringNotContainsString(\dirname(__DIR__, 2), $exception->getMessage());

            return;
        }

        $this->fail(InvalidFieldException::class . ' が投げられませんでした。');
    }

    public function test_array指定のenumフィールドに未知の値があると要素型と原因を示す(): void
    {
        try {
            new ComplexEntity(['states' => ['sent', 'unknown']]);
        } catch (InvalidFieldException $exception) {
            $this->assertSame(
                ComplexEntity::class . ' の API フィールド『states』が不正です。'
                . '配列要素を ' . MailState::class . ' に変換できませんでした。'
                . '原因: 未知の enum 値です。',
                $exception->getMessage(),
            );
            $this->assertInstanceOf(\UnexpectedValueException::class, $exception->getPrevious());

            return;
        }

        $this->fail(InvalidFieldException::class . ' が投げられませんでした。');
    }

    public function test_array指定のenumフィールドの要素型が不正でも内部メソッド名を露出しない(): void
    {
        try {
            new ComplexEntity(['states' => [1]]);
        } catch (InvalidFieldException $exception) {
            $this->assertSame(
                ComplexEntity::class . ' の API フィールド『states』が不正です。'
                . '配列要素を ' . MailState::class . ' に変換できませんでした。'
                . '原因: 配列要素の型が不正です。',
                $exception->getMessage(),
            );
            $this->assertStringNotContainsString('tryFrom', $exception->getMessage());
            $this->assertInstanceOf(\TypeError::class, $exception->getPrevious());
            $this->assertStringContainsString('tryFrom', $exception->getPrevious()->getMessage());

            return;
        }

        $this->fail(InvalidFieldException::class . ' が投げられませんでした。');
    }

    public function test_array指定のentityフィールドの要素形状が不正でも内部パスを露出しない(): void
    {
        try {
            new ComplexEntity(['children' => ['string']]);
        } catch (InvalidFieldException $exception) {
            $this->assertSame(
                ComplexEntity::class . ' の API フィールド『children』が不正です。'
                . '配列要素を ' . NestedEntity::class . ' に変換できませんでした。'
                . '原因: 配列要素の型が不正です。',
                $exception->getMessage(),
            );
            $this->assertStringNotContainsString('__construct', $exception->getMessage());
            $this->assertStringNotContainsString(\dirname(__DIR__, 2), $exception->getMessage());
            $this->assertStringNotContainsString('.php', $exception->getMessage());
            $this->assertInstanceOf(\TypeError::class, $exception->getPrevious());
            $this->assertStringContainsString('__construct', $exception->getPrevious()->getMessage());
            $this->assertStringContainsString(\dirname(__DIR__, 2), $exception->getPrevious()->getMessage());

            return;
        }

        $this->fail(InvalidFieldException::class . ' が投げられませんでした。');
    }

    // --- 欠損・不正フィールド ---------------------------------------------

    public function test_非nullableフィールドの欠損はgetter呼び出し時に汎用例外を投げる(): void
    {
        $entity = new RequiredEntity([]);

        $this->expectException(MissingFieldException::class);
        $this->expectExceptionMessage(
            RequiredEntity::class . ' の API フィールド『name』が欠損しています。',
        );

        $entity->getName();
    }

    public function test_nullableフィールドの欠損はnullを返す(): void
    {
        $entity = new RequiredEntity([]);

        $this->assertNull($entity->getDescription());
    }

    public function test_private宣言のフィールドを検証して初期化する(): void
    {
        $entity = new PrivateFieldEntity(['name' => '山田']);

        $this->assertSame('山田', $entity->getName());
    }

    #[DataProvider('invalidFieldProvider')]
    public function test_存在する不正値は汎用例外で早期に失敗する(
        string $class,
        array $data,
        string $field,
        string $expected,
        string $actual,
    ): void {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(
            $class . " の API フィールド『{$field}』が不正です。"
            . "{$expected} を期待しましたが {$actual} でした。",
        );

        new $class($data);
    }

    /**
     * @return array<string, array{class-string<Entity>, array<string, mixed>, string, string, string}>
     */
    public static function invalidFieldProvider(): array
    {
        return [
            'null' => [RequiredEntity::class, ['name' => null], 'name', 'string', 'null'],
            '型違い' => [RequiredEntity::class, ['count' => '1'], 'count', 'int', 'string'],
            '不正なネスト形状' => [
                ComplexEntity::class,
                ['child' => 'invalid'],
                'child',
                NestedEntity::class,
                'string',
            ],
            '値オブジェクトの変換失敗' => [ComplexEntity::class, ['limit' => 0], 'limit', Limit::class, 'int'],
            '値オブジェクトの引数型違い' => [
                ComplexEntity::class,
                ['limit' => '1'],
                'limit',
                Limit::class,
                'string',
            ],
        ];
    }

    public function test_変換後の型不一致は変換後の実型を示す(): void
    {
        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage(
            HydratedTypeMismatchEntity::class . ' の API フィールド『child』が不正です。'
            . 'array を期待しましたが ' . NestedEntity::class . ' でした。',
        );

        new HydratedTypeMismatchEntity(['child' => ['label' => 'child']]);
    }

    public function test_self型はプロパティの宣言クラスとして検証する(): void
    {
        $sameType = new RelativeTypeEntity([]);
        $entity = new RelativeTypeEntity(['same_type' => $sameType]);

        $this->assertSame($sameType, $entity->getSameType());
    }

    public function test_parent型はプロパティの宣言クラスの親として検証する(): void
    {
        $parentType = new RelativeTypeParentEntity([]);
        $entity = new RelativeTypeEntity(['parent_type' => $parentType]);

        $this->assertSame($parentType, $entity->getParentType());
    }

    public function test_DNF型は交差型を含めて再帰的に検証する(): void
    {
        if (\PHP_VERSION_ID < 80200) {
            $this->markTestSkipped('DNF 型の構文は PHP 8.2 以降でのみ利用できます。');
        }

        $class = 'Shimoning\\ColorMeShopApi\\Tests\\Doubles\\DnfTypeEntity';
        $subject = new DnfIntersectionValue();
        $entity = (new \ReflectionClass($class))->newInstance(['subject' => $subject]);

        $this->assertSame($subject, (new \ReflectionMethod($class, 'getSubject'))->invoke($entity));
    }

    public function test_ページネーション固有例外は汎用例外としても捕捉できる(): void
    {
        $this->assertInstanceOf(MissingFieldException::class, new MissingPaginationException());
        $this->assertInstanceOf(InvalidFieldException::class, new InvalidPaginationException());
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
