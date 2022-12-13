<?php

namespace Shimoning\ColorMeShopApi\Values;

use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use DateTimeInterface;

/**
 * 日付の型 (YYYY-MM-DD or YYYY-MM-DD hh:mm:ss)
 */
class DateTime
{
    private string $_date;

    /**
     * @param string|DateTimeInterface $date
     */
    public function __construct(string|DateTimeInterface $date)
    {
        if (\is_string($date)) {
            if (! \preg_match('/^\d{4}-\d{2}-\d{2}(\s\d{2}:\d{2}:\d{2})?$/', $date)) {
                throw new ParameterException('日付は "文字列" で "YYYY-MM-DD" もしくは "YYYY-MM-DD hh:mm:ss" の形式で入力してください。');
            }
            $this->_date = $date;
        } else if ($date instanceof DateTimeInterface) {
            $this->_date = $date->format('YYYY-MM-DD hh:mm:ss');
        } else {
            throw new ParameterException('日付は "文字列" もしくは "DateTimeInterface を継承したオブジェクト" を入れてください。');
        }
    }

    /**
     * 取得
     * @return string
     */
    public function get(): string
    {
        return $this->_date;
    }
}
