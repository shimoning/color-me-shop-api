<?php

namespace Shimoning\ColorMeShopApi\Entities;

/**
 * API が返したエラーの詳細。
 */
class Error extends Entity
{
    protected string $code;
    protected string $message;
    protected ?string $field = null;
    protected int $status;

    /**
     * エラーコードを取得
     * @return string
     */
    public function getCode(): string
    {
        $this->assertFieldInitialized('code');

        return $this->code;
    }

    /**
     * エラーメッセージを取得
     * @return string
     */
    public function getMessage(): string
    {
        $this->assertFieldInitialized('message');

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
     * @return int
     */
    public function getStatus(): int
    {
        $this->assertFieldInitialized('status');

        return $this->status;
    }
}
