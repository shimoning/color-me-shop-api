<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Services\Product;

use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Communicator\NoContent;
use Shimoning\ColorMeShopApi\Entities\Product\Option\Option as OptionEntity;
use Shimoning\ColorMeShopApi\Entities\Product\Option\OptionCreateInput;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use Shimoning\ColorMeShopApi\Services\Service;

/** 商品オプション API を操作するサービス。 */
class Option extends Service
{
    /**
     * オプションを作成する。成功は 201 で、応答の `option` を返す。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function create(int|string $productId, OptionCreateInput $input, ?string $accessToken = null): OptionEntity|Errors
    {
        $response = $this->_request(['json' => true], $accessToken)->post(
            $this->_endpoint('/products/' . $productId . '/options'),
            ['option' => self::_jsonObject($input->toArrayRecursive())],
        );
        return $this->_handle($response, static fn(?array $data): OptionEntity => new OptionEntity($data['option'] ?? []));
    }

    /**
     * オプションを削除する。成功は 204 でボディがないため NoContent を返す。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function delete(int|string $productId, int|string $id, ?string $accessToken = null): NoContent|Errors
    {
        $response = $this->_request([], $accessToken)->delete(
            $this->_endpoint('/products/' . $productId . '/options/' . $id),
        );
        return $this->_handle($response, static fn(?array $_data): NoContent => new NoContent($response));
    }
}
