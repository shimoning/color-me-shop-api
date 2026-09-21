<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Communicator;

/**
 * 204 No Content の成功応答を表す値オブジェクト。
 *
 * ボディ由来の Entity ではなく、Errors と同じく元の Response を保持する。
 * status、headers などの応答情報は getResponse() 経由で参照する。
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
}
