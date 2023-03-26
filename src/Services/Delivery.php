<?php

namespace Shimoning\ColorMeShopApi\Services;

use Shimoning\ColorMeShopApi\Communicator\Request;
use Shimoning\ColorMeShopApi\Communicator\RequestOptions;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Delivery\Delivery as DeliveryEntity;

class Delivery
{
    protected string $_accessToken;

    public function __construct(string $accessToken)
    {
        $this->_accessToken = $accessToken;
    }

    /**
     * 配送方法一覧を取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/delivery/operation/getDeliveries
     * @param string|null $accessToken
     * @return Collection<DeliveryEntity>|Errors
     */
    public function all(?string $accessToken = null): Collection|Errors
    {
        $response = (new Request(new RequestOptions([
            'authorization' => $accessToken ?? $this->_accessToken,
        ])))->get(
            'https://api.shop-pro.jp/v1/deliveries',
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
