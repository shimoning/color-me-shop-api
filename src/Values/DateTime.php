<?php

namespace Shimoning\ColorMeShopApi\Values;

use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use DateTimeInterface;

/**
 * 日付の型 (YYYY-MM-DD or YYYY-MM-DD hh:mm:ss)
 */
class DateTime implements Value
{
    private string $_date;

    /**
     * @param string|DateTimeInterface $date
     * @return void
     * @throws ParameterException 日付の形式が不正な場合
     */
    public function __construct(string|DateTimeInterface $date)
    {
        if (\is_string($date)) {
            // $ は末尾の改行にもマッチするため、文字列の終端を表す \z を使う。
            // 日付と時刻の区切りは半角スペースのみ (\s だと改行やタブも通ってしまう)
            if (! \preg_match('/\A\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?\z/', $date)) {
                throw new ParameterException('日付は "文字列" で "YYYY-MM-DD" もしくは "YYYY-MM-DD hh:mm:ss" の形式で入力してください。 : ' . $date);
            }
            $this->_date = $date;
        } else if ($date instanceof DateTimeInterface) {
            $this->_date = $date->format('Y-m-d H:i:s');
        } else {
            throw new ParameterException('日付は "文字列" もしくは "DateTimeInterface を継承したオブジェクト" を入れてください。');
        }
    }

    /**
     * 日付文字列を取得する。
     *
     * @return string
     */
    public function get(): string
    {
        return $this->_date;
    }

    /**
     * 日付を検証する。
     *
     * @param mixed $date 検証する値
     * @return bool
     */
    public function validate($date): bool
    {
        // TODO: merge construct
        return true;
    }
}
