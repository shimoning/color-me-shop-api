<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Services\Product;

use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Page;
use Shimoning\ColorMeShopApi\Entities\Product\Advertising\Advertising as AdvertisingEntity;
use Shimoning\ColorMeShopApi\Entities\Product\Advertising\SearchParameters as ProductAdvertisingSearchParameters;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use Shimoning\ColorMeShopApi\Services\Service;

/** 商品広告 API を操作するサービス。 */
class Advertising extends Service
{
    /**
     * 商品広告一覧。
     *
     * 必要な scope: `read_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::READ_PRODUCTS})
     *
     * @return Page<AdvertisingEntity>|Errors
     * @throws ParameterException アクセストークンが空の場合
     * @throws \Shimoning\ColorMeShopApi\Exceptions\InvalidPaginationException meta の型が不正な場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function page(
        ?ProductAdvertisingSearchParameters $parameters = null,
        ?string $accessToken = null,
    ): Page|Errors {
        $response = $this->_request([], $accessToken)->get(
            $this->_endpoint('/product_advertisings'),
            ($parameters ?? new ProductAdvertisingSearchParameters([]))->toArrayRecursive(),
        );
        return $this->_handle($response, static fn(?array $data): Page => Page::build(
            AdvertisingEntity::class, $data, 'product_advertisings', 'meta', 'GET /v1/product_advertisings のレスポンス',
        ));
    }
}
