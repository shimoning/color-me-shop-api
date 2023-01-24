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
     */
    public function __construct(int $limit)
    {
        if (! $this->validate($limit)) {
            throw new ParameterException('件数は 1 ~ 100 の間で指定してください。 : '  . $limit);
        }
        $this->_limit = $limit;
    }

    public function get(): int
    {
        return $this->_limit;
    }

    public function validate(mixed $value): bool
    {
        return 0 < $value && $value <= 100;
    }
}
