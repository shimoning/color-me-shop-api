<?php

namespace Shimoning\ColorMeShopApi\Entities;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Traversable;
use ArrayIterator;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;

/**
 * API レスポンスの要素を保持する型付きコレクション。
 *
 * @template T
 * @implements ArrayAccess<array-key, T>
 * @implements IteratorAggregate<array-key, T>
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate
{
    /** @var array<array-key, T> */
    protected array $_items = [];

    /**
     * コレクションを生成する。
     *
     * @param mixed $items コレクションに格納する要素
     * @return void
     */
    public function __construct(mixed $items = [])
    {
        if (\is_array($items)) {
            $this->_items = $items;
        } else if ($items instanceof self) {
            $this->_items = $items->all();
        } else {
            $this->_items = (array)$items;
        }
    }

    /**
     * 配列の各要素を指定したクラスへ変換する。
     *
     * @template TEntity of object
     * @param class-string<TEntity> $class 変換先のクラス名
     * @param mixed $items 変換元の要素
     * @return self<TEntity>
     * @throws ParameterException 変換元が配列でない場合
     */
    static public function cast(string $class, mixed $items): self
    {
        if (! \is_array($items)) {
            throw new ParameterException();
        }
        return new self(
            array_map(function ($item) use ($class) {
                return new $class($item);
            }, $items),
        );
    }

    /**
     * すべての要素を取得する。
     *
     * @return array<array-key, T>
     */
    public function all(): array
    {
        return $this->_items;
    }

    /**
     * 要素数
     *
     * Countable の実装。これがないと PHP 8 では count() が TypeError になる。
     *
     * @return int
     */
    public function count(): int
    {
        return \count($this->_items);
    }

    /**
     * 指定したオフセットが存在するかを判定する。
     *
     * @param mixed $offset オフセット
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return \array_key_exists($offset, $this->_items);
    }

    /**
     * 指定したオフセットの要素を取得する。
     *
     * @param mixed $offset オフセット
     * @return T
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->_items[$offset];
    }

    /**
     * 指定したオフセットに要素を設定する。
     *
     * @param mixed $offset オフセット。null の場合は末尾に追加する
     * @param T $value 設定する要素
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (\is_null($offset)) {
            $this->_items[] = $value;
        } else {
            $this->_items[$offset] = $value;
        }
    }

    /**
     * 指定したオフセットの要素を削除する。
     *
     * @param mixed $offset オフセット
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->_items[$offset]);
    }

    /**
     * 要素を反復するイテレータを取得する。
     *
     * @return Traversable<array-key, T>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->_items);
    }
}
