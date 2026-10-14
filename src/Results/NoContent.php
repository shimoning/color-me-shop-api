<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Results;

use Shimoning\ColorMeShopApi\Aliases;
use Shimoning\ColorMeShopApi\Communicator\Response;
use Shimoning\ColorMeShopApi\Results\Concerns\RestoresLegacySerializedResponse;

/**
 * 204 No Content の成功応答を表す値オブジェクト。
 *
 * ボディ由来の Entity ではなく、Errors と同じく元の Response を保持する。
 * status、headers などの応答情報は getResponse() 経由で参照する。
 */
final class NoContent
{
    use RestoresLegacySerializedResponse;

    /**
     * NoContent 移動前の serialize private キーを復元するための後方互換措置。
     * 次のメジャーバージョンで Aliases::MAP とともに削除する。
     */
    private const LEGACY_SERIALIZED_CLASS = 'Shimoning\\ColorMeShopApi\\Communicator\\NoContent';

    public function __construct(private Response $_response)
    {
    }

    /** 元の API レスポンスを取得する。 */
    public function getResponse(): Response
    {
        return $this->_response;
    }
}

// NoContent 移動の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(NoContent::class);
