<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Sale;

use Shimoning\ColorMeShopApi\Aliases;

use Shimoning\ColorMeShopApi\Entities\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 受注データの作成 (POST /v1/sales) の `sale` 入力。
 *
 * 必須フィールドは Services\Sale::create() が送信前に検証する。
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/createSale
 */
class SaleCreateInput extends Entity implements RequestEntity
{
    public const FIELD_TYPES = [
        'customer' => ['allowNull' => true, 'entity' => CustomerCreateInput::class],
        'saleDeliveries' => ['array' => true, 'entity' => DeliveryCreateInput::class],
        'details' => ['array' => true, 'entity' => DetailCreateInput::class],
    ];

    public const REQUIRED_FIELDS = ['payment_id', 'details'];

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
