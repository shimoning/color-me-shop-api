<?php

namespace Shimoning\ColorMeShopApi\Services;

use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Shop\Shop as ShopEntity;

/**
 * ショップ情報 API を操作するサービス。
 */
class Shop extends Service
{
    /**
     * ショップ情報の取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/shop/operation/getShop
     * @param string|null $accessToken
     * @return ShopEntity|Errors
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function get(?string $accessToken = null): ShopEntity|Errors
    {
        $response = $this->_request([], $accessToken)->get(
            $this->_endpoint('/shop'),
        );
        if (! $response->isSuccess()) {
            return Errors::build($response);
        }
        $data = $response->getParsedBody();

        return new ShopEntity($data['shop'] ?? []);
    }
}
