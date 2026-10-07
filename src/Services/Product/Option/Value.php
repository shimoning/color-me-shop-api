<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Services\Product\Option;

use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Communicator\NoContent;
use Shimoning\ColorMeShopApi\Entities\Product\Option\Value\Value as ValueEntity;
use Shimoning\ColorMeShopApi\Entities\Product\Option\Value\ValueCreateInput;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use Shimoning\ColorMeShopApi\Services\Service;

/** 商品オプション値 API を操作するサービス。 */
class Value extends Service
{
    /**
     * オプション値を作成する。成功は 201 で、応答の `option_value` を返す。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function create(
        int|string $productId,
        int|string $optionId,
        ValueCreateInput $input,
        ?string $accessToken = null,
    ): ValueEntity|Errors {
        $response = $this->_request(['json' => true], $accessToken)->post(
            $this->_endpoint('/products/' . $productId . '/options/' . $optionId . '/values'),
            ['option_value' => self::_jsonObject($input->toArrayRecursive())],
        );
        return $this->_handle($response, static fn(?array $data): ValueEntity => new ValueEntity($data['option_value'] ?? []));
    }

    /**
     * オプション値を削除する。成功は 204 でボディがないため NoContent を返す。
     * 実測では最後の1件の削除は 422 で、`field` のない Errors になる。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function delete(
        int|string $productId,
        int|string $optionId,
        int|string $id,
        ?string $accessToken = null,
    ): NoContent|Errors {
        $response = $this->_request([], $accessToken)->delete(
            $this->_endpoint('/products/' . $productId . '/options/' . $optionId . '/values/' . $id),
        );
        return $this->_handle($response, static fn(?array $_data): NoContent => new NoContent($response));
    }
}
