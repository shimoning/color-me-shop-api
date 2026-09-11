<?php

namespace Shimoning\ColorMeShopApi\Services;

use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Delivery\Delivery as DeliveryEntity;

/**
 * 配送方法 API を操作するサービス。
 */
class Delivery extends Service
{
    /**
     * 配送方法一覧を取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/delivery/operation/getDeliveries
     * @param string|null $accessToken
     * @return Collection<DeliveryEntity>|Errors
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function all(?string $accessToken = null): Collection|Errors
    {
        $response = $this->request([], $accessToken)->get(
            $this->endpoint('/deliveries'),
        );
        if (! $response->isSuccess()) {
            return Errors::build($response);
        }
        $data = $response->getParsedBody();

        return Collection::cast(DeliveryEntity::class, $data['deliveries'] ?? []);
    }

    // TODO: 配送日時設定を取得
    // https://developer.shop-pro.jp/docs/colorme-api#tag/delivery/operation/getDeliveryDateSetting
}
