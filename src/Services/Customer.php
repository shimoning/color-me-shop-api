<?php

namespace Shimoning\ColorMeShopApi\Services;

use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Customer\SearchParameters;
use Shimoning\ColorMeShopApi\Entities\Page;
use Shimoning\ColorMeShopApi\Entities\Customer\Customer as CustomerEntity;
use Shimoning\ColorMeShopApi\Entities\Customer\CustomerCreateInput;
use Shimoning\ColorMeShopApi\Entities\Customer\CustomerUpdateInput;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;

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
     * 必要な scope は `write_sales` で、顧客専用の scope は存在しない。
     * 公式 OpenAPI が required とする6フィールドは、送信前に明示を確認する (ADR 0015)。
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/customer/operation/postCustomers
     * @param CustomerCreateInput $input
     * @param string|null $accessToken
     * @return CustomerEntity|Errors
     * @throws ParameterException 実効アクセストークンが空文字、または必須フィールドが未指定の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function create(CustomerCreateInput $input, ?string $accessToken = null): CustomerEntity|Errors
    {
        $response = $this->_request(['json' => true], $accessToken)->post(
            $this->_endpoint('/customers'),
            ['customer' => self::requireCreateFields($input)],
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
     * 更新の `customer` には required 指定の子プロパティがないため、空の入力を送信前に拒否せず
     * API の検証 (422 の `Errors`) に委ねる (ADR 0015)。必要な scope は `write_sales`。
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/customer/operation/updateCustomers
     * @param int|string $id
     * @param CustomerUpdateInput $input
     * @param string|null $accessToken
     * @return CustomerEntity|Errors
     * @throws ParameterException 実効アクセストークンが空文字の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function update(
        int|string $id,
        CustomerUpdateInput $input,
        ?string $accessToken = null,
    ): CustomerEntity|Errors {
        $response = $this->_request(['json' => true], $accessToken)->put(
            $this->_endpoint('/customers/' . $id),
            ['customer' => self::_jsonObject($input->toArrayRecursive())],
        );

        return $this->_handle(
            $response,
            static fn(?array $data): CustomerEntity => new CustomerEntity($data['customer'] ?? []),
        );
    }

    /**
     * 顧客作成の入力に、公式 OpenAPI が required とする6フィールドが明示されていることを確認する。
     *
     * 作成の request では `customer` とその6つの子プロパティに required 指定があるため、
     * 空の要求を API へ送らず送信前に拒否する (ADR 0015)。明示した `null` は送信し、
     * その受理は API に委ねる。
     *
     * @return array<string, mixed>
     * @throws ParameterException 未指定の必須フィールドがある場合
     */
    private static function requireCreateFields(CustomerCreateInput $input): array
    {
        $fields = $input->toArrayRecursive();
        $missing = \array_diff(CustomerCreateInput::REQUIRED_FIELDS, \array_keys($fields));
        if ($missing !== []) {
            throw new ParameterException(\sprintf(
                '顧客データの追加には %s を指定してください (未指定: %s)。',
                \implode(', ', CustomerCreateInput::REQUIRED_FIELDS),
                \implode(', ', $missing),
            ));
        }

        return $fields;
    }
}
