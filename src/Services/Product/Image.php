<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Services\Product;

use Psr\Http\Message\StreamInterface;
use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Communicator\NoContent;
use Shimoning\ColorMeShopApi\Entities\Collection;
use Shimoning\ColorMeShopApi\Entities\Product\Image\Image as ImageEntity;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use Shimoning\ColorMeShopApi\Services\Service;

/** 商品画像 API を操作するサービス。 */
class Image extends Service
{
    /**
     * 商品画像専用 GET。商品本体の追加画像と別構造。
     *
     * 必要な scope: `read_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::READ_PRODUCTS})
     *
     * @return Collection<ImageEntity>|Errors
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function all(int|string $productId, ?string $accessToken = null): Collection|Errors
    {
        $response = $this->_request([], $accessToken)->get(
            $this->_endpoint('/products/' . $productId . '/images'),
        );
        return $this->_handle($response, static fn(?array $data): Collection => Collection::cast(
            ImageEntity::class, $data['product']['images'] ?? [],
        ));
    }

    /**
     * 商品画像を作成する。`multipart/form-data` で `image` と `position` (0〜49) を送信する。
     *
     * 必要な scope: `read_products` と `write_products` の両方
     * ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::READ_PRODUCTS}、{@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @param string|resource|StreamInterface $image 画像ファイルのパス、または読み取り可能なストリーム
     * @param string|null $filename multipart で送るファイル名。省略時はパスまたはストリーム URI の末尾
     *   (php://memory などでは拡張子のない `memory`) になるため、ストリーム入力では拡張子付きの名前を指定する
     * @throws ParameterException アクセストークンが空、またはファイル/ストリームを読み取れない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function create(
        int|string $productId,
        mixed $image,
        int $position,
        ?string $accessToken = null,
        ?string $filename = null,
    ): ImageEntity|Errors {
        $response = $this->_request([], $accessToken)->postMultipart(
            $this->_endpoint('/products/' . $productId . '/images'),
            ['position' => $position],
            ['image' => $image],
            [],
            $filename === null ? [] : ['image' => $filename],
        );
        return $this->_handle($response, static fn(?array $data): ImageEntity => new ImageEntity($data['product_image'] ?? []));
    }

    /**
     * 商品画像を削除する。成功は 204 でボディがないため NoContent を返す。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws ParameterException アクセストークンが空の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function delete(int|string $productId, int $position, ?string $accessToken = null): NoContent|Errors
    {
        $response = $this->_request([], $accessToken)->delete(
            $this->_endpoint('/products/' . $productId . '/images/' . $position),
        );
        return $this->_handle($response, static fn(?array $_data): NoContent => new NoContent($response));
    }
}
