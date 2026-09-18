<?php

namespace Shimoning\ColorMeShopApi\Services;

use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Page;
use Shimoning\ColorMeShopApi\Entities\Product\Advertising;
use Shimoning\ColorMeShopApi\Entities\Product\AdvertisingSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\BigCategory;
use Shimoning\ColorMeShopApi\Entities\Product\Category;
use Shimoning\ColorMeShopApi\Entities\Product\Group;
use Shimoning\ColorMeShopApi\Entities\Product\Product as ProductEntity;
use Shimoning\ColorMeShopApi\Entities\Product\ProductImage;
use Shimoning\ColorMeShopApi\Entities\Product\SearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\SmallCategory;
use Shimoning\ColorMeShopApi\Entities\Product\Variant;
use Shimoning\ColorMeShopApi\Entities\Product\VariantSearchParameters;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;

/**
 * 商品関連 API を操作するサービス。
 */
class Product extends Service
{
    /**
     * 商品一覧。
     * @return Page<ProductEntity>|Errors
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function products(SearchParameters $parameters, ?string $accessToken = null): Page|Errors
    {
        $response = $this->_request([], $accessToken)->get(
            $this->_endpoint('/products'),
            $parameters->toArrayRecursive(),
        );

        return $this->_handle($response, static fn(?array $data): Page => Page::build(
            ProductEntity::class, $data, 'products', 'meta', 'GET /v1/products のレスポンス',
        ));
    }

    /**
     * 商品単体。
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function product(int|string $id, ?string $accessToken = null): ProductEntity|Errors
    {
        $response = $this->_request([], $accessToken)->get($this->_endpoint('/products/' . $id));
        return $this->_handle($response, static fn(?array $data): ProductEntity => new ProductEntity($data['product'] ?? []));
    }

    /**
     * バリエーション一覧。実測の既定 limit は 10。
     * 検索条件では model_number / fields / limit / offset を指定できる。
     * @return Page<Variant>|Errors
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function variants(
        int|string $productId,
        ?VariantSearchParameters $parameters = null,
        ?string $accessToken = null,
    ): Page|Errors {
        $response = $this->_request([], $accessToken)->get(
            $this->_endpoint('/products/' . $productId . '/variants'),
            ($parameters ?? new VariantSearchParameters([]))->toArrayRecursive(),
        );
        return $this->_handle($response, static fn(?array $data): Page => Page::build(
            Variant::class, $data, 'variants', 'meta', 'GET /v1/products/{id}/variants のレスポンス',
        ));
    }

    /**
     * バリエーション単体。
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function variant(int|string $productId, int|string $id, ?string $accessToken = null): Variant|Errors
    {
        $response = $this->_request([], $accessToken)->get(
            $this->_endpoint('/products/' . $productId . '/variants/' . $id),
        );
        return $this->_handle($response, static fn(?array $data): Variant => new Variant($data['variant'] ?? []));
    }

    /**
     * 商品画像専用 GET。商品本体の追加画像と別構造。
     * @return Collection<ProductImage>|Errors
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function images(int|string $productId, ?string $accessToken = null): Collection|Errors
    {
        $response = $this->_request([], $accessToken)->get(
            $this->_endpoint('/products/' . $productId . '/images'),
        );
        return $this->_handle($response, static fn(?array $data): Collection => Collection::cast(
            ProductImage::class, $data['product']['images'] ?? [],
        ));
    }

    /**
     * 商品広告一覧。
     * @return Page<Advertising>|Errors
     * @throws ParameterException アクセストークンが空の場合
     * @throws \Shimoning\ColorMeShopApi\Exceptions\InvalidPaginationException meta の型が不正な場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function advertisings(
        ?AdvertisingSearchParameters $parameters = null,
        ?string $accessToken = null,
    ): Page|Errors
    {
        $response = $this->_request([], $accessToken)->get(
            $this->_endpoint('/product_advertisings'),
            ($parameters ?? new AdvertisingSearchParameters([]))->toArrayRecursive(),
        );
        return $this->_handle($response, static fn(?array $data): Page => Page::build(
            Advertising::class, $data, 'product_advertisings', 'meta', 'GET /v1/product_advertisings のレスポンス',
        ));
    }

    /**
     * 商品グループ単体。
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function group(int|string $id, ?string $accessToken = null): Group|Errors
    {
        $response = $this->_request([], $accessToken)->get($this->_endpoint('/groups/' . $id));
        return $this->_handle($response, static fn(?array $data): Group => new Group($data['group'] ?? []));
    }

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
