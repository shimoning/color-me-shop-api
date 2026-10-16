<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Sale;

use Shimoning\ColorMeShopApi\Aliases;

use Shimoning\ColorMeShopApi\Entities\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

/**
 * 受注データの作成 (POST /v1/sales) の `sale` 入力。
 *
 * 必須フィールドはコンストラクタが構築時に検証する。
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/createSale
 * @final 0.32.0 で予告。次のメジャーな版で final にする。
 */
class SaleCreateInput extends Entity implements RequestEntity
{
    public const FIELD_TYPES = [
        'customer' => ['allowNull' => true, 'entity' => CustomerCreateInput::class],
        'saleDeliveries' => ['array' => true, 'entity' => DeliveryCreateInput::class],
        'details' => ['array' => true, 'entity' => DetailCreateInput::class],
    ];

    public const REQUIRED_FIELDS = ['payment_id', 'details'];

    /**
     * @param array<string, mixed> $data
     * @throws InvalidFieldException 必須フィールドが未指定、details が空、またはフィールド値が不正な場合
     */
    public function __construct(array $data)
    {
        self::assertRequiredFields($data, self::REQUIRED_FIELDS);
        parent::__construct($data);

        if ($this->details === []) {
            throw new InvalidFieldException(\sprintf(
                '%s の API フィールド『details』が不正です。1件以上の配列要素を指定してください (指定件数: 0)。',
                self::class,
            ));
        }
    }

    protected ?CustomerCreateInput $customer;
    /** @var list<DeliveryCreateInput> */
    protected ?array $saleDeliveries;
    /** @var list<DetailCreateInput> */
    protected array $details;
    protected int $paymentId;
}

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(SaleCreateInput::class);
