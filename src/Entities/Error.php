<?php

namespace Shimoning\ColorMeShopApi\Entities;

class Error extends Entity
{
    protected string $code;
    protected string $message;
    protected int $status;

    /**
     * エラーコードを取得
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * エラーメッセージを取得
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * ステータスコードを取得
     * @return string
     */
    public function getStatus(): int
    {
        return $this->status;
    }
}
