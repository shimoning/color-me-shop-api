<?php

namespace Shimoning\ColorMeShopApi\Values;

use Shimoning\ColorMeShopApi\Exceptions\ParameterException;

/**
 * 件数
 */
class Limit implements Value
{
    private int $_limit = 10;

    /**
     * @param int $limit
     * @return void
     * @throws ParameterException 件数が許容範囲外の場合
     */
    public function __construct(int $limit)
    {
        if (! $this->validate($limit)) {
            throw new ParameterException('件数は 1 ~ 100 の間で指定してください。 : '  . $limit);
        }
        $this->_limit = $limit;
    }

    /**
     * 件数を取得する。
     *
     * @return int
     */
    public function get(): int
    {
        return $this->_limit;
    }

    /**
     * 件数を検証する。
     *
     * @param mixed $value 検証する値
     * @return bool
     */
    public function validate(mixed $value): bool
    {
        return 0 < $value && $value <= 100;
    }
}
