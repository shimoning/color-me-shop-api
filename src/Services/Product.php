<?php

namespace Shimoning\ColorMeShopApi\Services;

use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Product\Group;
use Shimoning\ColorMeShopApi\Entities\Product\Category;

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
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function groups(?string $accessToken = null): Collection|Errors
    {
        $response = $this->request([], $accessToken)->get(
            $this->endpoint('/groups'),
        );
        if (! $response->isSuccess()) {
            return Errors::build($response);
        }
        $data = $response->getParsedBody();

        return Collection::cast(Group::class, $data['groups'] ?? []);
    }

    /**
     * 商品カテゴリー一覧を取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/group/operation/getProductCategories
     * @param string|null $accessToken
     * @return Collection<Category>|Errors
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function categories(?string $accessToken = null): Collection|Errors
    {
        $response = $this->request([], $accessToken)->get(
            $this->endpoint('/categories'),
        );
        if (! $response->isSuccess()) {
            return Errors::build($response);
        }
        $data = $response->getParsedBody();

        return Collection::cast(Category::class, $data['categories'] ?? []);
    }
}
