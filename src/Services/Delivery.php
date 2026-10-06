<?php

namespace Shimoning\ColorMeShopApi\Services;

use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Delivery\Delivery as DeliveryEntity;
use Shimoning\ColorMeShopApi\Entities\Delivery\Date\Date;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;

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
     * @throws ParameterException 実効アクセストークンが空文字の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function all(?string $accessToken = null): Collection|Errors
    {
        $response = $this->_request([], $accessToken)->get(
            $this->_endpoint('/deliveries'),
        );

        return $this->_handle(
            $response,
            fn(?array $data): Collection => Collection::cast(DeliveryEntity::class, $data['deliveries'] ?? []),
        );
    }

    /**
     * 配送日時設定を取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/delivery/operation/getDeliveryDateSetting
     * @param string|null $accessToken
     * @return Date|Errors
     * @throws ParameterException 実効アクセストークンが空文字の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function dateSetting(?string $accessToken = null): Date|Errors
    {
        $response = $this->_request([], $accessToken)->get(
            $this->_endpoint('/deliveries/date'),
        );

        return $this->_handle(
            $response,
            fn(?array $data): Date => new Date($data['delivery_date'] ?? []),
        );
    }
}
