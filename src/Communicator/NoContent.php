<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Communicator;

/**
 * 204 No Content の成功応答を表す値オブジェクト。
 *
 * ボディ由来の Entity ではなく、Errors と同じく元の Response を保持する。
 */
final class NoContent
{
    public function __construct(private Response $_response)
    {
    }

    /** 元の API レスポンスを取得する。 */
    public function getResponse(): Response
    {
        return $this->_response;
    }

    /** HTTP ステータスを取得する。 */
    public function getStatus(): int
    {
        return $this->_response->getStatus();
    }

    /** @return array<string, string[]> 生レスポンスヘッダ */
    public function getRawHeader(): array
    {
        return $this->_response->getRawHeader();
    }
}
