<?php

namespace Shimoning\ColorMeShopApi\Entities;

class Pages extends Collection
{
    protected Pagination $_pagination;

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
