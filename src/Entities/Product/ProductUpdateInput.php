<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 商品の更新 (PUT /v1/products/{id}) の `product` 入力。
 *
 * 直列化の契約:
 * - コンストラクタ配列で明示したフィールドだけを送信する。
 * - 明示した `null` も送信する。実測では `sales_price` と `price` を `null` でクリアできた。
 * - 指定しなかったフィールドは送信しない。実測では `name` だけの PUT が部分更新として動作した。
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
 * (バッキング値へ正規化して送信する)、`stocks` は整数または ProductStocksIncrementInput、`variants` は
 * ProductVariantInput のリストへ変換され、再帰的に API の object 形状で送信される。いずれも
 * 従来どおり連想配列でも指定でき、構築済みの各 Entity も指定できる。
 *
 * 要求側は厳格に検証する (ADR 0013 / 0014)。`group_ids` の要素は int、`stocks` の object は
 * `increment` キーだけを持つ int、`variants` は上記3キーのいずれかを持ち他のキーを持たない object の
 * リストとし (空の要素は JSON で `[]` になるため拒否する)、公式 OpenAPI の配列・object 定義に合わない
 * 形状は構築時に `InvalidFieldException` で拒否する。
 * ネストした `variants[].stocks` は OpenAPI に nullable 指定がなく実測もないため `null` を受け付けない
 * (ADR 0012)。トップレベルの nullable 化はネストした object のキーへは及ぼさない。
 * 値の範囲 (`minimum` など) は API 側の検証に委ね、ライブラリでは検証しない。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
class ProductUpdateInput extends Entity implements RequestEntity
{
    public const FIELD_TYPES = [
        'displayState' => ['enum' => ProductDisplayState::class],
        'stocks' => [
            'entity' => ProductStocksIncrementInput::class,
            'orScalar' => 'int',
            'allowNull' => true,
        ],
        'groupIds' => ['array' => true, 'scalar' => 'int'],
        'variants' => [
            'array' => true,
            'entity' => ProductVariantInput::class,
            'strictList' => true,
            'allowNull' => true,
        ],
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
    /** 更新専用。整数の絶対値か increment object */
    protected ProductStocksIncrementInput|int|null $stocks;
    /** @var list<int>|null 更新専用 */
    protected ?array $groupIds;
    /** @var list<ProductVariantInput>|null 更新専用 */
    protected ?array $variants;
    protected ?bool $taxReduced;
}
