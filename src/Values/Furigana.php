<?php

namespace Shimoning\ColorMeShopApi\Values;

use Shimoning\ColorMeShopApi\Exceptions\ParameterException;

/**
 * フリガナ (カタカナ)
 */
class Furigana implements Value
{
    private string $_furigana;

    /**
     * @param string $furigana
     */
    public function __construct(string $furigana)
    {
        if (! $this->validate($furigana)) {
            throw new ParameterException('フリガナはカタカナで入力してください。');
        }
        $this->_furigana = $furigana;
    }

    /**
     * 取得
     * @return string
     */
    public function get(): string
    {
        return $this->_furigana;
    }

    /**
     * バリデーション
     *
     * @param mixed $value
     * @return boolean
     */
    public function validate(mixed $value): bool
    {
        return preg_match('/\A[ァ-ヶー 　]+\z/u', $value);
    }
}
