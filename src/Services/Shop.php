<?php

namespace Shimoning\ColorMeShopApi\Services;

use GuzzleHttp\ClientInterface;
use Shimoning\ColorMeShopApi\Communicator\Request;
use Shimoning\ColorMeShopApi\Communicator\RequestOptions;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Shop\Shop as ShopEntity;

class Shop
{
    protected string $_accessToken;
    protected ?ClientInterface $_httpClient;

    /**
     * @param string $accessToken
     * @param ClientInterface|null $httpClient HTTP クライアント (省略時は Guzzle のデフォルト)
     */
    public function __construct(string $accessToken, ?ClientInterface $httpClient = null)
    {
        $this->_accessToken = $accessToken;
        $this->_httpClient = $httpClient;
    }

    /**
     * ショップ情報の取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/shop/operation/getShop
     * @param string|null $accessToken
     * @return ShopEntity|Errors
     */
    public function get(?string $accessToken = null): ShopEntity|Errors
    {
        $response = (new Request(new RequestOptions([
            'authorization' => $accessToken ?? $this->_accessToken,
        ]), $this->_httpClient))->get(
            'https://api.shop-pro.jp/v1/shop',
        );
        if (! $response->isSuccess()) {
            return Errors::build($response);
        }
        $data = $response->getParsedBody();

        return new ShopEntity($data['shop'] ?? []);
    }
}
