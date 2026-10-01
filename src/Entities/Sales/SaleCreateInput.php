<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Sales;

use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 受注データの作成 (POST /v1/sales) の `sale` 入力。
 *
 * 必須フィールドは Services\Sales::create() が送信前に検証する。
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/createSale
 */
class SaleCreateInput extends Entity implements RequestEntity
{
    public const FIELD_TYPES = [
        'customer' => ['allowNull' => true, 'entity' => SaleCustomerCreateInput::class],
        'saleDeliveries' => ['array' => true, 'entity' => SaleDeliveryCreateInput::class],
        'details' => ['array' => true, 'entity' => SaleDetailCreateInput::class],
    ];

    public const REQUIRED_FIELDS = ['payment_id', 'details'];

    protected ?SaleCustomerCreateInput $customer;
    /** @var list<SaleDeliveryCreateInput> */
    protected ?array $saleDeliveries;
    /** @var list<SaleDetailCreateInput> */
    protected array $details;
    protected int $paymentId;
}
