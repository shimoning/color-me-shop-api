<?php

namespace Shimoning\ColorMeShopApi\Services;

use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Product\BigCategory;
use Shimoning\ColorMeShopApi\Entities\Product\Category;
use Shimoning\ColorMeShopApi\Entities\Product\Group;
use Shimoning\ColorMeShopApi\Entities\Product\SmallCategory;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
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
     * @throws ParameterException 実効アクセストークンが空文字の場合
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
     * @throws ParameterException 実効アクセストークンが空文字、または categories が配列以外の場合
     * @throws InvalidFieldException Category::fromArray() で API フィールドが不正、または categories の要素が配列以外の場合
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

                $convert = static function (mixed $category, int|string $index): BigCategory|SmallCategory {
                    if (! \is_array($category)) {
                        throw InvalidFieldException::forArrayElement(
                            self::class,
                            \sprintf('categories[%s]', $index),
                            Category::class,
                            new \TypeError('カテゴリー要素は配列である必要があります。'),
                        );
                    }

                    return Category::fromArray($category);
                };

                $items = [];
                foreach ($categories as $index => $category) {
                    $items[$index] = $convert($category, $index);
                }

                return new Collection($items);
            },
        );
    }
}
