<?php

namespace Shimoning\ColorMeShopApi\Services;

use GuzzleHttp\ClientInterface;
use Shimoning\ColorMeShopApi\Communicator\Request;
use Shimoning\ColorMeShopApi\Communicator\RequestOptions;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Delivery\Delivery as DeliveryEntity;

/**
 * 配送方法 API を操作するサービス。
 */
class Delivery
{
    protected string $_accessToken;
    protected ?ClientInterface $_httpClient;

    /**
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/delivery
     * @param string $accessToken
     * @param ClientInterface|null $httpClient HTTP クライアント (省略時は Guzzle のデフォルト)
     * @return void
     */
    public function __construct(string $accessToken, ?ClientInterface $httpClient = null)
    {
        $this->_accessToken = $accessToken;
        $this->_httpClient = $httpClient;
    }

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
        $response = (new Request(new RequestOptions([
            'authorization' => $accessToken ?? $this->_accessToken,
        ]), $this->_httpClient))->get(
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
