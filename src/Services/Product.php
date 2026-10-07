<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Services;

use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Communicator\NoContent;
use Shimoning\ColorMeShopApi\Constants\PickupType;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Page;
use Shimoning\ColorMeShopApi\Entities\Product\Advertising\SearchParameters as AdvertisingSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\Category\BigCategory;
use Shimoning\ColorMeShopApi\Entities\Product\Category\CategoryInput;
use Shimoning\ColorMeShopApi\Entities\Product\Category\ChildInput;
use Shimoning\ColorMeShopApi\Entities\Product\Category\SmallCategory;
use Shimoning\ColorMeShopApi\Entities\Product\Group\Group;
use Shimoning\ColorMeShopApi\Entities\Product\Group\GroupInput;
use Shimoning\ColorMeShopApi\Entities\Product\Image\Image as ImageEntity;
use Shimoning\ColorMeShopApi\Entities\Product\Option\Option;
use Shimoning\ColorMeShopApi\Entities\Product\Option\OptionCreateInput;
use Shimoning\ColorMeShopApi\Entities\Product\Option\Value\Value;
use Shimoning\ColorMeShopApi\Entities\Product\Option\Value\ValueCreateInput;
use Shimoning\ColorMeShopApi\Entities\Product\Pickup\Pickup;
use Shimoning\ColorMeShopApi\Entities\Product\Pickup\PickupInput;
use Shimoning\ColorMeShopApi\Entities\Product\Product as ProductEntity;
use Shimoning\ColorMeShopApi\Entities\Product\ProductCreateInput;
use Shimoning\ColorMeShopApi\Entities\Product\ProductUpdateInput;
use Shimoning\ColorMeShopApi\Entities\Product\SearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\Stock\SearchParameters as StockSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\Variant\SearchParameters as VariantSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\Variant\Variant;
use Shimoning\ColorMeShopApi\Entities\Product\Variant\VariantUpdateInput;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use Shimoning\ColorMeShopApi\Services\Product\Advertising as AdvertisingService;
use Shimoning\ColorMeShopApi\Services\Product\Category as CategoryService;
use Shimoning\ColorMeShopApi\Services\Product\Group as GroupService;
use Shimoning\ColorMeShopApi\Services\Product\Image as ImageService;
use Shimoning\ColorMeShopApi\Services\Product\Option as OptionService;
use Shimoning\ColorMeShopApi\Services\Product\Option\Value as ValueService;
use Shimoning\ColorMeShopApi\Services\Product\Pickup as PickupService;
use Shimoning\ColorMeShopApi\Services\Product\Stock as StockService;
use Shimoning\ColorMeShopApi\Services\Product\Variant as VariantService;

/** 商品関連 API を操作するサービス。 */
class Product extends Service
{
    /**
     * 商品一覧。
     *
     * 必要な scope: `read_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::READ_PRODUCTS})
     *
     * @return Page<ProductEntity>|Errors
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function page(SearchParameters $parameters, ?string $accessToken = null): Page|Errors
    {
        $response = $this->_request([], $accessToken)->get($this->_endpoint('/products'), $parameters->toArrayRecursive());
        return $this->_handle($response, static fn(?array $data): Page => Page::build(
            ProductEntity::class, $data, 'products', 'meta', 'GET /v1/products のレスポンス',
        ));
    }

    /**
     * @deprecated 0.25.0 page() を使うこと。
     * @see docs/adr/0033-unify-client-and-service-method-names.md
     */
    public function products(SearchParameters $parameters, ?string $accessToken = null): Page|Errors
    {
        return $this->page($parameters, $accessToken);
    }

    /**
     * 商品単体。
     *
     * 必要な scope: `read_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::READ_PRODUCTS})
     *
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function one(int|string $id, ?string $accessToken = null): ProductEntity|Errors
    {
        $response = $this->_request([], $accessToken)->get($this->_endpoint('/products/' . $id));
        return $this->_handle($response, static fn(?array $data): ProductEntity => new ProductEntity($data['product'] ?? []));
    }

    /**
     * @deprecated 0.25.0 one() を使うこと。
     * @see docs/adr/0033-unify-client-and-service-method-names.md
     */
    public function product(int|string $id, ?string $accessToken = null): ProductEntity|Errors
    {
        return $this->one($id, $accessToken);
    }

    /**
     * 商品を作成する。更新専用フィールドは送信されない。
     *
     * `name` だけで作成できる (2026-09-20)。
     *
     * 公式 OpenAPI との差分: 作成の request にない更新専用の 4 項目を送っても、実 API はエラーにせず無視する
     * (2026-10-02)。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @see docs/api-product-structure.md
     */
    public function create(ProductCreateInput $input, ?string $accessToken = null): ProductEntity|Errors
    {
        $response = $this->_request(['json' => true], $accessToken)->post(
            $this->_endpoint('/products'), ['product' => self::_jsonObject($input->toArrayRecursive())],
        );
        return $this->_handle($response, static fn(?array $data): ProductEntity => new ProductEntity($data['product'] ?? []));
    }

    /**
     * 商品を更新する。明示したフィールドだけを送る部分更新で、明示した `null` はクリア要求として送信する。
     * `sales_price` と `price` は `null` でクリアできることを確認している (2026-09-20、2026-09-21)。
     *
     * 空の `ProductUpdateInput` は `{"product":{}}` として送信し、API が 422 (`VALIDATE_ERROR_FIELD`、`field=product`)
     * で拒否して `Errors` が返る。ライブラリ側では事前に拒否しない。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @see docs/api-product-structure.md
     */
    public function update(int|string $id, ProductUpdateInput $input, ?string $accessToken = null): ProductEntity|Errors
    {
        $response = $this->_request(['json' => true], $accessToken)->put(
            $this->_endpoint('/products/' . $id), ['product' => self::_jsonObject($input->toArrayRecursive())],
        );
        return $this->_handle($response, static fn(?array $data): ProductEntity => new ProductEntity($data['product'] ?? []));
    }

    /**
     * 在庫情報一覧を取得する。
     * @return Page<\Shimoning\ColorMeShopApi\Entities\Product\Stock\Stock>|Errors
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @deprecated 0.26.0 Services\Product\Stock::page() を使うこと。
     * @see docs/adr/0034-split-product-service.md
     */
    public function stockPage(StockSearchParameters $parameters, ?string $accessToken = null): Page|Errors
    {
        return $this->stockService()->page($parameters, $accessToken);
    }

    /**
     * バリエーション一覧。検索条件では model_number / fields / limit / offset を指定できる。
     * @return Page<Variant>|Errors
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @deprecated 0.26.0 Services\Product\Variant::page() を使うこと。
     * @see docs/adr/0034-split-product-service.md
     */
    public function variantPage(int|string $productId, ?VariantSearchParameters $parameters = null, ?string $accessToken = null): Page|Errors
    {
        return $this->variantService()->page($productId, $parameters, $accessToken);
    }

    /**
     * @deprecated 0.25.0 variantPage() を使うこと。
     * @see docs/adr/0033-unify-client-and-service-method-names.md
     */
    public function variants(int|string $productId, ?VariantSearchParameters $parameters = null, ?string $accessToken = null): Page|Errors
    {
        return $this->variantService()->page($productId, $parameters, $accessToken);
    }

    /**
     * バリエーション単体。
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @deprecated 0.26.0 Services\Product\Variant::one() を使うこと。
     * @see docs/adr/0034-split-product-service.md
     */
    public function variantOne(int|string $productId, int|string $id, ?string $accessToken = null): Variant|Errors
    {
        return $this->variantService()->one($productId, $id, $accessToken);
    }

    /**
     * @deprecated 0.25.0 variantOne() を使うこと。
     * @see docs/adr/0033-unify-client-and-service-method-names.md
     */
    public function variant(int|string $productId, int|string $id, ?string $accessToken = null): Variant|Errors
    {
        return $this->variantService()->one($productId, $id, $accessToken);
    }

    /**
     * 商品画像専用 GET。商品本体の追加画像と別構造。
     * @return Collection<ImageEntity>|Errors
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @deprecated 0.26.0 Services\Product\Image::all() を使うこと。
     * @see docs/adr/0034-split-product-service.md
     */
    public function imageAll(int|string $productId, ?string $accessToken = null): Collection|Errors
    {
        return $this->imageService()->all($productId, $accessToken);
    }

    /**
     * @deprecated 0.25.0 imageAll() を使うこと。
     * @see docs/adr/0033-unify-client-and-service-method-names.md
     */
    public function images(int|string $productId, ?string $accessToken = null): Collection|Errors
    {
        return $this->imageService()->all($productId, $accessToken);
    }

    /**
     * 商品広告一覧。
     * @return Page<\Shimoning\ColorMeShopApi\Entities\Product\Advertising\Advertising>|Errors
     * @throws ParameterException アクセストークンが空の場合
     * @throws \Shimoning\ColorMeShopApi\Exceptions\InvalidPaginationException meta の型が不正な場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @deprecated 0.26.0 Services\Product\Advertising::page() を使うこと。
     * @see docs/adr/0034-split-product-service.md
     */
    public function advertisingPage(?AdvertisingSearchParameters $parameters = null, ?string $accessToken = null): Page|Errors
    {
        return $this->advertisingService()->page($parameters, $accessToken);
    }

    /**
     * @deprecated 0.25.0 advertisingPage() を使うこと。
     * @see docs/adr/0033-unify-client-and-service-method-names.md
     */
    public function advertisings(?AdvertisingSearchParameters $parameters = null, ?string $accessToken = null): Page|Errors
    {
        return $this->advertisingService()->page($parameters, $accessToken);
    }

    /**
     * 商品グループ単体。
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @deprecated 0.26.0 Services\Product\Group::one() を使うこと。
     * @see docs/adr/0034-split-product-service.md
     */
    public function groupOne(int|string $id, ?string $accessToken = null): Group|Errors
    {
        return $this->groupService()->one($id, $accessToken);
    }

    /**
     * @deprecated 0.25.0 groupOne() を使うこと。
     * @see docs/adr/0033-unify-client-and-service-method-names.md
     */
    public function group(int|string $id, ?string $accessToken = null): Group|Errors
    {
        return $this->groupService()->one($id, $accessToken);
    }

    /**
     * 商品グループ一覧を取得
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/group/operation/getProductGroups
     * @return Collection<Group>|Errors
     * @throws ParameterException 実効アクセストークンが空文字の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @deprecated 0.26.0 Services\Product\Group::all() を使うこと。
     * @see docs/adr/0034-split-product-service.md
     */
    public function groupAll(?string $accessToken = null): Collection|Errors
    {
        return $this->groupService()->all($accessToken);
    }

    /**
     * @deprecated 0.25.0 groupAll() を使うこと。
     * @see docs/adr/0033-unify-client-and-service-method-names.md
     */
    public function groups(?string $accessToken = null): Collection|Errors
    {
        return $this->groupService()->all($accessToken);
    }

    /**
     * 商品カテゴリー一覧を取得
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/group/operation/getProductCategories
     * @return Collection<BigCategory|SmallCategory>|Errors
     * @throws ParameterException 実効アクセストークンが空文字、または categories が配列以外の場合
     * @throws \Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException API フィールドが不正の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @deprecated 0.26.0 Services\Product\Category::all() を使うこと。
     * @see docs/adr/0034-split-product-service.md
     */
    public function categoryAll(?string $accessToken = null): Collection|Errors
    {
        return $this->categoryService()->all($accessToken);
    }

    /**
     * @deprecated 0.25.0 categoryAll() を使うこと。
     * @see docs/adr/0033-unify-client-and-service-method-names.md
     */
    public function categories(?string $accessToken = null): Collection|Errors
    {
        return $this->categoryService()->all($accessToken);
    }

    /**
     * 商品グループを作成する。
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @deprecated 0.26.0 Services\Product\Group::create() を使うこと。
     * @see docs/adr/0034-split-product-service.md
     */
    public function createGroup(GroupInput $input, ?string $accessToken = null): Group|Errors
    {
        return $this->groupService()->create($input, $accessToken);
    }

    /**
     * 商品グループを更新する。
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @deprecated 0.26.0 Services\Product\Group::update() を使うこと。
     * @see docs/adr/0034-split-product-service.md
     */
    public function updateGroup(int|string $id, GroupInput $input, ?string $accessToken = null): Group|Errors
    {
        return $this->groupService()->update($id, $input, $accessToken);
    }

    /**
     * 大カテゴリーを作成する。
     * @throws ParameterException アクセストークンが空の場合
     * @throws \Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException 応答のカテゴリーが不正な場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @deprecated 0.26.0 Services\Product\Category::create() を使うこと。
     * @see docs/adr/0034-split-product-service.md
     */
    public function createCategory(CategoryInput $input, ?string $accessToken = null): BigCategory|Errors
    {
        return $this->categoryService()->create($input, $accessToken);
    }

    /**
     * 大カテゴリーを更新する。
     * @throws ParameterException アクセストークンが空の場合
     * @throws \Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException 応答のカテゴリーが不正な場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @deprecated 0.26.0 Services\Product\Category::update() を使うこと。
     * @see docs/adr/0034-split-product-service.md
     */
    public function updateCategory(int|string $id, CategoryInput $input, ?string $accessToken = null): BigCategory|Errors
    {
        return $this->categoryService()->update($id, $input, $accessToken);
    }

    /**
     * 小カテゴリーを作成する。
     * @throws ParameterException アクセストークンが空の場合
     * @throws \Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException 応答のカテゴリーが不正な場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @deprecated 0.26.0 Services\Product\Category::createChild() を使うこと。
     * @see docs/adr/0034-split-product-service.md
     */
    public function createCategoryChild(int|string $categoryId, ChildInput $input, ?string $accessToken = null): SmallCategory|Errors
    {
        return $this->categoryService()->createChild($categoryId, $input, $accessToken);
    }

    /**
     * 小カテゴリーを更新する。
     * @throws ParameterException アクセストークンが空の場合
     * @throws \Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException 応答のカテゴリーが不正な場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @deprecated 0.26.0 Services\Product\Category::updateChild() を使うこと。
     * @see docs/adr/0034-split-product-service.md
     */
    public function updateCategoryChild(int|string $categoryId, int|string $id, ChildInput $input, ?string $accessToken = null): SmallCategory|Errors
    {
        return $this->categoryService()->updateChild($categoryId, $id, $input, $accessToken);
    }

    /**
     * バリエーションを更新する。
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @deprecated 0.26.0 Services\Product\Variant::update() を使うこと。
     * @see docs/adr/0034-split-product-service.md
     */
    public function updateVariant(int|string $productId, int|string $id, VariantUpdateInput $input, ?string $accessToken = null): Variant|Errors
    {
        return $this->variantService()->update($productId, $id, $input, $accessToken);
    }

    /**
     * オプションを作成する。
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @deprecated 0.26.0 Services\Product\Option::create() を使うこと。
     * @see docs/adr/0034-split-product-service.md
     */
    public function createOption(int|string $productId, OptionCreateInput $input, ?string $accessToken = null): Option|Errors
    {
        return $this->optionService()->create($productId, $input, $accessToken);
    }

    /**
     * オプションを削除する。
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @deprecated 0.26.0 Services\Product\Option::delete() を使うこと。
     * @see docs/adr/0034-split-product-service.md
     */
    public function deleteOption(int|string $productId, int|string $id, ?string $accessToken = null): NoContent|Errors
    {
        return $this->optionService()->delete($productId, $id, $accessToken);
    }

    /**
     * オプション値を作成する。
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @deprecated 0.26.0 Services\Product\Option\Value::create() を使うこと。
     * @see docs/adr/0034-split-product-service.md
     */
    public function createOptionValue(int|string $productId, int|string $optionId, ValueCreateInput $input, ?string $accessToken = null): Value|Errors
    {
        return $this->valueService()->create($productId, $optionId, $input, $accessToken);
    }

    /**
     * オプション値を削除する。
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @deprecated 0.26.0 Services\Product\Option\Value::delete() を使うこと。
     * @see docs/adr/0034-split-product-service.md
     */
    public function deleteOptionValue(int|string $productId, int|string $optionId, int|string $id, ?string $accessToken = null): NoContent|Errors
    {
        return $this->valueService()->delete($productId, $optionId, $id, $accessToken);
    }

    /**
     * おすすめ商品情報を作成する。
     * @throws ParameterException アクセストークンが空、または必須フィールドが未指定の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @deprecated 0.26.0 Services\Product\Pickup::create() を使うこと。
     * @see docs/adr/0034-split-product-service.md
     */
    public function createPickup(int|string $productId, PickupInput $input, ?string $accessToken = null): Pickup|Errors
    {
        return $this->pickupService()->create($productId, $input, $accessToken);
    }

    /**
     * おすすめ商品情報を更新する。
     * @throws ParameterException アクセストークンが空、または必須フィールドが未指定の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @deprecated 0.26.0 Services\Product\Pickup::update() を使うこと。
     * @see docs/adr/0034-split-product-service.md
     */
    public function updatePickup(int|string $productId, PickupInput $input, ?string $accessToken = null): Pickup|Errors
    {
        return $this->pickupService()->update($productId, $input, $accessToken);
    }

    /**
     * おすすめ商品情報を削除する。
     * @throws ParameterException アクセストークンが空、または種別が不正な場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @deprecated 0.26.0 Services\Product\Pickup::delete() を使うこと。
     * @see docs/adr/0034-split-product-service.md
     */
    public function deletePickup(int|string $productId, PickupType|int|string $pickupType, ?string $accessToken = null): Pickup|Errors
    {
        return $this->pickupService()->delete($productId, $pickupType, $accessToken);
    }

    /**
     * 商品画像を作成する。
     * @param string|resource|\Psr\Http\Message\StreamInterface $image 画像ファイルのパス、または読み取り可能なストリーム
     * @param string|null $filename multipart で送るファイル名
     * @throws ParameterException アクセストークンが空、またはファイル/ストリームを読み取れない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @deprecated 0.26.0 Services\Product\Image::create() を使うこと。
     * @see docs/adr/0034-split-product-service.md
     */
    public function createImage(int|string $productId, mixed $image, int $position, ?string $accessToken = null, ?string $filename = null): ImageEntity|Errors
    {
        return $this->imageService()->create($productId, $image, $position, $accessToken, $filename);
    }

    /**
     * 商品画像を削除する。
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @deprecated 0.26.0 Services\Product\Image::delete() を使うこと。
     * @see docs/adr/0034-split-product-service.md
     */
    public function deleteImage(int|string $productId, int $position, ?string $accessToken = null): NoContent|Errors
    {
        return $this->imageService()->delete($productId, $position, $accessToken);
    }

    private function variantService(): VariantService
    {
        return new VariantService($this->_accessToken, $this->_httpClient);
    }

    private function optionService(): OptionService
    {
        return new OptionService($this->_accessToken, $this->_httpClient);
    }

    private function valueService(): ValueService
    {
        return new ValueService($this->_accessToken, $this->_httpClient);
    }

    private function pickupService(): PickupService
    {
        return new PickupService($this->_accessToken, $this->_httpClient);
    }

    private function imageService(): ImageService
    {
        return new ImageService($this->_accessToken, $this->_httpClient);
    }

    private function groupService(): GroupService
    {
        return new GroupService($this->_accessToken, $this->_httpClient);
    }

    private function categoryService(): CategoryService
    {
        return new CategoryService($this->_accessToken, $this->_httpClient);
    }

    private function advertisingService(): AdvertisingService
    {
        return new AdvertisingService($this->_accessToken, $this->_httpClient);
    }

    private function stockService(): StockService
    {
        return new StockService($this->_accessToken, $this->_httpClient);
    }
}
