<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Services\Product;

use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Page;
use Shimoning\ColorMeShopApi\Entities\Product\Variant\SearchParameters as VariantSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\Variant\Variant as VariantEntity;
use Shimoning\ColorMeShopApi\Entities\Product\Variant\VariantUpdateInput;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use Shimoning\ColorMeShopApi\Services\Service;

/** 商品バリエーション API を操作するサービス。 */
class Variant extends Service
{
    /**
     * バリエーション一覧。検索条件では model_number / fields / limit / offset を指定できる。
     *
     * 公式 OpenAPI との差分: 未記載の既定 limit は 10 (2026-09-18)。
     *
     * 必要な scope: `read_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::READ_PRODUCTS})
     *
     * @return Page<VariantEntity>|Errors
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @see docs/api-product-structure.md
     */
    public function page(
        int|string $productId,
        ?VariantSearchParameters $parameters = null,
        ?string $accessToken = null,
    ): Page|Errors {
        $response = $this->_request([], $accessToken)->get(
            $this->_endpoint('/products/' . $productId . '/variants'),
            ($parameters ?? new VariantSearchParameters([]))->toArrayRecursive(),
        );
        return $this->_handle($response, static fn(?array $data): Page => Page::build(
            VariantEntity::class, $data, 'variants', 'meta', 'GET /v1/products/{id}/variants のレスポンス',
        ));
    }

    /**
     * バリエーション単体。
     *
     * 必要な scope: `read_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::READ_PRODUCTS})
     *
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function one(int|string $productId, int|string $id, ?string $accessToken = null): VariantEntity|Errors
    {
        $response = $this->_request([], $accessToken)->get(
            $this->_endpoint('/products/' . $productId . '/variants/' . $id),
        );
        return $this->_handle($response, static fn(?array $data): VariantEntity => new VariantEntity($data['variant'] ?? []));
    }

    /**
     * バリエーションを更新する。応答の `variant` は商品 GET の `variants[]` と同じ形。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function update(
        int|string $productId,
        int|string $id,
        VariantUpdateInput $input,
        ?string $accessToken = null,
    ): VariantEntity|Errors {
        $response = $this->_request(['json' => true], $accessToken)->put(
            $this->_endpoint('/products/' . $productId . '/variants/' . $id),
            ['variant' => self::_jsonObject($input->toArrayRecursive())],
        );
        return $this->_handle($response, static fn(?array $data): VariantEntity => new VariantEntity($data['variant'] ?? []));
    }
}
