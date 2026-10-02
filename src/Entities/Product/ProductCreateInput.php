<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 商品の作成 (POST /v1/products) の `product` 入力。
 *
 * 直列化の契約:
 * - コンストラクタ配列で明示したフィールドだけを送信する。
 * - 明示した `null` も送信する。実測では `sales_price` と `price` を `null` でクリアできた。
 * - 指定しなかったフィールドは送信しない。
 *
 * 公式 OpenAPI の `product` request は全フィールドとも nullable 指定がないが、本 Entity は
 * ADR 0014 の「明示した `null` はクリア要求」の契約に従い全フィールドを nullable にしている。
 * 実測の出典: docs/api-product-structure.md「書き込み系の観測」(b1ceab5、dee9609)。
 * `null` を受理するかは API 側の判断であり、実測していないフィールドへは一般化しない。
 *
 * `display_state` は実測で受理された `showing` / `hidden` / `showing_for_members` /
 * `sale_for_members` の4値 (`ProductDisplayState`) だけを受け付け、`members_only` は拒否する。
 * `unlisted` は実測で書き込みできなかったため入力フィールドに持たない。
 *
 * 値は API の生の形で指定する。enum はバッキング値の文字列または同じ enum のインスタンス
 * (バッキング値へ正規化して送信する)。値の範囲 (`minimum` など) は API 側の検証に委ね、
 * ライブラリでは検証しない。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
class ProductCreateInput extends Entity implements RequestEntity
{
    public const FIELD_TYPES = [
        'displayState' => ['enum' => ProductDisplayState::class],
    ];

    protected ?string $name;
    protected ?int $price;
    protected ?int $categoryIdBig;
    protected ?int $cost;
    protected ?int $salesPrice;
    protected ?int $membersPrice;
    protected ?string $modelNumber;
    protected ?string $expl;
    protected ?string $simpleExpl;
    protected ?string $smartphoneExpl;
    protected ?ProductDisplayState $displayState;
    protected ?bool $stockManaged;
    protected ?bool $taxReduced;
}
