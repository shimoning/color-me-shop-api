<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Services\Product;

use Shimoning\ColorMeShopApi\Communicator\Errors;
use Shimoning\ColorMeShopApi\Constants\PickupType;
use Shimoning\ColorMeShopApi\Entities\Product\Pickup\Pickup as PickupEntity;
use Shimoning\ColorMeShopApi\Entities\Product\Pickup\PickupInput;
use Shimoning\ColorMeShopApi\Exceptions\ParameterException;
use Shimoning\ColorMeShopApi\Services\Service;

/** おすすめ商品 API を操作するサービス。 */
class Pickup extends Service
{
    /**
     * おすすめ商品情報を作成する。入力はトップレベルの `pickup_type` / `order_num` で、応答の `pickup` を返す。
     *
     * `pickup_type` / `order_num` の未指定は送信前に拒否し、明示した `null` は送信する。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws ParameterException アクセストークンが空、または `pickup_type` / `order_num` が未指定の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function create(int|string $productId, PickupInput $input, ?string $accessToken = null): PickupEntity|Errors
    {
        $response = $this->_request(['json' => true], $accessToken)->post(
            $this->_endpoint('/products/' . $productId . '/pickups'),
            self::requirePickupFields($input),
        );
        return $this->_handle($response, static fn(?array $data): PickupEntity => new PickupEntity($data['pickup'] ?? []));
    }

    /**
     * おすすめ商品情報を更新する。入力はトップレベルの `pickup_type` / `order_num` で、応答の `pickup` を返す。
     *
     * 作成と同じく、`pickup_type` / `order_num` が未指定の入力は送信前に拒否する。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws ParameterException アクセストークンが空、または `pickup_type` / `order_num` が未指定の場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     */
    public function update(int|string $productId, PickupInput $input, ?string $accessToken = null): PickupEntity|Errors
    {
        $response = $this->_request(['json' => true], $accessToken)->put(
            $this->_endpoint('/products/' . $productId . '/pickups'),
            self::requirePickupFields($input),
        );
        return $this->_handle($response, static fn(?array $data): PickupEntity => new PickupEntity($data['pickup'] ?? []));
    }

    /**
     * おすすめ商品情報を削除する。
     *
     * 成功すると 200 で削除済みの `pickup` object を返す (2026-09-20)。
     * int / string の種別は `PickupType` の値 (0 / 1 / 3 / 4) として検証し、それ以外はパスへ載せずに拒否する。
     *
     * 必要な scope: `write_products` ({@see \Shimoning\ColorMeShopApi\Constants\AuthScope::WRITE_PRODUCTS})
     *
     * @throws ParameterException アクセストークンが空、または種別が `PickupType` の値でない場合
     * @throws \GuzzleHttp\Exception\GuzzleException HTTP リクエストに失敗した場合
     * @see docs/api-product-structure.md
     */
    public function delete(
        int|string $productId,
        PickupType|int|string $pickupType,
        ?string $accessToken = null,
    ): PickupEntity|Errors {
        $type = self::pickupTypeValue($pickupType);
        $response = $this->_request([], $accessToken)->delete(
            $this->_endpoint('/products/' . $productId . '/pickups/' . $type),
        );
        return $this->_handle($response, static fn(?array $data): PickupEntity => new PickupEntity($data['pickup'] ?? []));
    }

    /**
     * ピックアップ入力の `pickup_type` と `order_num` が明示されていることを送信前に確認する。
     *
     * 公式 OpenAPI との差分: 両フィールドに required 指定はないが、ライブラリでは未指定を拒否する。
     * 明示した `null` は API の検証に委ねる。
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
}
