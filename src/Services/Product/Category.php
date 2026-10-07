<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Services\Product;

use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Product\Category\BigCategory;
use Shimoning\ColorMeShopApi\Entities\Product\Category\Category as CategoryEntity;
use Shimoning\ColorMeShopApi\Entities\Product\Category\CategoryInput;
use Shimoning\ColorMeShopApi\Entities\Product\Category\ChildInput;
use Shimoning\ColorMeShopApi\Entities\Product\Category\SmallCategory;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use Shimoning\ColorMeShopApi\Services\Service;

/** 商品カテゴリー API を操作するサービス。 */
class Category extends Service
{
    /**
     * 商品カテゴリー一覧を取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/group/operation/getProductCategories
     * @param string|null $accessToken
     * @return Collection<BigCategory|SmallCategory>|Errors
     * @throws ParameterException 実効アクセストークンが空文字、または categories が配列以外の場合
     * @throws InvalidFieldException CategoryEntity::fromArray() で API フィールドが不正、または categories の要素が配列以外の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function all(?string $accessToken = null): Collection|Errors
    {
        $response = $this->_request([], $accessToken)->get($this->_endpoint('/categories'));
        return $this->_handle($response, static function (?array $data): Collection {
            $categories = $data['categories'] ?? [];
            if (! \is_array($categories)) {
                throw new ParameterException();
            }

            $items = [];
            foreach ($categories as $index => $category) {
                if (! \is_array($category)) {
                    throw InvalidFieldException::forArrayElement(
                        self::class,
                        \sprintf('categories[%s]', $index),
                        CategoryEntity::class,
                        new \TypeError('カテゴリー要素は配列である必要があります。'),
                    );
                }
                $items[$index] = CategoryEntity::fromArray($category);
            }
            return new Collection($items);
        });
    }

    /**
     * 大カテゴリーを作成する。成功は 201 で、応答の `category` を `BigCategory` として返す。
     *
     * 公式 OpenAPI との差分: `name` は required だがライブラリでは送信前に検証せず、API の検証に委ねる。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws ParameterException アクセストークンが空の場合
     * @throws InvalidFieldException 応答の `category` が配列以外、`CategoryEntity::fromArray()` で変換できない、または `BigCategory` でない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function create(CategoryInput $input, ?string $accessToken = null): BigCategory|Errors
    {
        $response = $this->_request(['json' => true], $accessToken)->post(
            $this->_endpoint('/categories'),
            ['category' => self::_jsonObject($input->toArrayRecursive())],
        );
        return $this->_handle($response, static fn(?array $data): BigCategory => self::categoryFromResponse($data, BigCategory::class));
    }

    /**
     * 大カテゴリーを更新する。明示したフィールドだけを送る部分更新で、応答の `category` を `BigCategory` として返す。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws ParameterException アクセストークンが空の場合
     * @throws InvalidFieldException 応答の `category` が配列以外、`CategoryEntity::fromArray()` で変換できない、または `BigCategory` でない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function update(int|string $id, CategoryInput $input, ?string $accessToken = null): BigCategory|Errors
    {
        $response = $this->_request(['json' => true], $accessToken)->put(
            $this->_endpoint('/categories/' . $id),
            ['category' => self::_jsonObject($input->toArrayRecursive())],
        );
        return $this->_handle($response, static fn(?array $data): BigCategory => self::categoryFromResponse($data, BigCategory::class));
    }

    /**
     * 小カテゴリーを作成する。成功は 201 で、応答の `category` を `SmallCategory` として返す。
     *
     * `name` の required 指定の扱いは大カテゴリーの作成と同じで、API の検証に委ねる。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws ParameterException アクセストークンが空の場合
     * @throws InvalidFieldException 応答の `category` が配列以外、`CategoryEntity::fromArray()` で変換できない、または `SmallCategory` でない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function createChild(
        int|string $categoryId,
        ChildInput $input,
        ?string $accessToken = null,
    ): SmallCategory|Errors {
        $response = $this->_request(['json' => true], $accessToken)->post(
            $this->_endpoint('/categories/' . $categoryId . '/children'),
            ['category' => self::_jsonObject($input->toArrayRecursive())],
        );
        return $this->_handle($response, static fn(?array $data): SmallCategory => self::categoryFromResponse($data, SmallCategory::class));
    }

    /**
     * 小カテゴリーを更新する。明示したフィールドだけを送る部分更新で、応答の `category` を `SmallCategory` として返す。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws ParameterException アクセストークンが空の場合
     * @throws InvalidFieldException 応答の `category` が配列以外、`CategoryEntity::fromArray()` で変換できない、または `SmallCategory` でない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function updateChild(
        int|string $categoryId,
        int|string $id,
        ChildInput $input,
        ?string $accessToken = null,
    ): SmallCategory|Errors {
        $response = $this->_request(['json' => true], $accessToken)->put(
            $this->_endpoint('/categories/' . $categoryId . '/children/' . $id),
            ['category' => self::_jsonObject($input->toArrayRecursive())],
        );
        return $this->_handle($response, static fn(?array $data): SmallCategory => self::categoryFromResponse($data, SmallCategory::class));
    }

    /**
     * 書き込み応答の `category` を `CategoryEntity::fromArray()` で変換し、操作が期待する親子の型であることを確認する。
     *
     * @template T of BigCategory|SmallCategory
     * @param class-string<T> $expected
     * @return T
     * @throws InvalidFieldException `category` が配列以外、`CategoryEntity::fromArray()` で変換できない、または期待した型でない場合
     * @see docs/adr/0010-split-category-into-big-and-small.md
     */
    private static function categoryFromResponse(?array $data, string $expected): BigCategory|SmallCategory
    {
        $raw = $data['category'] ?? [];
        if (! \is_array($raw)) {
            throw InvalidFieldException::for(self::class, 'category', $expected, $raw);
        }
        $category = CategoryEntity::fromArray($raw);
        if (! $category instanceof $expected) {
            throw InvalidFieldException::for(self::class, 'category', $expected, $category);
        }
        return $category;
    }
}
