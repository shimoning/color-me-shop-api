<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 商品の作成 (POST /v1/products) と更新 (PUT /v1/products/{id}) の `product` 入力。
 *
 * 公式 OpenAPI の両 `product` object の和集合を表し、作成と更新で共用する (ADR 0014)。
 * 更新側にだけある `category_id_small` / `stocks` / `group_ids` / `variants` を含む。
 * どの操作でどのフィールドが有効かは公式 API 契約に従って利用者が選ぶ。
 *
 * 直列化の契約:
 * - コンストラクタ配列で明示したフィールドだけを送信する。
 * - 明示した `null` も送信する。実測では `sales_price` を `null` でクリアできた。
 * - 指定しなかったフィールドは送信しない。実測では `name` だけの PUT が部分更新として動作した。
 *
 * `display_state` は実測で受理された `showing` / `hidden` / `showing_for_members` /
 * `sale_for_members` の4値 (`ProductDisplayState`) だけを受け付け、`members_only` は拒否する。
 * `unlisted` は実測で書き込みできなかったため入力フィールドに持たない。
 *
 * 値は API の生の形で指定する。enum はバッキング値の文字列、`stocks` は整数または
 * `['increment' => int]`、`variants` は `option1_value` / `option2_value` / `stocks` を持つ
 * 連想配列のリストで、いずれもそのまま送信される。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
class ProductInput extends Entity implements RequestEntity
{
    public const OBJECT_FIELDS = [
        'displayState' => ['enum' => ProductDisplayState::class],
    ];

    protected ?string $name;
    protected ?int $price;
    protected ?int $categoryIdBig;
    protected ?int $categoryIdSmall;
    protected ?int $cost;
    protected ?int $salesPrice;
    protected ?int $membersPrice;
    protected ?string $modelNumber;
    protected ?string $expl;
    protected ?string $simpleExpl;
    protected ?string $smartphoneExpl;
    protected ?ProductDisplayState $displayState;
    protected ?bool $stockManaged;
    /** @var int|array{increment: int}|null 更新専用。整数の絶対値か increment object */
    protected int|array|null $stocks;
    /** @var list<int>|null 更新専用 */
    protected ?array $groupIds;
    /** @var list<array{option1_value?: string, option2_value?: string, stocks?: int|array{increment: int}|null}>|null 更新専用 */
    protected ?array $variants;
    protected ?bool $taxReduced;
}
