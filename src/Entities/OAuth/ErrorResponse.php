<?php

namespace Shimoning\ColorMeShopApi\Entities\OAuth;

use Shimoning\ColorMeShopApi\Communicator\Response;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * RFC 6749 §5.2 で定義されたトークンエンドポイントのエラー応答。
 */
class ErrorResponse extends Entity
{
    protected string $error;
    protected ?string $errorDescription = null;
    protected ?string $errorUri = null;
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
     * （非推奨）state を取得する。
     *
     * トークンエラー応答の `state` は `toArray()` に含まれず、存在する場合は `getRaw()` から取得できる。
     * 認可コールバックの `state` には Services\OAuth::getUrl() を使用する。
     *
     * @deprecated 0.19.0
     * @return string|null
     * @see docs/api-oauth-token-error-observation.md
     * @see docs/adr/0021-drop-state-from-token-error-response.md
     */
    public function getState(): ?string
    {
        $state = $this->getRaw()['state'] ?? null;

        return \is_string($state) ? $state : null;
    }

    /**
     * 元の HTTP レスポンスを取得する。
     */
    public function getResponse(): Response
    {
        return $this->_response;
    }
}
