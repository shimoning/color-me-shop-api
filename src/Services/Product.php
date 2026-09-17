<?php

namespace Shimoning\ColorMeShopApi\Services;

use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Product\BigCategory;
use Shimoning\ColorMeShopApi\Entities\Product\Category;
use Shimoning\ColorMeShopApi\Entities\Product\Group;
use Shimoning\ColorMeShopApi\Entities\Product\SmallCategory;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;

/**
 * 商品関連 API を操作するサービス。
 */
class Product extends Service
{
    /**
     * 商品グループ一覧を取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/group/operation/getProductGroups
     * @param string|null $accessToken
     * @return Collection<Group>|Errors
     * @throws ParameterException 実効アクセストークンが空文字、または categories が配列以外の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function groups(?string $accessToken = null): Collection|Errors
    {
        $response = $this->_request([], $accessToken)->get(
            $this->_endpoint('/groups'),
        );

        return $this->_handle(
            $response,
            fn(?array $data): Collection => Collection::cast(Group::class, $data['groups'] ?? []),
        );
    }

    /**
     * 商品カテゴリー一覧を取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/group/operation/getProductCategories
     * @param string|null $accessToken
     * @return Collection<BigCategory|SmallCategory>|Errors
     * @throws ParameterException 実効アクセストークンが空文字の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function categories(?string $accessToken = null): Collection|Errors
    {
        $response = $this->_request([], $accessToken)->get(
            $this->_endpoint('/categories'),
        );

        return $this->_handle(
            $response,
            static function (?array $data): Collection {
                $categories = $data['categories'] ?? [];
                if (! \is_array($categories)) {
                    throw new ParameterException();
                }

                return new Collection(\array_map(
                    static fn(array $category): BigCategory|SmallCategory => Category::fromArray($category),
                    $categories,
                ));
            },
        );
    }
}
