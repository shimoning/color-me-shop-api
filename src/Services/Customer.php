<?php

namespace Shimoning\ColorMeShopApi\Services;

use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Customer\SearchParameters;
use Shimoning\ColorMeShopApi\Entities\Page;
use Shimoning\ColorMeShopApi\Entities\Customer\Customer as CustomerEntity;

/**
 * 顧客 API を操作するサービス。
 */
class Customer extends Service
{
    /**
     * 顧客データのリストを取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/customer/operation/getCustomers
     * @param SearchParameters $searchParameters
     * @param string|null $accessToken
     * @return Page<CustomerEntity>|Errors
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function page(
        SearchParameters $searchParameters,
        ?string $accessToken = null,
    ): Page|Errors {
        $response = $this->_request([], $accessToken)->get(
            $this->_endpoint('/customers'),
            $searchParameters->toArrayRecursive(),
        );

        return $this->_handle(
            $response,
            fn(?array $data): Page => Page::build(
                CustomerEntity::class,
                $data,
                'customers',
                'meta',
                'GET /v1/customers のレスポンス',
            ),
        );
    }

    /**
     * 顧客データの取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/customer/operation/getCustomer
     * @param int|string $id
     * @param string|null $accessToken
     * @return CustomerEntity|Errors
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function one(int|string $id, ?string $accessToken = null): CustomerEntity|Errors
    {
        $response = $this->_request([], $accessToken)->get(
            $this->_endpoint('/customers/' . $id),
        );

        return $this->_handle(
            $response,
            fn(?array $data): CustomerEntity => new CustomerEntity($data['customer'] ?? []),
        );
    }

    // TODO: create
    // https://developer.shop-pro.jp/docs/colorme-api#tag/customer/operation/postCustomers
}
