<?php

namespace Shimoning\ColorMeShopApi\Entities;

/**
 * ページネーション情報を持つ型付きコレクション。
 *
 * @template T
 * @extends Collection<T>
 */
class Page extends Collection
{
    protected Pagination $_pagination;

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
