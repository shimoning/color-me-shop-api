<?php

namespace Shimoning\ColorMeShopApi\Entities;

class Pagination extends Entity
{
    protected int $total;
    protected int $limit;
    protected int $offset;

    /**
     * 合計数
     * @return int
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * 取得件数
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * 取得開始位置
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }
}
