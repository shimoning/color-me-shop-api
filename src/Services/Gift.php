<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Services;

use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Gift\Gift as GiftEntity;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;

/**
 * ギフト設定 API を操作するサービス。
 */
class Gift extends Service
{
    /**
     * ギフト設定を取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/gift/operation/getGift
     * @param string|null $accessToken
     * @return GiftEntity|Errors
     * @throws ParameterException 実効アクセストークンが空文字の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function get(?string $accessToken = null): GiftEntity|Errors
    {
        $response = $this->_request([], $accessToken)->get(
            $this->_endpoint('/gift'),
        );

        return $this->_handle(
            $response,
            fn(?array $data): GiftEntity => new GiftEntity($data['gift'] ?? []),
        );
    }
}
