<?php

namespace Shimoning\ColorMeShopApi\Entities;

class Error extends Entity
{
    private string $_code;
    private string $_message;
    private int $_status;

    /**
     * エラーコードを取得
     * @return string
     */
    public function getCode(): string
    {
        return $this->_code;
    }

    /**
     * エラーメッセージを取得
     * @return string
     */
    public function getMessage(): string
    {
        return $this->_message;
    }

    /**
     * ステータスコードを取得
     * @return string
     */
    public function getStatus(): int
    {
        return $this->_status;
    }
}
