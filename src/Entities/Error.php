<?php

namespace Shimoning\ColorMeShopApi\Entities;

class Error extends Entity
{
    protected string $code;
    protected string $message;
    protected ?string $field;
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
     * 対象フィールドを取得
     * @return string|null
     */
    public function getField(): ?string
    {
        return $this->field;
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
