<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Services;

use Shimoning\ColorMeShopApi\Results\Errors;
use Shimoning\ColorMeShopApi\Entities\Customer\SearchParameters;
use Shimoning\ColorMeShopApi\Entities\Page;
use Shimoning\ColorMeShopApi\Entities\Customer\Customer as CustomerEntity;
use Shimoning\ColorMeShopApi\Entities\Customer\CustomerCreateInput;
use Shimoning\ColorMeShopApi\Entities\Customer\Points\PointsInput;
use Shimoning\ColorMeShopApi\Entities\Customer\CustomerUpdateInput;
use Shimoning\ColorMeShopApi\Entities\Customer\Points\Points;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;

/**
 * 顧客 API を操作するサービス。
 */
class Customer extends Service
{
    /**
     * 顧客データのリストを取得
     *
     * 必要な scope: `read_sales` ({@see \Shimoning\ColorMeShopApi\Constants\OAuth\AuthScope::READ_SALES})
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/customer/operation/getCustomers
     * @param SearchParameters $searchParameters
     * @param string|null $accessToken
     * @return Page<CustomerEntity>|Errors
     * @throws ParameterException 実効アクセストークンが空文字の場合
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
     * 必要な scope: `read_sales` ({@see \Shimoning\ColorMeShopApi\Constants\OAuth\AuthScope::READ_SALES})
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/customer/operation/getCustomer
     * @param int|string $id
     * @param string|null $accessToken
     * @return CustomerEntity|Errors
     * @throws ParameterException 実効アクセストークンが空文字の場合
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

    // --- 書き込み系 (ADR 0015) --------------------------------------------

    /**
     * 顧客データの追加
     *
     * 必要な scope: `write_sales` ({@see \Shimoning\ColorMeShopApi\Constants\OAuth\AuthScope::WRITE_SALES})
     *
     * 顧客専用の scope は存在しない。必須フィールドは CustomerCreateInput の構築時に検証される。
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/customer/operation/postCustomers
     * @param CustomerCreateInput $input
     * @param string|null $accessToken
     * @return CustomerEntity|Errors
     * @throws ParameterException 実効アクセストークンが空文字の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @see docs/adr/0015-model-customer-write-api.md
     */
    public function create(CustomerCreateInput $input, ?string $accessToken = null): CustomerEntity|Errors
    {
        $response = $this->_request(['json' => true], $accessToken)->post(
            $this->_endpoint('/customers'),
            ['customer' => $input->toArrayRecursive()],
        );

        return $this->_handle(
            $response,
            static fn(?array $data): CustomerEntity => new CustomerEntity($data['customer'] ?? []),
        );
    }

    /**
     * 顧客データの更新
     *
     * 明示したフィールドだけを送る部分更新で、明示した `null` はクリア要求として送信する。
     *
     * 公式 OpenAPI との差分: required 指定はないが、`name` / `address1` を入力の構築時に検証する
     * (2026-09-25)。`address2` は省略すると空になるため必須として扱う (2026-10-09)。消す場合は
     * `null` を明示する。
     *
     * 必要な scope: `write_sales` ({@see \Shimoning\ColorMeShopApi\Constants\OAuth\AuthScope::WRITE_SALES})
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/customer/operation/updateCustomers
     * @param int|string $id
     * @param CustomerUpdateInput $input
     * @param string|null $accessToken
     * @return CustomerEntity|Errors
     * @throws ParameterException 実効アクセストークンが空文字の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @see docs/api-partial-update-observation.md
     * @see docs/api-customer-structure.md
     */
    public function update(
        int|string $id,
        CustomerUpdateInput $input,
        ?string $accessToken = null,
    ): CustomerEntity|Errors {
        $response = $this->_request(['json' => true], $accessToken)->put(
            $this->_endpoint('/customers/' . $id),
            ['customer' => $input->toArrayRecursive()],
        );

        return $this->_handle(
            $response,
            static fn(?array $data): CustomerEntity => new CustomerEntity($data['customer'] ?? []),
        );
    }

    /**
     * 顧客ショップポイントの増減
     *
     * 正の値が加算、負の値が減算を表す。応答は `customer` などで包まれず、`customer_id` と
     * 増減後の `points` をトップレベルに持つ。
     * `points` の未指定は PointsInput の構築時に拒否し、値の範囲は API の検証に委ねる。
     *
     * 必要な scope: `write_sales` ({@see \Shimoning\ColorMeShopApi\Constants\OAuth\AuthScope::WRITE_SALES})
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/customer/operation/postCustomerPoints
     * @param int|string $id
     * @param PointsInput $input
     * @param string|null $accessToken
     * @return Points|Errors
     * @throws ParameterException 実効アクセストークンが空文字の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @see docs/adr/0015-model-customer-write-api.md
     */
    public function changePoints(
        int|string $id,
        PointsInput $input,
        ?string $accessToken = null,
    ): Points|Errors {
        $response = $this->_request(['json' => true], $accessToken)->post(
            $this->_endpoint('/customers/' . $id . '/points'),
            $input->toArrayRecursive(),
        );

        return $this->_handle($response, static fn(?array $data): Points => new Points($data ?? []));
    }

}
