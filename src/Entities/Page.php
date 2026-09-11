<?php

namespace Shimoning\ColorMeShopApi\Entities;

/**
 * ページネーション情報を持つ型付きコレクション。
 *
 * @template T
 * @extends Collection<T>
 * @phpstan-consistent-constructor
 */
class Page extends Collection
{
    protected Pagination $_pagination;

    /**
     * API レスポンスからページを生成する。
     *
     * @template TEntity of object
     * @param class-string<TEntity> $class 要素として生成するエンティティクラス
     * @param mixed $data API レスポンスの配列
     * @param string $key 要素が格納されているキー
     * @param string $metaKey ページネーション情報が格納されているキー
     * @return static<TEntity>
     */
    public static function build(string $class, mixed $data, string $key, string $metaKey = 'meta'): static
    {
        return new static(
            Collection::cast($class, $data[$key] ?? []),
            new Pagination($data[$metaKey] ?? []),
        );
    }

    /**
     * ページを生成する。
     *
     * @param mixed $items ページに格納する要素
     * @param Pagination $pagination ページネーション情報
     * @return void
     */
    public function __construct(mixed $items, Pagination $pagination)
    {
        parent::__construct($items);
        $this->_pagination = $pagination;
    }

    /**
     * 合計数
     * @return int
     */
    public function getTotal(): int
    {
        return $this->_pagination?->getTotal() ?? 0;
    }

    /**
     * 取得件数
     * @return int
     */
    public function getLimit(): int
    {
        return $this->_pagination?->getLimit() ?? 0;
    }

    /**
     * 取得開始位置
     * @return int
     */
    public function getOffset(): int
    {
        return $this->_pagination?->getOffset() ?? 0;
    }
}
