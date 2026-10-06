<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Sale;

use Shimoning\ColorMeShopApi\Aliases;
use Shimoning\ColorMeShopApi\Constants\Prefecture;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Values\Furigana;

/**
 * 受注作成時のお届け先。
 *
 * `preferred_date` は既存の受注更新入力と同じく文字列をそのまま送信する。
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/createSale
 */
class DeliveryCreateInput extends Entity implements RequestEntity
{
    public const FIELD_TYPES = [
        'furigana' => ['value' => Furigana::class],
        'prefId' => ['enum' => Prefecture::class],
    ];

    public const REQUIRED_FIELDS = [
        'delivery_id',
        'name',
        'furigana',
        'postal',
        'pref_id',
        'address1',
        'tel',
    ];

    protected int $deliveryId;
    protected string $name;
    protected Furigana $furigana;
    protected string $postal;
    protected Prefecture $prefId;
    protected string $address1;
    protected ?string $address2;
    protected string $tel;
    protected ?string $preferredDate;
    protected ?string $preferredPeriod;
    protected ?string $memo;
}

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーで削除予定。
Aliases::defineLegacyAlias(DeliveryCreateInput::class);
