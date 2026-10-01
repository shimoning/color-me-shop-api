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
     * トークンエンドポイントのエラー応答（RFC 6749 §5.2）は state を定義しておらず、
     * 2026-09-30 の実測でも実 API は返さなかった。
     * 詳細は docs/api-oauth-token-error-observation.md を参照。
     *
     * 認可リクエストと応答を対応付ける state は、認可エンドポイントのコールバックの
     * クエリで受け取るものであり、この値ではない。Services\OAuth::getUrl() を参照。
     *
     * 応答に state が含まれていれば従来どおり文字列を返すが、次のメジャーな変更で削除する。
     * state は toArray() には含まれないが、getRaw() から取得できる。
     *
     * @deprecated 0.19.0
     * @return string|null
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
