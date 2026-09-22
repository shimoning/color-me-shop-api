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
use Shimoning\ColorMeShopApi\Entities\OAuth\Options as OAuthOptions;
use Shimoning\ColorMeShopApi\Entities\OAuth\AccessToken;
use Shimoning\ColorMeShopApi\Entities\OAuth\ErrorResponse as OAuthErrorResponse;
use Shimoning\ColorMeShopApi\Values\Scopes;

use Shimoning\ColorMeShopApi\Services\Shop;
use Shimoning\ColorMeShopApi\Entities\Shop\Shop as ShopEntity;

use Shimoning\ColorMeShopApi\Services\Sales;
use Shimoning\ColorMeShopApi\Entities\Sales\Sale;
use Shimoning\ColorMeShopApi\Entities\Sales\SearchParameters as SalesSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Sales\SaleUpdater;
use Shimoning\ColorMeShopApi\Entities\Sales\Stat as SaleStat;

use Shimoning\ColorMeShopApi\Services\Payment;
use Shimoning\ColorMeShopApi\Entities\Payment\Payment as PaymentEntity;

use Shimoning\ColorMeShopApi\Services\Delivery;
use Shimoning\ColorMeShopApi\Entities\Delivery\Delivery as DeliveryEntity;

use Shimoning\ColorMeShopApi\Services\Customer;
use Shimoning\ColorMeShopApi\Entities\Customer\SearchParameters as CustomerSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Customer\Customer as CustomerEntity;

use Shimoning\ColorMeShopApi\Services\Product;
use Shimoning\ColorMeShopApi\Entities\Product\Group as GroupEntity;
use Shimoning\ColorMeShopApi\Entities\Product\GroupInput as ProductGroupInput;
use Shimoning\ColorMeShopApi\Entities\Product\BigCategory as BigCategoryEntity;
use Shimoning\ColorMeShopApi\Entities\Product\SmallCategory as SmallCategoryEntity;
use Shimoning\ColorMeShopApi\Entities\Product\CategoryInput as ProductCategoryInput;
use Shimoning\ColorMeShopApi\Entities\Product\CategoryChildInput as ProductCategoryChildInput;
use Shimoning\ColorMeShopApi\Entities\Product\Product as ProductEntity;
use Shimoning\ColorMeShopApi\Entities\Product\ProductInput;
use Shimoning\ColorMeShopApi\Entities\Product\Variant as ProductVariantEntity;
use Shimoning\ColorMeShopApi\Entities\Product\VariantInput as ProductVariantInput;
use Shimoning\ColorMeShopApi\Entities\Product\Option as ProductOptionEntity;
use Shimoning\ColorMeShopApi\Entities\Product\OptionInput as ProductOptionInput;
use Shimoning\ColorMeShopApi\Entities\Product\OptionValue as ProductOptionValueEntity;
use Shimoning\ColorMeShopApi\Entities\Product\OptionValueInput as ProductOptionValueInput;
use Shimoning\ColorMeShopApi\Entities\Product\Pickup as ProductPickupEntity;
use Shimoning\ColorMeShopApi\Entities\Product\PickupInput as ProductPickupInput;
use Shimoning\ColorMeShopApi\Entities\Product\ProductImage as ProductImageEntity;
use Shimoning\ColorMeShopApi\Entities\Product\Advertising as ProductAdvertisingEntity;
use Shimoning\ColorMeShopApi\Entities\Product\AdvertisingSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\SearchParameters as ProductSearchParameters;
use Shimoning\ColorMeShopApi\Entities\Product\VariantSearchParameters;

/**
 * カラーミーショップ API の各機能を提供するクライアント。
 */
class Client
{
    protected string $accessToken;
    protected ?ClientInterface $httpClient;

    /**
     * 商品一覧を取得する。
     * @return Page<ProductEntity>|Errors
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function getProducts(?ProductSearchParameters $parameters = null, ?string $accessToken = null): Page|Errors
    {
        return $this->productService($accessToken)->products($parameters ?? new ProductSearchParameters([]), $accessToken);
    }

    /**
     * 商品単体を取得する。
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function getProduct(int|string $id, ?string $accessToken = null): ProductEntity|Errors
    {
        return $this->productService($accessToken)->product($id, $accessToken);
    }

    /**
     * 商品バリエーション一覧を取得する。実測の既定 limit は 10。
     * 検索条件では model_number / fields / limit / offset を指定できる。
     * @return Page<ProductVariantEntity>|Errors
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function getProductVariants(
        int|string $productId,
        ?VariantSearchParameters $parameters = null,
        ?string $accessToken = null,
    ): Page|Errors {
        return $this->productService($accessToken)->variants($productId, $parameters, $accessToken);
    }

    /**
     * 商品バリエーション単体を取得する。
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function getProductVariant(
        int|string $productId,
        int|string $id,
        ?string $accessToken = null,
    ): ProductVariantEntity|Errors {
        return $this->productService($accessToken)->variant($productId, $id, $accessToken);
    }

    /**
     * 商品画像専用 GET の一覧を取得する。
     * @return Collection<ProductImageEntity>|Errors
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function getProductImages(int|string $productId, ?string $accessToken = null): Collection|Errors
    {
        return $this->productService($accessToken)->images($productId, $accessToken);
    }

    /**
     * 商品広告一覧を取得する。
     * @return Page<ProductAdvertisingEntity>|Errors
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \Shimoning\ColorMeShopApi\Exceptions\InvalidPaginationException meta の型が不正な場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function getProductAdvertisings(
        ?AdvertisingSearchParameters $parameters = null,
        ?string $accessToken = null,
    ): Page|Errors
    {
        return $this->productService($accessToken)->advertisings($parameters, $accessToken);
    }

    /**
     * 商品グループ単体を取得する。
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function getProductGroup(int|string $id, ?string $accessToken = null): GroupEntity|Errors
    {
        return $this->productService($accessToken)->group($id, $accessToken);
    }

    /**
     * 商品を作成する。実測では `name` だけで作成できる。
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function createProduct(ProductInput $input, ?string $accessToken = null): ProductEntity|Errors
    {
        return $this->productService($accessToken)->create($input, $accessToken);
    }

    /**
     * 商品を更新する。明示したフィールドだけを送る部分更新で、明示した `null` はクリア要求として送信する。
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function updateProduct(int|string $id, ProductInput $input, ?string $accessToken = null): ProductEntity|Errors
    {
        return $this->productService($accessToken)->update($id, $input, $accessToken);
    }

    /**
     * 商品バリエーションを更新する。
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function updateProductVariant(
        int|string $productId,
        int|string $id,
        ProductVariantInput $input,
        ?string $accessToken = null,
    ): ProductVariantEntity|Errors {
        return $this->productService($accessToken)->updateVariant($productId, $id, $input, $accessToken);
    }

    /**
     * 商品オプションを作成する。
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function createProductOption(
        int|string $productId,
        ProductOptionInput $input,
        ?string $accessToken = null,
    ): ProductOptionEntity|Errors {
        return $this->productService($accessToken)->createOption($productId, $input, $accessToken);
    }

    /**
     * 商品オプションを削除する。成功は 204 で NoContent を返す。
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function deleteProductOption(int|string $productId, int|string $id, ?string $accessToken = null): NoContent|Errors
    {
        return $this->productService($accessToken)->deleteOption($productId, $id, $accessToken);
    }

    /**
     * 商品オプション値を作成する。
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function createProductOptionValue(
        int|string $productId,
        int|string $optionId,
        ProductOptionValueInput $input,
        ?string $accessToken = null,
    ): ProductOptionValueEntity|Errors {
        return $this->productService($accessToken)->createOptionValue($productId, $optionId, $input, $accessToken);
    }

    /**
     * 商品オプション値を削除する。成功は 204 で NoContent を返す。
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function deleteProductOptionValue(
        int|string $productId,
        int|string $optionId,
        int|string $id,
        ?string $accessToken = null,
    ): NoContent|Errors {
        return $this->productService($accessToken)->deleteOptionValue($productId, $optionId, $id, $accessToken);
    }

    /**
     * おすすめ商品情報を作成する。
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空、または `pickup_type` / `order_num` が未指定の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function createProductPickup(
        int|string $productId,
        ProductPickupInput $input,
        ?string $accessToken = null,
    ): ProductPickupEntity|Errors {
        return $this->productService($accessToken)->createPickup($productId, $input, $accessToken);
    }

    /**
     * おすすめ商品情報を更新する。
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空、または `pickup_type` / `order_num` が未指定の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function updateProductPickup(
        int|string $productId,
        ProductPickupInput $input,
        ?string $accessToken = null,
    ): ProductPickupEntity|Errors {
        return $this->productService($accessToken)->updatePickup($productId, $input, $accessToken);
    }

    /**
     * おすすめ商品情報を削除する。実測では 200 で削除済みの pickup を返す。
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空、または種別が `PickupType` の値でない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function deleteProductPickup(
        int|string $productId,
        PickupType|int|string $pickupType,
        ?string $accessToken = null,
    ): ProductPickupEntity|Errors {
        return $this->productService($accessToken)->deletePickup($productId, $pickupType, $accessToken);
    }

    /**
     * 商品画像を作成する。実 API 未検証 (プラン制限) で、公式 OpenAPI 定義に基づく。
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
        return $this->productService($accessToken)->createImage($productId, $image, $position, $accessToken, $filename);
    }

    /**
     * 商品画像を削除する。実 API 未検証 (プラン制限)。成功は 204 で NoContent を返す。
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function deleteProductImage(int|string $productId, int $position, ?string $accessToken = null): NoContent|Errors
    {
        return $this->productService($accessToken)->deleteImage($productId, $position, $accessToken);
    }

    /**
     * 商品グループを作成する。
     *
     * `display_state` は `GroupInput::WRITABLE_DISPLAY_STATES` の 3 値 (`showing` / `hidden` / `members_only`) で、
     * 実 API の観測 (2026-09-21) と公式 OpenAPI の request 定義に一致する。`GroupDisplayState` の応答専用の
     * 2 値 (`showing_for_members` / `sale_for_members`) は `GroupInput` の構築時に拒否される。
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function createProductGroup(ProductGroupInput $input, ?string $accessToken = null): GroupEntity|Errors
    {
        return $this->productService($accessToken)->createGroup($input, $accessToken);
    }

    /**
     * 商品グループを更新する。明示したフィールドだけを送る部分更新。
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function updateProductGroup(
        int|string $id,
        ProductGroupInput $input,
        ?string $accessToken = null,
    ): GroupEntity|Errors {
        return $this->productService($accessToken)->updateGroup($id, $input, $accessToken);
    }

    /**
     * 大カテゴリーを作成する。
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException 応答の `category` が配列以外、`Category::fromArray()` で変換できない、または `BigCategory` でない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function createProductCategory(ProductCategoryInput $input, ?string $accessToken = null): BigCategoryEntity|Errors
    {
        return $this->productService($accessToken)->createCategory($input, $accessToken);
    }

    /**
     * 大カテゴリーを更新する。明示したフィールドだけを送る部分更新。
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException 応答の `category` が配列以外、`Category::fromArray()` で変換できない、または `BigCategory` でない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function updateProductCategory(
        int|string $id,
        ProductCategoryInput $input,
        ?string $accessToken = null,
    ): BigCategoryEntity|Errors {
        return $this->productService($accessToken)->updateCategory($id, $input, $accessToken);
    }

    /**
     * 小カテゴリーを作成する。
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException 応答の `category` が配列以外、`Category::fromArray()` で変換できない、または `SmallCategory` でない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function createProductCategoryChild(
        int|string $categoryId,
        ProductCategoryChildInput $input,
        ?string $accessToken = null,
    ): SmallCategoryEntity|Errors {
        return $this->productService($accessToken)->createCategoryChild($categoryId, $input, $accessToken);
    }

    /**
     * 小カテゴリーを更新する。明示したフィールドだけを送る部分更新。
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが空の場合
     * @throws \Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException 応答の `category` が配列以外、`Category::fromArray()` で変換できない、または `SmallCategory` でない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function updateProductCategoryChild(
        int|string $categoryId,
        int|string $id,
        ProductCategoryChildInput $input,
        ?string $accessToken = null,
    ): SmallCategoryEntity|Errors {
        return $this->productService($accessToken)->updateCategoryChild($categoryId, $id, $input, $accessToken);
    }

    private function productService(?string $accessToken): Product
    {
        if ($accessToken !== null) {
            $this->accessToken = $accessToken;
        }
        return new Product($this->accessToken ?? '', $this->httpClient);
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
     * @link https://developer.shop-pro.jp/docs/colorme-api#section/API/%E5%88%A9%E7%94%A8%E6%89%8B%E9%A0%86#oauth%E3%82%A2%E3%83%97%E3%83%AA%E3%82%B1%E3%83%BC%E3%82%B7%E3%83%A7%E3%83%B3%E3%81%AE%E7%99%BB%E9%8C%B2
     * @param OAuthOptions $options
     * @param Scopes $scopes
     * @return string
     */
    public function getOAuthUrl(OAuthOptions $options, Scopes $scopes): string
    {
        return (new OAuth($options, $this->httpClient))->getUrl($scopes);
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
    public function exchangeCode2Token(
        OAuthOptions $options,
        string $code,
    ): AccessToken|OAuthErrorResponse|Errors
    {
        return (new OAuth($options, $this->httpClient))->exchangeCode2Token($code);
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
     * 受注データのリストを取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/getSales
     * @param SalesSearchParameters|null $searchParameters
     * @param string|null $accessToken
     * @return Page<Sale>|Errors
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが指定されていない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function getSales(
        ?SalesSearchParameters $searchParameters = null,
        ?string $accessToken = null,
    ): Page|Errors {
        return $this
            ->salesService($accessToken)
            ->page($searchParameters ?? new SalesSearchParameters([]), $accessToken);
    }

    /**
     * 売上集計の取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/statSale
     * @param \DateTimeInterface $dateTime
     * @param string|null $accessToken
     * @return SaleStat|Errors
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが指定されていない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function statSales(\DateTimeInterface $dateTime, ?string $accessToken = null): SaleStat|Errors
    {
        return $this->salesService($accessToken)->stat($dateTime, $accessToken);
    }

    /**
     * 受注データの取得
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
     * 受注データの更新
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/updateSale
     * @param SaleUpdater $updater
     * @param string|null $accessToken
     * @return Sale|Errors
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが指定されていない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function updateSale(SaleUpdater $updater, ?string $accessToken = null): Sale|Errors
    {
        return $this->salesService($accessToken)->update($updater, $accessToken);
    }

    /**
     * 受注のキャンセル
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
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/sendSalesMail
     * @param integer|string $id
     * @param MailType $mailType
     * @param string|null $accessToken
     * @return true|Errors
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが指定されていない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function sendSalesMail(
        int|string $id,
        MailType $mailType,
        ?string $accessToken = null,
    ): bool|Errors {
        return $this->salesService($accessToken)->sendMail($id, $mailType, $accessToken);
    }

    private function salesService(?string $accessToken = null): Sales
    {
        if ($accessToken !== null) {
            $this->accessToken = $accessToken;
        }
        return new Sales($this->accessToken ?? '', $this->httpClient);
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
     * 顧客データのリストを取得
     *
     * @link https://developer.shop-pro.jp/docs/colorme-api#tag/customer/operation/getCustomers
     * @param CustomerSearchParameters|null $searchParameters
     * @param string|null $accessToken
     * @return Page<CustomerEntity>|Errors
     * @throws \Shimoning\ColorMeShopApi\Exceptions\ParameterException アクセストークンが指定されていない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function getCustomers(
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
     * 顧客データの取得
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
        if ($accessToken !== null) {
            $this->accessToken = $accessToken;
        }
        return (new Customer($this->accessToken ?? '', $this->httpClient))->one($id, $accessToken);
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
        return (new Product($this->accessToken ?? '', $this->httpClient))->groups();
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
        return (new Product($this->accessToken ?? '', $this->httpClient))->categories();
    }
}
