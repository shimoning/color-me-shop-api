<?php

namespace Shimoning\ColorMeShopApi\Entities;

use ArrayAccess;
use IteratorAggregate;
use Traversable;
use ArrayIterator;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;

class Collection implements ArrayAccess, IteratorAggregate
{
    protected array $_items = [];

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

    public function all(): array
    {
        return $this->_items;
    }

    public function count(): int
    {
        return \count($this->_items);
    }

    public function offsetExists(mixed $offset): bool
    {
        return \array_key_exists($offset, $this->_items);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->_items[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (\is_null($offset)) {
            $this->_items[] = $value;
        } else {
            $this->_items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->_items[$offset]);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->_items);
    }
}
