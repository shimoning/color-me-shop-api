<?php

namespace Shimoning\ColorMeShopApi\Entities\OAuth;

use Shimoning\ColorMeShopApi\Communicator\Response;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * RFC 6749 で定義された OAuth エラーレスポンス。
 */
class ErrorResponse extends Entity
{
    protected string $error;
    protected ?string $errorDescription = null;
    protected ?string $errorUri = null;
    protected ?string $state = null;
    private Response $_response;

    /**
     * @param array<string, mixed> $data OAuth エラーレスポンスデータ
     * @param Response $response 元の HTTP レスポンス
     */
    public function __construct(array $data, Response $response)
    {
        parent::__construct($data);
        $this->_response = $response;
    }

    /**
     * OAuth エラーコードを取得する。
     */
    public function getError(): string
    {
        $this->assertFieldInitialized('error');

        return $this->error;
    }

    /**
     * 人間が読める補足説明を取得する。
     */
    public function getErrorDescription(): ?string
    {
        return $this->errorDescription;
    }

    /**
     * エラーの説明ページを取得する。
     */
    public function getErrorUri(): ?string
    {
        return $this->errorUri;
    }

    /**
     * 認可リクエストと応答を対応付ける state を取得する。
     */
    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * 元の HTTP レスポンスを取得する。
     */
    public function getResponse(): Response
    {
        return $this->_response;
    }
}
