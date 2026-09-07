<?php

namespace Shimoning\ColorMeShopApi\Tests\Entities;

use PHPUnit\Framework\TestCase;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use Shimoning\ColorMeShopApi\Tests\Doubles\NestedEntity;

class CollectionTest extends TestCase
{
    // --- コンストラクタ ---------------------------------------------------

    public function test_引数なしで空のコレクションを作る(): void
    {
        $collection = new Collection();

        $this->assertSame([], $collection->all());
        $this->assertSame(0, $collection->count());
    }

    public function test_配列から生成する(): void
    {
        $collection = new Collection(['a', 'b']);

        $this->assertSame(['a', 'b'], $collection->all());
    }

    public function test_他のコレクションから中身をコピーして生成する(): void
    {
        $source = new Collection(['a', 'b']);
        $collection = new Collection($source);

        $this->assertSame(['a', 'b'], $collection->all());
    }

    public function test_コピー元を変更してもコピー先に影響しない(): void
    {
        $source = new Collection(['a']);
        $collection = new Collection($source);

        $source[] = 'b';

        $this->assertSame(['a'], $collection->all());
    }

    public function test_スカラー値は配列にキャストされる(): void
    {
        $this->assertSame(['a'], (new Collection('a'))->all());
        $this->assertSame([], (new Collection(null))->all());
    }

    // --- cast -------------------------------------------------------------

    public function test_castは各要素を指定クラスのインスタンスに変換する(): void
    {
        $collection = Collection::cast(NestedEntity::class, [['label' => 'x'], ['label' => 'y']]);

        $this->assertCount(2, $collection);
        $this->assertContainsOnlyInstancesOf(NestedEntity::class, $collection->all());
        $this->assertSame('x', $collection[0]->getLabel());
        $this->assertSame('y', $collection[1]->getLabel());
    }

    public function test_castは空配列から空のコレクションを作る(): void
    {
        $this->assertSame([], Collection::cast(NestedEntity::class, [])->all());
    }

    public function test_castに配列以外を渡すとParameterExceptionを投げる(): void
    {
        $this->expectException(ParameterException::class);

        Collection::cast(NestedEntity::class, 'not an array');
    }

    public function test_castはCollectionを返す(): void
    {
        $this->assertInstanceOf(Collection::class, Collection::cast(NestedEntity::class, []));
    }

    // --- ArrayAccess ------------------------------------------------------

    public function test_オフセットで要素を取得できる(): void
    {
        $collection = new Collection(['a', 'b']);

        $this->assertSame('a', $collection[0]);
        $this->assertSame('b', $collection[1]);
    }

    public function test_オフセットの存在を判定できる(): void
    {
        $collection = new Collection(['a']);

        $this->assertTrue(isset($collection[0]));
        $this->assertFalse(isset($collection[1]));
    }

    public function test_オフセットを指定して代入できる(): void
    {
        $collection = new Collection(['a']);
        $collection[0] = 'z';

        $this->assertSame(['z'], $collection->all());
    }

    public function test_オフセットを省略した代入は末尾に追加する(): void
    {
        $collection = new Collection(['a']);
        $collection[] = 'b';

        $this->assertSame(['a', 'b'], $collection->all());
    }

    public function test_オフセットを削除できる(): void
    {
        $collection = new Collection(['a', 'b']);
        unset($collection[0]);

        $this->assertSame([1 => 'b'], $collection->all());
    }

    public function test_値がnullの要素も存在するとみなす(): void
    {
        $collection = new Collection([null]);

        // offsetExists() は array_key_exists() を使うため、素の配列に isset() した場合と異なり
        // 値が null でも「存在する」と判定される
        $this->assertTrue($collection->offsetExists(0));
        $this->assertTrue(isset($collection[0]));
        $this->assertFalse(isset([null][0]));
    }

    // --- IteratorAggregate ------------------------------------------------

    public function test_foreachで走査できる(): void
    {
        $collection = new Collection(['a', 'b', 'c']);

        $result = [];
        foreach ($collection as $key => $value) {
            $result[$key] = $value;
        }

        $this->assertSame(['a', 'b', 'c'], $result);
    }

    public function test_getIteratorはTraversableを返す(): void
    {
        $this->assertInstanceOf(\Traversable::class, (new Collection(['a']))->getIterator());
    }

    // --- count ------------------------------------------------------------

    public function test_countメソッドは要素数を返す(): void
    {
        $this->assertSame(0, (new Collection())->count());
        $this->assertSame(3, (new Collection(['a', 'b', 'c']))->count());
    }
}
