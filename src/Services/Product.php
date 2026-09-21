<?php

namespace Shimoning\ColorMeShopApi\Services;

use Psr\Http\Message\StreamInterface;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Communicator\NoContent;
use Shimoning\ColorMeShopApi\Constants\PickupType;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Page;
use Shimoning\ColorMeShopApi\Entities\Product\Advertising;
use Shimoning\ColorMeShopApi\Entities\Product\AdvertisingSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\BigCategory;
use Shimoning\ColorMeShopApi\Entities\Product\Category;
use Shimoning\ColorMeShopApi\Entities\Product\CategoryChildInput;
use Shimoning\ColorMeShopApi\Entities\Product\CategoryInput;
use Shimoning\ColorMeShopApi\Entities\Product\Group;
use Shimoning\ColorMeShopApi\Entities\Product\GroupInput;
use Shimoning\ColorMeShopApi\Entities\Product\Option;
use Shimoning\ColorMeShopApi\Entities\Product\OptionInput;
use Shimoning\ColorMeShopApi\Entities\Product\OptionValue;
use Shimoning\ColorMeShopApi\Entities\Product\OptionValueInput;
use Shimoning\ColorMeShopApi\Entities\Product\Pickup;
use Shimoning\ColorMeShopApi\Entities\Product\PickupInput;
use Shimoning\ColorMeShopApi\Entities\Product\Product as ProductEntity;
use Shimoning\ColorMeShopApi\Entities\Product\ProductImage;
use Shimoning\ColorMeShopApi\Entities\Product\ProductInput;
use Shimoning\ColorMeShopApi\Entities\Product\SearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\SmallCategory;
use Shimoning\ColorMeShopApi\Entities\Product\Variant;
use Shimoning\ColorMeShopApi\Entities\Product\VariantInput;
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

    // --- グループ・カテゴリーの書き込み系 (ADR 0010 / 0014) ----------------

    /**
     * 商品グループを作成する。成功は 201 で、応答の `group` を返す。
     *
     * `display_state` は `GroupDisplayState` (`showing` / `hidden` / `members_only`) で、実 API の観測
     * (2026-09-21) と公式 OpenAPI の request 定義に一致する。
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function createGroup(GroupInput $input, ?string $accessToken = null): Group|Errors
    {
        $response = $this->_request(['json' => true], $accessToken)->post(
            $this->_endpoint('/groups'),
            ['group' => self::_jsonObject($input->toArrayRecursive())],
        );
        return $this->_handle($response, static fn(?array $data): Group => new Group($data['group'] ?? []));
    }

    /**
     * 商品グループを更新する。明示したフィールドだけを送る部分更新で、明示した `null` はクリア要求として送信する。
     *
     * 公式 OpenAPI の更新 request に `parent_group_id` はない (作成専用)。
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function updateGroup(int|string $id, GroupInput $input, ?string $accessToken = null): Group|Errors
    {
        $response = $this->_request(['json' => true], $accessToken)->put(
            $this->_endpoint('/groups/' . $id),
            ['group' => self::_jsonObject($input->toArrayRecursive())],
        );
        return $this->_handle($response, static fn(?array $data): Group => new Group($data['group'] ?? []));
    }

    /**
     * 大カテゴリーを作成する。成功は 201 で、応答の `category` を `BigCategory` として返す。
     *
     * 公式 OpenAPI では `name` が required だが、更新と入力 Entity を共用するため送信前には検証せず、
     * `name` のない要求は API の検証 (422 の `Errors`) に委ねる。
     * @throws ParameterException アクセストークンが空の場合
     * @throws InvalidFieldException 応答の `category` が配列以外、`Category::fromArray()` で変換できない、または `BigCategory` でない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function createCategory(CategoryInput $input, ?string $accessToken = null): BigCategory|Errors
    {
        $response = $this->_request(['json' => true], $accessToken)->post(
            $this->_endpoint('/categories'),
            ['category' => self::_jsonObject($input->toArrayRecursive())],
        );
        return $this->_handle(
            $response,
            static fn(?array $data): BigCategory => self::categoryFromResponse($data, BigCategory::class),
        );
    }

    /**
     * 大カテゴリーを更新する。明示したフィールドだけを送る部分更新で、応答の `category` を `BigCategory` として返す。
     * @throws ParameterException アクセストークンが空の場合
     * @throws InvalidFieldException 応答の `category` が配列以外、`Category::fromArray()` で変換できない、または `BigCategory` でない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function updateCategory(int|string $id, CategoryInput $input, ?string $accessToken = null): BigCategory|Errors
    {
        $response = $this->_request(['json' => true], $accessToken)->put(
            $this->_endpoint('/categories/' . $id),
            ['category' => self::_jsonObject($input->toArrayRecursive())],
        );
        return $this->_handle(
            $response,
            static fn(?array $data): BigCategory => self::categoryFromResponse($data, BigCategory::class),
        );
    }

    /**
     * 小カテゴリーを作成する。成功は 201 で、応答の `category` を `SmallCategory` として返す。
     *
     * `name` の required 指定の扱いは大カテゴリーの作成と同じで、API の検証に委ねる。
     * @throws ParameterException アクセストークンが空の場合
     * @throws InvalidFieldException 応答の `category` が配列以外、`Category::fromArray()` で変換できない、または `SmallCategory` でない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function createCategoryChild(
        int|string $categoryId,
        CategoryChildInput $input,
        ?string $accessToken = null,
    ): SmallCategory|Errors {
        $response = $this->_request(['json' => true], $accessToken)->post(
            $this->_endpoint('/categories/' . $categoryId . '/children'),
            ['category' => self::_jsonObject($input->toArrayRecursive())],
        );
        return $this->_handle(
            $response,
            static fn(?array $data): SmallCategory => self::categoryFromResponse($data, SmallCategory::class),
        );
    }

    /**
     * 小カテゴリーを更新する。明示したフィールドだけを送る部分更新で、応答の `category` を `SmallCategory` として返す。
     * @throws ParameterException アクセストークンが空の場合
     * @throws InvalidFieldException 応答の `category` が配列以外、`Category::fromArray()` で変換できない、または `SmallCategory` でない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function updateCategoryChild(
        int|string $categoryId,
        int|string $id,
        CategoryChildInput $input,
        ?string $accessToken = null,
    ): SmallCategory|Errors {
        $response = $this->_request(['json' => true], $accessToken)->put(
            $this->_endpoint('/categories/' . $categoryId . '/children/' . $id),
            ['category' => self::_jsonObject($input->toArrayRecursive())],
        );
        return $this->_handle(
            $response,
            static fn(?array $data): SmallCategory => self::categoryFromResponse($data, SmallCategory::class),
        );
    }

    /**
     * 書き込み応答の `category` を `Category::fromArray()` で変換し、操作が期待する親子の型であることを確認する。
     *
     * ADR 0010 の厳格方針に従い、`id_small` の欠損・型不正はもちろん、大カテゴリーの操作で
     * `id_small !== 0` の応答が返る (またはその逆) 場合も誤分類を隠さず例外にする。
     * `category` が配列以外 (文字列などのスカラー) の場合も、`categories()` の `categories[n]` と同じく
     * `TypeError` ではなく `InvalidFieldException` に正規化する。
     *
     * @template T of BigCategory|SmallCategory
     * @param class-string<T> $expected
     * @return T
     * @throws InvalidFieldException `category` が配列以外、`Category::fromArray()` で変換できない、または期待した型でない場合
     */
    private static function categoryFromResponse(?array $data, string $expected): BigCategory|SmallCategory
    {
        $raw = $data['category'] ?? [];
        if (! \is_array($raw)) {
            throw InvalidFieldException::for(self::class, 'category', $expected, $raw);
        }

        $category = Category::fromArray($raw);
        if (! $category instanceof $expected) {
            throw InvalidFieldException::for(self::class, 'category', $expected, $category);
        }

        return $category;
    }

    // --- 書き込み系 (ADR 0014) ---------------------------------------------

    /**
     * 商品を作成する。
     *
     * 実測では `name` だけの POST が 200 で、応答の `product` は GET と同じキー集合だった。
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function create(ProductInput $input, ?string $accessToken = null): ProductEntity|Errors
    {
        $response = $this->_request(['json' => true], $accessToken)->post(
            $this->_endpoint('/products'),
            ['product' => self::_jsonObject($input->toArrayRecursive())],
        );
        return $this->_handle($response, static fn(?array $data): ProductEntity => new ProductEntity($data['product'] ?? []));
    }

    /**
     * 商品を更新する。明示したフィールドだけを送る部分更新で、明示した `null` はクリア要求として送信する。
     *
     * 実測では `name` だけの PUT で他フィールドが保持され、`sales_price: null` で値をクリアできた。
     * 空の `ProductInput` は `{"product":{}}` として送信し、API が 422 (`VALIDATE_ERROR_FIELD`、`field=product`)
     * で拒否して `Errors` が返る。ライブラリ側では事前に拒否しない。
     * 実測の出典: docs/api-product-structure.md「書き込み系の観測」。
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function update(int|string $id, ProductInput $input, ?string $accessToken = null): ProductEntity|Errors
    {
        $response = $this->_request(['json' => true], $accessToken)->put(
            $this->_endpoint('/products/' . $id),
            ['product' => self::_jsonObject($input->toArrayRecursive())],
        );
        return $this->_handle($response, static fn(?array $data): ProductEntity => new ProductEntity($data['product'] ?? []));
    }

    /**
     * バリエーションを更新する。応答の `variant` は商品 GET の `variants[]` と同じ形。
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function updateVariant(
        int|string $productId,
        int|string $id,
        VariantInput $input,
        ?string $accessToken = null,
    ): Variant|Errors {
        $response = $this->_request(['json' => true], $accessToken)->put(
            $this->_endpoint('/products/' . $productId . '/variants/' . $id),
            ['variant' => self::_jsonObject($input->toArrayRecursive())],
        );
        return $this->_handle($response, static fn(?array $data): Variant => new Variant($data['variant'] ?? []));
    }

    /**
     * オプションを作成する。成功は 201 で、応答の `option` を返す。
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function createOption(int|string $productId, OptionInput $input, ?string $accessToken = null): Option|Errors
    {
        $response = $this->_request(['json' => true], $accessToken)->post(
            $this->_endpoint('/products/' . $productId . '/options'),
            ['option' => self::_jsonObject($input->toArrayRecursive())],
        );
        return $this->_handle($response, static fn(?array $data): Option => new Option($data['option'] ?? []));
    }

    /**
     * オプションを削除する。成功は 204 でボディがないため NoContent を返す。
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function deleteOption(int|string $productId, int|string $id, ?string $accessToken = null): NoContent|Errors
    {
        $response = $this->_request([], $accessToken)->delete(
            $this->_endpoint('/products/' . $productId . '/options/' . $id),
        );
        return $this->_handle($response, static fn(?array $_data): NoContent => new NoContent($response));
    }

    /**
     * オプション値を作成する。成功は 201 で、応答の `option_value` を返す。
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function createOptionValue(
        int|string $productId,
        int|string $optionId,
        OptionValueInput $input,
        ?string $accessToken = null,
    ): OptionValue|Errors {
        $response = $this->_request(['json' => true], $accessToken)->post(
            $this->_endpoint('/products/' . $productId . '/options/' . $optionId . '/values'),
            ['option_value' => self::_jsonObject($input->toArrayRecursive())],
        );
        return $this->_handle($response, static fn(?array $data): OptionValue => new OptionValue($data['option_value'] ?? []));
    }

    /**
     * オプション値を削除する。成功は 204 でボディがないため NoContent を返す。
     * 実測では最後の1件の削除は 422 で、`field` のない Errors になる。
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function deleteOptionValue(
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

    /**
     * おすすめ商品情報を作成する。入力はトップレベルの `pickup_type` / `order_num` で、応答の `pickup` を返す。
     *
     * 公式 OpenAPI は要求ボディを required とし、API は `pickup_type` で対象を特定するため、
     * 両フィールドが未指定の入力は送信前に拒否する (明示した `null` は送信する)。
     * @throws ParameterException アクセストークンが空、または `pickup_type` / `order_num` が未指定の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function createPickup(int|string $productId, PickupInput $input, ?string $accessToken = null): Pickup|Errors
    {
        $response = $this->_request(['json' => true], $accessToken)->post(
            $this->_endpoint('/products/' . $productId . '/pickups'),
            self::requirePickupFields($input),
        );
        return $this->_handle($response, static fn(?array $data): Pickup => new Pickup($data['pickup'] ?? []));
    }

    /**
     * おすすめ商品情報を更新する。入力はトップレベルの `pickup_type` / `order_num` で、応答の `pickup` を返す。
     *
     * 作成と同じく、`pickup_type` / `order_num` が未指定の入力は送信前に拒否する。
     * @throws ParameterException アクセストークンが空、または `pickup_type` / `order_num` が未指定の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function updatePickup(int|string $productId, PickupInput $input, ?string $accessToken = null): Pickup|Errors
    {
        $response = $this->_request(['json' => true], $accessToken)->put(
            $this->_endpoint('/products/' . $productId . '/pickups'),
            self::requirePickupFields($input),
        );
        return $this->_handle($response, static fn(?array $data): Pickup => new Pickup($data['pickup'] ?? []));
    }

    /**
     * ピックアップ入力の `pickup_type` と `order_num` が明示されていることを送信前に確認する。
     *
     * 公式 OpenAPI の pickups スキーマに両フィールドの required 指定はないが、要求ボディ自体は
     * required で、空や片方だけのボディは意味を持たない。明示した `null` は API へ委ねる。
     *
     * @return array<string, mixed>
     * @throws ParameterException いずれかが未指定の場合
     */
    private static function requirePickupFields(PickupInput $input): array
    {
        $fields = $input->toArrayRecursive();
        $missing = \array_diff(['pickup_type', 'order_num'], \array_keys($fields));
        if ($missing !== []) {
            throw new ParameterException(\sprintf(
                'おすすめ商品情報の入力には pickup_type と order_num を指定してください (未指定: %s)。',
                \implode(', ', $missing),
            ));
        }

        return $fields;
    }

    /**
     * おすすめ商品情報を削除する。
     *
     * 他の DELETE と異なり、実測では 200 で削除済みの `pickup` object を返すため、NoContent ではなく Pickup を返す。
     * int / string の種別は `PickupType` の値 (0 / 1 / 3 / 4) として検証し、それ以外はパスへ載せずに拒否する。
     * @throws ParameterException アクセストークンが空、または種別が `PickupType` の値でない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function deletePickup(
        int|string $productId,
        PickupType|int|string $pickupType,
        ?string $accessToken = null,
    ): Pickup|Errors {
        $type = self::pickupTypeValue($pickupType);
        $response = $this->_request([], $accessToken)->delete(
            $this->_endpoint('/products/' . $productId . '/pickups/' . $type),
        );
        return $this->_handle($response, static fn(?array $data): Pickup => new Pickup($data['pickup'] ?? []));
    }

    /**
     * ピックアップ種別を `PickupType` のバッキング値へ正規化する。
     *
     * 文字列は 10 進整数の表記 (`'3'`) だけを受け付け、`'3.0'` や空文字、パス区切りを含む値は拒否する。
     * @throws ParameterException `PickupType` に定義のない値の場合
     */
    private static function pickupTypeValue(PickupType|int|string $pickupType): int
    {
        if ($pickupType instanceof PickupType) {
            return $pickupType->value;
        }

        $case = null;
        if (\is_int($pickupType)) {
            $case = PickupType::tryFrom($pickupType);
        } else if (\preg_match('/\A(?:0|[1-9][0-9]*)\z/', $pickupType) === 1) {
            $case = PickupType::tryFrom((int) $pickupType);
        }
        if ($case === null) {
            throw new ParameterException(\sprintf(
                'pickup_type は PickupType の値 (%s) で指定してください (指定値: %s)。',
                \implode(' / ', \array_map(static fn(PickupType $type): int => $type->value, PickupType::cases())),
                \var_export($pickupType, true),
            ));
        }

        return $case->value;
    }

    /**
     * 商品画像を作成する。`multipart/form-data` で `image` と `position` (0〜49) を送信する。
     *
     * 実 API 未検証 (プラン制限)。成功の 201 と応答 `product_image` (`position` / `url`) は
     * 公式 OpenAPI 定義に基づく。実測では契約プランの制限により 401 だった。
     *
     * @param string|resource|StreamInterface $image 画像ファイルのパス、または読み取り可能なストリーム
     * @param string|null $filename multipart で送るファイル名。省略時はパスまたはストリーム URI の末尾
     *   (php://memory などでは拡張子のない `memory`) になるため、ストリーム入力では拡張子付きの名前を指定する
     * @throws ParameterException アクセストークンが空、またはファイル/ストリームを読み取れない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function createImage(
        int|string $productId,
        mixed $image,
        int $position,
        ?string $accessToken = null,
        ?string $filename = null,
    ): ProductImage|Errors {
        $response = $this->_request([], $accessToken)->postMultipart(
            $this->_endpoint('/products/' . $productId . '/images'),
            ['position' => $position],
            ['image' => $image],
            [],
            $filename === null ? [] : ['image' => $filename],
        );
        return $this->_handle($response, static fn(?array $data): ProductImage => new ProductImage($data['product_image'] ?? []));
    }

    /**
     * 商品画像を削除する。成功は 204 でボディがないため NoContent を返す。
     *
     * 実 API 未検証 (プラン制限)。公式 OpenAPI 定義に基づく。
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function deleteImage(int|string $productId, int $position, ?string $accessToken = null): NoContent|Errors
    {
        $response = $this->_request([], $accessToken)->delete(
            $this->_endpoint('/products/' . $productId . '/images/' . $position),
        );
        return $this->_handle($response, static fn(?array $_data): NoContent => new NoContent($response));
    }
}
