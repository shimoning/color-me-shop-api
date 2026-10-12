<?php

namespace Shimoning\ColorMeShopApi;

use GuzzleHttp\ClientInterface;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Communicator\NoContent;
use Shimoning\ColorMeShopApi\Constants\MailType;
use Shimoning\ColorMeShopApi\Constants\PickupType;
use Shimoning\ColorMeShopApi\Entities\Page;
use Shimoning\ColorMeShopApi\Entities\Collection;

use Shimoning\ColorMeShopApi\Services\OAuth;
use Shimoning\ColorMeShopApi\Services\OAuth\Options as OAuthOptions;
use Shimoning\ColorMeShopApi\Entities\OAuth\AccessToken;
use Shimoning\ColorMeShopApi\Entities\OAuth\ErrorResponse as OAuthErrorResponse;
use Shimoning\ColorMeShopApi\Values\Scopes;

use Shimoning\ColorMeShopApi\Services\Shop;
use Shimoning\ColorMeShopApi\Entities\Shop\Shop as ShopEntity;

use Shimoning\ColorMeShopApi\Services\Gift;
use Shimoning\ColorMeShopApi\Entities\Gift\Gift as GiftEntity;

use Shimoning\ColorMeShopApi\Services\Sale as SaleService;
use Shimoning\ColorMeShopApi\Entities\Sale\Sale;
use Shimoning\ColorMeShopApi\Entities\Sale\SearchParameters as SalesSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Sale\SaleUpdateInput;
use Shimoning\ColorMeShopApi\Entities\Sale\SaleCreateInput;
use Shimoning\ColorMeShopApi\Entities\Customer\CustomerCreateInput;
use Shimoning\ColorMeShopApi\Entities\Customer\Points\PointsInput;
use Shimoning\ColorMeShopApi\Entities\Customer\CustomerUpdateInput;
use Shimoning\ColorMeShopApi\Entities\Customer\Points\Points as CustomerPoints;
use Shimoning\ColorMeShopApi\Entities\Sale\Stat\Stat as SaleStat;

use Shimoning\ColorMeShopApi\Services\Payment;
use Shimoning\ColorMeShopApi\Entities\Payment\Payment as PaymentEntity;

use Shimoning\ColorMeShopApi\Services\Delivery;
use Shimoning\ColorMeShopApi\Entities\Delivery\Delivery as DeliveryEntity;
use Shimoning\ColorMeShopApi\Entities\Delivery\Date\Date;

use Shimoning\ColorMeShopApi\Services\Customer;
use Shimoning\ColorMeShopApi\Entities\Customer\SearchParameters as CustomerSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Customer\Customer as CustomerEntity;

use Shimoning\ColorMeShopApi\Services\Product;
use Shimoning\ColorMeShopApi\Services\Product\Advertising as ProductAdvertisingService;
use Shimoning\ColorMeShopApi\Services\Product\Category as ProductCategoryService;
use Shimoning\ColorMeShopApi\Services\Product\Group as ProductGroupService;
use Shimoning\ColorMeShopApi\Services\Product\Image as ProductImageService;
use Shimoning\ColorMeShopApi\Services\Product\Option as ProductOptionService;
use Shimoning\ColorMeShopApi\Services\Product\Option\Value as ProductOptionValueService;
use Shimoning\ColorMeShopApi\Services\Product\Pickup as ProductPickupService;
use Shimoning\ColorMeShopApi\Services\Product\Stock as ProductStockService;
use Shimoning\ColorMeShopApi\Services\Product\Variant as ProductVariantService;
use Shimoning\ColorMeShopApi\Entities\Product\Group\Group as GroupEntity;
use Shimoning\ColorMeShopApi\Entities\Product\Group\GroupInput as ProductGroupInput;
use Shimoning\ColorMeShopApi\Entities\Product\Category\BigCategory as BigCategoryEntity;
use Shimoning\ColorMeShopApi\Entities\Product\Category\SmallCategory as SmallCategoryEntity;
use Shimoning\ColorMeShopApi\Entities\Product\Category\CategoryInput as ProductCategoryInput;
use Shimoning\ColorMeShopApi\Entities\Product\Category\ChildInput;
use Shimoning\ColorMeShopApi\Entities\Product\Product as ProductEntity;
use Shimoning\ColorMeShopApi\Entities\Product\ProductCreateInput;
use Shimoning\ColorMeShopApi\Entities\Product\ProductUpdateInput;
use Shimoning\ColorMeShopApi\Entities\Product\Variant\Variant as ProductVariantEntity;
use Shimoning\ColorMeShopApi\Entities\Product\Variant\VariantUpdateInput as ProductVariantUpdateInput;
use Shimoning\ColorMeShopApi\Entities\Product\Option\Option as ProductOptionEntity;
use Shimoning\ColorMeShopApi\Entities\Product\Option\OptionCreateInput as ProductOptionCreateInput;
use Shimoning\ColorMeShopApi\Entities\Product\Option\Value\Value as ProductOptionValueEntity;
use Shimoning\ColorMeShopApi\Entities\Product\Option\Value\ValueCreateInput as ProductOptionValueCreateInput;
use Shimoning\ColorMeShopApi\Entities\Product\Pickup\Pickup as ProductPickupEntity;
use Shimoning\ColorMeShopApi\Entities\Product\Pickup\PickupInput as ProductPickupInput;
use Shimoning\ColorMeShopApi\Entities\Product\Image\Image as ProductImageEntity;
use Shimoning\ColorMeShopApi\Entities\Product\Advertising\Advertising as ProductAdvertisingEntity;
use Shimoning\ColorMeShopApi\Entities\Product\Advertising\SearchParameters as ProductAdvertisingSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\SearchParameters as ProductSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\Variant\SearchParameters as VariantSearchParameters;

use Shimoning\ColorMeShopApi\Entities\Product\Stock\SearchParameters as StockSearchParameters;

/**
 * カラーミーショップ API の各機能を提供するクライアント。
 */
class Client
{
    protected string $accessToken;
    protected ?ClientInterface $httpClient;

    /**
     * 商品一覧を取得する。
     *
     * 必要な scope: `read_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::READ_PRODUCTS})
     *
     * @return Page<ProductEntity>|Errors
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function getProductPage(?ProductSearchParameters $parameters = null, ?string $accessToken = null): Page|Errors
    {
        return $this->productService($accessToken)->page($parameters ?? new ProductSearchParameters([]), $accessToken);
    }

    /**
     * @deprecated 0.25.0 getProductPage() を使うこと。
     * @see docs/adr/0033-unify-client-and-service-method-names.md
     */
    public function getProducts(?ProductSearchParameters $parameters = null, ?string $accessToken = null): Page|Errors
    {
        return $this->getProductPage($parameters, $accessToken);
    }

    /**
     * 在庫情報一覧を取得する。
     *
     * @return Page<\Shimoning\ColorMeShopApi\Entities\Product\Stock\Stock>|Errors
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function getProductStockPage(?StockSearchParameters $parameters = null, ?string $accessToken = null): Page|Errors
    {
        return $this->productStockService($accessToken)->page(
            $parameters ?? new StockSearchParameters([]),
            $accessToken,
        );
    }

    /**
     * @deprecated 0.25.0 getProductStockPage() を使うこと。
     * @see docs/adr/0033-unify-client-and-service-method-names.md
     */
    public function getStocks(?StockSearchParameters $parameters = null, ?string $accessToken = null): Page|Errors
    {
        return $this->getProductStockPage($parameters, $accessToken);
    }

    /**
     * 商品単体を取得する。
     *
     * 必要な scope: `read_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::READ_PRODUCTS})
     *
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function getProduct(int|string $id, ?string $accessToken = null): ProductEntity|Errors
    {
        return $this->productService($accessToken)->one($id, $accessToken);
    }

    /**
     * 商品バリエーション一覧を取得する。検索条件では model_number / fields / limit / offset を指定できる。
     *
     * 公式 OpenAPI との差分: 未記載の既定 limit は 10 (2026-09-18)。
     *
     * 必要な scope: `read_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::READ_PRODUCTS})
     *
     * @return Page<ProductVariantEntity>|Errors
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @see docs/api-product-structure.md
     */
    public function getProductVariantPage(
        int|string $productId,
        ?VariantSearchParameters $parameters = null,
        ?string $accessToken = null,
    ): Page|Errors {
        return $this->productVariantService($accessToken)->page($productId, $parameters, $accessToken);
    }

    /**
     * @deprecated 0.25.0 getProductVariantPage() を使うこと。
     * @see docs/adr/0033-unify-client-and-service-method-names.md
     */
    public function getProductVariants(
        int|string $productId,
        ?VariantSearchParameters $parameters = null,
        ?string $accessToken = null,
    ): Page|Errors {
        return $this->getProductVariantPage($productId, $parameters, $accessToken);
    }

    /**
     * 商品バリエーション単体を取得する。
     *
     * 必要な scope: `read_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::READ_PRODUCTS})
     *
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function getProductVariant(
        int|string $productId,
        int|string $id,
        ?string $accessToken = null,
    ): ProductVariantEntity|Errors {
        return $this->productVariantService($accessToken)->one($productId, $id, $accessToken);
    }

    /**
     * 商品画像専用 GET の一覧を取得する。
     *
     * 必要な scope: `read_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::READ_PRODUCTS})
     *
     * @return Collection<ProductImageEntity>|Errors
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function getProductImages(int|string $productId, ?string $accessToken = null): Collection|Errors
    {
        return $this->productImageService($accessToken)->all($productId, $accessToken);
    }

    /**
     * 商品広告一覧を取得する。
     *
     * 必要な scope: `read_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::READ_PRODUCTS})
     *
     * @return Page<ProductAdvertisingEntity>|Errors
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \Shimoning\ColorMeShopApi\Exceptions\InvalidPaginationException meta の型が不正な場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function getProductAdvertisingPage(
        ?ProductAdvertisingSearchParameters $parameters = null,
        ?string $accessToken = null,
    ): Page|Errors
    {
        return $this->productAdvertisingService($accessToken)->page($parameters, $accessToken);
    }

    /**
     * @deprecated 0.25.0 getProductAdvertisingPage() を使うこと。
     * @see docs/adr/0033-unify-client-and-service-method-names.md
     */
    public function getProductAdvertisings(
        ?ProductAdvertisingSearchParameters $parameters = null,
        ?string $accessToken = null,
    ): Page|Errors {
        return $this->getProductAdvertisingPage($parameters, $accessToken);
    }

    /**
     * 商品グループ単体を取得する。
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function getProductGroup(int|string $id, ?string $accessToken = null): GroupEntity|Errors
    {
        return $this->productGroupService($accessToken)->one($id, $accessToken);
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
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @see docs/api-product-structure.md
     */
    public function createProduct(ProductCreateInput $input, ?string $accessToken = null): ProductEntity|Errors
    {
        return $this->productService($accessToken)->create($input, $accessToken);
    }

    /**
     * 商品を更新する。明示したフィールドだけを送る部分更新で、明示した `null` はクリア要求として送信する。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function updateProduct(int|string $id, ProductUpdateInput $input, ?string $accessToken = null): ProductEntity|Errors
    {
        return $this->productService($accessToken)->update($id, $input, $accessToken);
    }

    /**
     * 商品バリエーションを更新する。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function updateProductVariant(
        int|string $productId,
        int|string $id,
        ProductVariantUpdateInput $input,
        ?string $accessToken = null,
    ): ProductVariantEntity|Errors {
        return $this->productVariantService($accessToken)->update($productId, $id, $input, $accessToken);
    }

    /**
     * 商品オプションを作成する。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function createProductOption(
        int|string $productId,
        ProductOptionCreateInput $input,
        ?string $accessToken = null,
    ): ProductOptionEntity|Errors {
        return $this->productOptionService($accessToken)->create($productId, $input, $accessToken);
    }

    /**
     * 商品オプションを削除する。成功は 204 で NoContent を返す。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function deleteProductOption(int|string $productId, int|string $id, ?string $accessToken = null): NoContent|Errors
    {
        return $this->productOptionService($accessToken)->delete($productId, $id, $accessToken);
    }

    /**
     * 商品オプション値を作成する。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function createProductOptionValue(
        int|string $productId,
        int|string $optionId,
        ProductOptionValueCreateInput $input,
        ?string $accessToken = null,
    ): ProductOptionValueEntity|Errors {
        return $this->productOptionValueService($accessToken)->create($productId, $optionId, $input, $accessToken);
    }

    /**
     * 商品オプション値を削除する。成功は 204 で NoContent を返す。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function deleteProductOptionValue(
        int|string $productId,
        int|string $optionId,
        int|string $id,
        ?string $accessToken = null,
    ): NoContent|Errors {
        return $this->productOptionValueService($accessToken)->delete($productId, $optionId, $id, $accessToken);
    }

    /**
     * おすすめ商品情報を作成する。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空、または `pickup_type` / `order_num` が未指定の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function createProductPickup(
        int|string $productId,
        ProductPickupInput $input,
        ?string $accessToken = null,
    ): ProductPickupEntity|Errors {
        return $this->productPickupService($accessToken)->create($productId, $input, $accessToken);
    }

    /**
     * おすすめ商品情報を更新する。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空、または `pickup_type` / `order_num` が未指定の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function updateProductPickup(
        int|string $productId,
        ProductPickupInput $input,
        ?string $accessToken = null,
    ): ProductPickupEntity|Errors {
        return $this->productPickupService($accessToken)->update($productId, $input, $accessToken);
    }

    /**
     * おすすめ商品情報を削除する。
     *
     * 成功すると 200 で削除済みの pickup を返す (2026-09-20)。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空、または種別が `PickupType` の値でない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @see docs/api-product-structure.md
     */
    public function deleteProductPickup(
        int|string $productId,
        PickupType|int|string $pickupType,
        ?string $accessToken = null,
    ): ProductPickupEntity|Errors {
        return $this->productPickupService($accessToken)->delete($productId, $pickupType, $accessToken);
    }

    /**
     * 商品画像を作成する。
     *
     * 必要な scope: `read_products` と `write_products` の両方
     * ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::READ_PRODUCTS}、{@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @param string|resource|\Psr\Http\Message\StreamInterface $image 画像ファイルのパス、または読み取り可能なストリーム
     * @param string|null $filename multipart で送るファイル名。ストリーム入力では拡張子付きの名前を指定する
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空、またはファイル/ストリームを読み取れない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function createProductImage(
        int|string $productId,
        mixed $image,
        int $position,
        ?string $accessToken = null,
        ?string $filename = null,
    ): ProductImageEntity|Errors {
        return $this->productImageService($accessToken)->create($productId, $image, $position, $accessToken, $filename);
    }

    /**
     * 商品画像を削除する。成功は 204 で NoContent を返す。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function deleteProductImage(int|string $productId, int $position, ?string $accessToken = null): NoContent|Errors
    {
        return $this->productImageService($accessToken)->delete($productId, $position, $accessToken);
    }

    /**
     * 商品グループを作成する。
     *
     * `display_state` は `showing` / `hidden` / `members_only` に限り、応答専用の2値は構築時に拒否する。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @see docs/api-product-structure.md
     */
    public function createProductGroup(ProductGroupInput $input, ?string $accessToken = null): GroupEntity|Errors
    {
        return $this->productGroupService($accessToken)->create($input, $accessToken);
    }

    /**
     * 商品グループを更新する。明示したフィールドだけを送る部分更新。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function updateProductGroup(
        int|string $id,
        ProductGroupInput $input,
        ?string $accessToken = null,
    ): GroupEntity|Errors {
        return $this->productGroupService($accessToken)->update($id, $input, $accessToken);
    }

    /**
     * 大カテゴリーを作成する。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException 応答の `category` が配列以外、`Category::fromArray()` で変換できない、または `BigCategory` でない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function createProductCategory(ProductCategoryInput $input, ?string $accessToken = null): BigCategoryEntity|Errors
    {
        return $this->productCategoryService($accessToken)->create($input, $accessToken);
    }

    /**
     * 大カテゴリーを更新する。明示したフィールドだけを送る部分更新。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException 応答の `category` が配列以外、`Category::fromArray()` で変換できない、または `BigCategory` でない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function updateProductCategory(
        int|string $id,
        ProductCategoryInput $input,
        ?string $accessToken = null,
    ): BigCategoryEntity|Errors {
        return $this->productCategoryService($accessToken)->update($id, $input, $accessToken);
    }

    /**
     * 小カテゴリーを作成する。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException 応答の `category` が配列以外、`Category::fromArray()` で変換できない、または `SmallCategory` でない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function createProductCategoryChild(
        int|string $categoryId,
        ChildInput $input,
        ?string $accessToken = null,
    ): SmallCategoryEntity|Errors {
        return $this->productCategoryService($accessToken)->createChild($categoryId, $input, $accessToken);
    }

    /**
     * 小カテゴリーを更新する。明示したフィールドだけを送る部分更新。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException 応答の `category` が配列以外、`Category::fromArray()` で変換できない、または `SmallCategory` でない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function updateProductCategoryChild(
        int|string $categoryId,
        int|string $id,
        ChildInput $input,
        ?string $accessToken = null,
    ): SmallCategoryEntity|Errors {
        return $this->productCategoryService($accessToken)->updateChild($categoryId, $id, $input, $accessToken);
    }

    private function productService(?string $accessToken): Product
    {
        return new Product($this->effectiveAccessToken($accessToken), $this->httpClient);
    }

    private function productVariantService(?string $accessToken): ProductVariantService
    {
        return new ProductVariantService($this->effectiveAccessToken($accessToken), $this->httpClient);
    }

    private function productOptionService(?string $accessToken): ProductOptionService
    {
        return new ProductOptionService($this->effectiveAccessToken($accessToken), $this->httpClient);
    }

    private function productOptionValueService(?string $accessToken): ProductOptionValueService
    {
        return new ProductOptionValueService($this->effectiveAccessToken($accessToken), $this->httpClient);
    }

    private function productPickupService(?string $accessToken): ProductPickupService
    {
        return new ProductPickupService($this->effectiveAccessToken($accessToken), $this->httpClient);
    }

    private function productImageService(?string $accessToken): ProductImageService
    {
        return new ProductImageService($this->effectiveAccessToken($accessToken), $this->httpClient);
    }

    private function productGroupService(?string $accessToken): ProductGroupService
    {
        return new ProductGroupService($this->effectiveAccessToken($accessToken), $this->httpClient);
    }

    private function productCategoryService(?string $accessToken): ProductCategoryService
    {
        return new ProductCategoryService($this->effectiveAccessToken($accessToken), $this->httpClient);
    }

    private function productAdvertisingService(?string $accessToken): ProductAdvertisingService
    {
        return new ProductAdvertisingService($this->effectiveAccessToken($accessToken), $this->httpClient);
    }

    private function productStockService(?string $accessToken): ProductStockService
    {
        return new ProductStockService($this->effectiveAccessToken($accessToken), $this->httpClient);
    }

    private function effectiveAccessToken(?string $accessToken): string
    {
        if ($accessToken !== null) {
            $this->accessToken = $accessToken;
        }
        return $this->accessToken ?? '';
    }

    /**
     * @param string|null $accessToken
     * @param ClientInterface|null $httpClient HTTP クライアント (省略時は Guzzle のデフォルト)
     * @return void
     */
    public function __construct(?string $accessToken = null, ?ClientInterface $httpClient = null)
    {
        if ($accessToken !== null) {
            $this->accessToken = $accessToken;
        }
        $this->httpClient = $httpClient;
    }

    /**
     * OAuthアプリケーションの登録のための URL を取得する
     *
     * state の取り扱いの詳細は {@see OAuth::getUrl()} を参照。
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#section/API/%E5%88%A9%E7%94%A8%E6%89%8B%E9%A0%86#oauth%E3%82%A2%E3%83%97%E3%83%AA%E3%82%B1%E3%83%BC%E3%82%B7%E3%83%A7%E3%83%B3%E3%81%AE%E7%99%BB%E9%8C%B2
     * @param OAuthOptions $options
     * @param Scopes $scopes
     * @param string|null $state CSRF 対策に使用する state (省略時はクエリに含めない)
     * @return string
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException state に空文字を指定した場合
     */
    public function getOAuthUrl(OAuthOptions $options, Scopes $scopes, ?string $state = null): string
    {
        return (new OAuth($options, $this->httpClient))->getUrl($scopes, $state);
    }

    /**
     * 認可コードをアクセストークンに交換
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#section/API/%E5%88%A9%E7%94%A8%E6%89%8B%E9%A0%86#%E8%AA%8D%E5%8F%AF%E3%82%B3%E3%83%BC%E3%83%89%E3%82%92%E3%82%A2%E3%82%AF%E3%82%BB%E3%82%B9%E3%83%88%E3%83%BC%E3%82%AF%E3%83%B3%E3%81%AB%E4%BA%A4%E6%8F%9B
     * @param OAuthOptions $options
     * @param string $code
     * @return AccessToken|OAuthErrorResponse|Errors
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function exchangeCodeForToken(
        OAuthOptions $options,
        string $code,
    ): AccessToken|OAuthErrorResponse|Errors
    {
        return (new OAuth($options, $this->httpClient))->exchangeCodeForToken($code);
    }

    /**
     * @deprecated 0.25.0 exchangeCodeForToken() を使うこと。
     * @see docs/adr/0033-unify-client-and-service-method-names.md
     */
    public function exchangeCode2Token(
        OAuthOptions $options,
        string $code,
    ): AccessToken|OAuthErrorResponse|Errors {
        return $this->exchangeCodeForToken($options, $code);
    }


    /**
     * ショップ情報の取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/shop/operation/getShop
     * @param string|null $accessToken
     * @return ShopEntity|Errors
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが指定されていない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function getShop(?string $accessToken = null): ShopEntity|Errors
    {
        if ($accessToken !== null) {
            $this->accessToken = $accessToken;
        }
        return (new Shop($this->accessToken ?? '', $this->httpClient))->get();
    }

    /**
     * ギフト設定を取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/gift/operation/getGift
     * @param string|null $accessToken
     * @return GiftEntity|Errors
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが指定されていない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function getGift(?string $accessToken = null): GiftEntity|Errors
    {
        if ($accessToken !== null) {
            $this->accessToken = $accessToken;
        }
        return (new Gift($this->accessToken ?? '', $this->httpClient))->get();
    }

    /**
     * 受注データのリストを取得
     *
     * 必要な scope: `read_sales` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::READ_SALES})
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/getSales
     * @param SalesSearchParameters|null $searchParameters
     * @param string|null $accessToken
     * @return Page<Sale>|Errors
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが指定されていない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function getSalePage(
        ?SalesSearchParameters $searchParameters = null,
        ?string $accessToken = null,
    ): Page|Errors {
        return $this
            ->salesService($accessToken)
            ->page($searchParameters ?? new SalesSearchParameters([]), $accessToken);
    }

    /**
     * @deprecated 0.25.0 getSalePage() を使うこと。
     * @see docs/adr/0033-unify-client-and-service-method-names.md
     */
    public function getSales(
        ?SalesSearchParameters $searchParameters = null,
        ?string $accessToken = null,
    ): Page|Errors {
        return $this->getSalePage($searchParameters, $accessToken);
    }

    /**
     * 売上集計の取得
     *
     * 必要な scope: `read_sales` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::READ_SALES})
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/statSale
     * @param \DateTimeInterface $dateTime
     * @param string|null $accessToken
     * @return SaleStat|Errors
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが指定されていない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function getSaleStat(\DateTimeInterface $dateTime, ?string $accessToken = null): SaleStat|Errors
    {
        return $this->salesService($accessToken)->stat($dateTime, $accessToken);
    }

    /**
     * @deprecated 0.25.0 getSaleStat() を使うこと。
     * @see docs/adr/0033-unify-client-and-service-method-names.md
     */
    public function statSales(\DateTimeInterface $dateTime, ?string $accessToken = null): SaleStat|Errors
    {
        return $this->getSaleStat($dateTime, $accessToken);
    }

    /**
     * 受注データの取得
     *
     * 必要な scope: `read_sales` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::READ_SALES})
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/getSale
     * @param integer|string $id
     * @param string|null $accessToken
     * @return Sale|Errors
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが指定されていない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function getSale(int|string $id, ?string $accessToken = null): Sale|Errors
    {
        return $this->salesService($accessToken)->one($id, $accessToken);
    }

    /**
     * 受注データを作成する。
     *
     * 必要な scope: `write_sales` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_SALES})
     *
     * プレミアムプラン限定。対象外のプランでは 401 (code 401200) の Errors を返す (2026-10-01)。
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/createSale
     * @param SaleCreateInput $input
     * @param bool|null $reserveStocks 在庫を引き当てるか。null の場合はクエリへ含めない
     * @param string|null $accessToken
     * @return Sale|Errors
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空、または必須フィールドが未指定の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @see docs/api-sale-create-observation.md
     */
    public function createSale(
        SaleCreateInput $input,
        ?bool $reserveStocks = null,
        ?string $accessToken = null,
    ): Sale|Errors {
        return $this->salesService($accessToken)->create($input, $reserveStocks, $accessToken);
    }

    /**
     * 受注データの更新
     *
     * 必要な scope: `write_sales` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_SALES})
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/updateSale
     * @param int|string $id
     * @param SaleUpdateInput $input
     * @param string|null $accessToken
     * @return Sale|Errors
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが指定されていない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function updateSale(int|string $id, SaleUpdateInput $input, ?string $accessToken = null): Sale|Errors
    {
        return $this->salesService($accessToken)->update($id, $input, $accessToken);
    }

    /**
     * 受注のキャンセル
     *
     * 必要な scope: `write_sales` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_SALES})
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/cancelSale
     * @param integer|string $id
     * @param boolean|null $restock
     * @param string|null $accessToken
     * @return Sale|Errors
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが指定されていない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function cancelSale(
        int|string $id,
        ?bool $restock = false,
        ?string $accessToken = null,
    ): Sale|Errors {
        return $this->salesService($accessToken)->cancel($id, $restock, $accessToken);
    }

    /**
     * メールの送信
     *
     * 必要な scope: `write_sales` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_SALES})
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/sendSalesMail
     * @param integer|string $id
     * @param MailType $mailType
     * @param string|null $accessToken
     * @return true|Errors
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが指定されていない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function sendSaleMail(
        int|string $id,
        MailType $mailType,
        ?string $accessToken = null,
    ): bool|Errors {
        return $this->salesService($accessToken)->sendMail($id, $mailType, $accessToken);
    }

    /**
     * @deprecated 0.25.0 sendSaleMail() を使うこと。
     * @see docs/adr/0033-unify-client-and-service-method-names.md
     */
    public function sendSalesMail(
        int|string $id,
        MailType $mailType,
        ?string $accessToken = null,
    ): bool|Errors {
        return $this->sendSaleMail($id, $mailType, $accessToken);
    }

    private function salesService(?string $accessToken = null): SaleService
    {
        if ($accessToken !== null) {
            $this->accessToken = $accessToken;
        }
        return new SaleService($this->accessToken ?? '', $this->httpClient);
    }

    /**
     * 決済設定の一覧を取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/payment/operation/getPayments
     * @param string|null $accessToken
     * @return Collection<PaymentEntity>|Errors
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが指定されていない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function getPayments(?string $accessToken = null): Collection|Errors
    {
        if ($accessToken !== null) {
            $this->accessToken = $accessToken;
        }
        return (new Payment($this->accessToken ?? '', $this->httpClient))->all();
    }

    /**
     * 配送方法一覧を取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/delivery/operation/getDeliveries
     * @param string|null $accessToken
     * @return Collection<DeliveryEntity>|Errors
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが指定されていない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function getDeliveries(?string $accessToken = null): Collection|Errors
    {
        if ($accessToken !== null) {
            $this->accessToken = $accessToken;
        }
        return (new Delivery($this->accessToken ?? '', $this->httpClient))->all();
    }

    /**
     * 配送日時設定を取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/delivery/operation/getDeliveryDateSetting
     * @param string|null $accessToken
     * @return Date|Errors
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが指定されていない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function getDeliveryDateSetting(?string $accessToken = null): Date|Errors
    {
        if ($accessToken !== null) {
            $this->accessToken = $accessToken;
        }
        return (new Delivery($this->accessToken ?? '', $this->httpClient))->dateSetting();
    }

    /**
     * 顧客データのリストを取得
     *
     * 必要な scope: `read_sales` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::READ_SALES})
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/customer/operation/getCustomers
     * @param CustomerSearchParameters|null $searchParameters
     * @param string|null $accessToken
     * @return Page<CustomerEntity>|Errors
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが指定されていない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function getCustomerPage(
        ?CustomerSearchParameters $searchParameters = null,
        ?string $accessToken = null,
    ): Page|Errors {
        if ($accessToken !== null) {
            $this->accessToken = $accessToken;
        }
        return (new Customer($this->accessToken ?? '', $this->httpClient))
            ->page($searchParameters ?? new CustomerSearchParameters([]));
    }

    /**
     * @deprecated 0.25.0 getCustomerPage() を使うこと。
     * @see docs/adr/0033-unify-client-and-service-method-names.md
     */
    public function getCustomers(
        ?CustomerSearchParameters $searchParameters = null,
        ?string $accessToken = null,
    ): Page|Errors {
        return $this->getCustomerPage($searchParameters, $accessToken);
    }

    /**
     * 顧客データの取得
     *
     * 必要な scope: `read_sales` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::READ_SALES})
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/customer/operation/getCustomer
     * @param integer|string $id
     * @param string|null $accessToken
     * @return CustomerEntity|Errors
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが指定されていない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function getCustomer(int|string $id, ?string $accessToken = null): CustomerEntity|Errors
    {
        return $this->customerService($accessToken)->one($id, $accessToken);
    }

    /**
     * 顧客データを追加する。必須フィールドの未指定は Services\Customer::create() 呼び出し時に検証される。
     *
     * 必要な scope: `write_sales` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_SALES})
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/customer/operation/postCustomers
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空、または必須フィールドが未指定の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function createCustomer(CustomerCreateInput $input, ?string $accessToken = null): CustomerEntity|Errors
    {
        return $this->customerService($accessToken)->create($input, $accessToken);
    }

    /**
     * 顧客データを更新する。明示したフィールドだけを送る部分更新で、明示した `null` はクリア要求として送信する。
     * `name` / `address1` / `address2` の未指定は送信前に拒否する。
     *
     * 必要な scope: `write_sales` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_SALES})
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/customer/operation/updateCustomers
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空、または必須フィールドが未指定の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function updateCustomer(
        int|string $id,
        CustomerUpdateInput $input,
        ?string $accessToken = null,
    ): CustomerEntity|Errors {
        return $this->customerService($accessToken)->update($id, $input, $accessToken);
    }

    /**
     * 顧客のショップポイントを増減する。正の値が加算、負の値が減算。
     *
     * 必要な scope: `write_sales` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_SALES})
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/customer/operation/postCustomerPoints
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空、または `points` が未指定の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function changeCustomerPoints(
        int|string $id,
        PointsInput $input,
        ?string $accessToken = null,
    ): CustomerPoints|Errors {
        return $this->customerService($accessToken)->changePoints($id, $input, $accessToken);
    }

    /**
     * 顧客 API のサービスを、引数のアクセストークンを優先して生成する。
     */
    private function customerService(?string $accessToken): Customer
    {
        if ($accessToken !== null) {
            $this->accessToken = $accessToken;
        }
        return new Customer($this->accessToken ?? '', $this->httpClient);
    }

    /**
     * 商品グループ一覧を取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/group/operation/getProductGroups
     * @param string|null $accessToken
     * @return Collection<GroupEntity>|Errors
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが指定されていない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function getProductGroups(?string $accessToken = null): Collection|Errors
    {
        if ($accessToken !== null) {
            $this->accessToken = $accessToken;
        }
        return $this->productGroupService($accessToken)->all($accessToken);
    }

    /**
     * 商品カテゴリー一覧を取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/group/operation/getProductCategories
     * @param string|null $accessToken
     * @return Collection<BigCategoryEntity|SmallCategoryEntity>|Errors
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException 実効アクセストークンが空文字、または categories が配列以外の場合
     * @throws \Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException Category::fromArray() で API フィールドが不正、または categories の要素が配列以外の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function getProductCategories(?string $accessToken = null): Collection|Errors
    {
        if ($accessToken !== null) {
            $this->accessToken = $accessToken;
        }
        return $this->productCategoryService($accessToken)->all($accessToken);
    }
}
