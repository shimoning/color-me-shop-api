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
use Shimoning\ColorMeShopApi\Entities\Product\Group;
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
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function deletePickup(
        int|string $productId,
        PickupType|int|string $pickupType,
        ?string $accessToken = null,
    ): Pickup|Errors {
        $type = $pickupType instanceof PickupType ? $pickupType->value : $pickupType;
        $response = $this->_request([], $accessToken)->delete(
            $this->_endpoint('/products/' . $productId . '/pickups/' . $type),
        );
        return $this->_handle($response, static fn(?array $data): Pickup => new Pickup($data['pickup'] ?? []));
    }

    /**
     * 商品画像を作成する。`multipart/form-data` で `image` と `position` (0〜49) を送信する。
     *
     * 実 API 未検証 (プラン制限)。成功の 201 と応答 `product_image` (`position` / `url`) は
     * 公式 OpenAPI 定義に基づく。実測では契約プランの制限により 401 だった。
     *
     * @param string|resource|StreamInterface $image 画像ファイルのパス、または読み取り可能なストリーム
     * @throws ParameterException アクセストークンが空、またはファイル/ストリームを読み取れない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function createImage(
        int|string $productId,
        mixed $image,
        int $position,
        ?string $accessToken = null,
    ): ProductImage|Errors {
        $response = $this->_request([], $accessToken)->postMultipart(
            $this->_endpoint('/products/' . $productId . '/images'),
            ['position' => $position],
            ['image' => $image],
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
