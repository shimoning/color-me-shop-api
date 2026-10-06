<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Services;

use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Page;
use Shimoning\ColorMeShopApi\Entities\Product\Stock\SearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\Stock\Stock as StockEntity;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;

/**
 * 在庫情報 API を操作するサービス。
 */
class Stock extends Service
{
    /**
     * 在庫情報一覧を取得する。
     *
     * @return Page<StockEntity>|Errors
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function page(SearchParameters $parameters, ?string $accessToken = null): Page|Errors
    {
        $response = $this->_request([], $accessToken)->get(
            $this->_endpoint('/stocks'),
            $parameters->toArrayRecursive(),
        );

        return $this->_handle($response, static fn(?array $data): Page => Page::build(
            StockEntity::class,
            $data,
            'stocks',
            'meta',
            'GET /v1/stocks のレスポンス',
        ));
    }
}
