<?php

namespace Shimoning\ColorMeShopApi\Services;

use Shimoning\ColorMeShopApi\Communicator\Request;
use Shimoning\ColorMeShopApi\Communicator\RequestOptions;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Customer\SearchParameters;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Page;
use Shimoning\ColorMeShopApi\Entities\Pagination;
use Shimoning\ColorMeShopApi\Entities\Customer\Customer as CustomerEntity;

class Customer
{
    protected string $_accessToken;

    public function __construct(string $accessToken)
    {
        $this->_accessToken = $accessToken;
    }

    /**
     * 顧客データのリストを取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/Customer/operation/getCustomers
     * @param SearchParameters $searchParameters
     * @param string|null $accessToken
     * @return Page<CustomerEntity>|Errors
     */
    public function page(
        SearchParameters $searchParameters,
        ?string $accessToken = null,
    ): Page|Errors {
        $response = (new Request(new RequestOptions([
            'authorization' => $accessToken ?? $this->_accessToken,
        ])))->get(
            'https://api.shop-pro.jp/v1/customers',
            $searchParameters->toArrayRecursive(),
        );
        if (! $response->isSuccess()) {
            return Errors::build($response);
        }
        $data = $response->getParsedBody();

        return new Page(
            Collection::cast(CustomerEntity::class, $data['customers'] ?? []),
            new Pagination($data['meta'] ?? []),
        );
    }

    /**
     * 顧客データの取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/customer/operation/getCustomer
     * @param int|string $id
     * @param string|null $accessToken
     * @return CustomerEntity|Errors
     */
    public function one(int|string $id, ?string $accessToken = null): CustomerEntity|Errors
    {
        $response = (new Request(new RequestOptions([
            'authorization' => $accessToken ?? $this->_accessToken,
        ])))->get(
            'https://api.shop-pro.jp/v1/customers/' . $id,
        );
        if (! $response->isSuccess()) {
            return Errors::build($response);
        }
        $data = $response->getParsedBody();
        return new CustomerEntity($data['customer'] ?? []);
    }

    // TODO: create
    // https://developer.shop-pro.jp/docs/colorme-api#tag/customer/operation/postCustomers
}
