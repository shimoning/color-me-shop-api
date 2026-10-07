<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Services;

use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Page;
use Shimoning\ColorMeShopApi\Entities\Product\Stock\SearchParameters;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;

/**
 * 在庫情報 API を操作するサービス。
 *
 * @deprecated 0.25.0 Services\Product::stockPage() を使うこと。
 * @see docs/adr/0032-merge-stock-service-into-product-service.md
 */
class Stock extends Service
{
    /**
     * 在庫情報一覧を取得する。
     *
     * @return Page<\Shimoning\ColorMeShopApi\Entities\Product\Stock\Stock>|Errors
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function page(SearchParameters $parameters, ?string $accessToken = null): Page|Errors
    {
        return (new Product($this->_accessToken, $this->_httpClient))->stockPage($parameters, $accessToken);
    }
}
