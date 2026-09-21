<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Product;

use Shimoning\ColorMeShopApi\Constants\ProductDisplayState;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

/**
 * 商品の作成 (POST /v1/products) と更新 (PUT /v1/products/{id}) の `product` 入力。
 *
 * 公式 OpenAPI の両 `product` object の和集合を表し、作成と更新で共用する (ADR 0014)。
 * 更新側にだけある `category_id_small` / `stocks` / `group_ids` / `variants` を含む。
 * どの操作でどのフィールドが有効かは公式 API 契約に従って利用者が選ぶ。
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
 * (バッキング値へ正規化して送信する)、`stocks` は整数または `['increment' => int]`、`variants` は
 * `option1_value` / `option2_value` / `stocks` を持つ連想配列のリストで、いずれもそのまま送信される。
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
    /** @var list<array{option1_value?: string, option2_value?: string, stocks?: int|array{increment: int}}>|null 更新専用 */
    protected ?array $variants;
    protected ?bool $taxReduced;

    private const STOCKS_SHAPE = 'int|array{increment: int}';
    private const VARIANT_SHAPE = 'array{option1_value?: string, option2_value?: string, stocks?: int|array{increment: int}}';

    /**
     * @param array<string, mixed> $data
     * @throws InvalidFieldException ネストした配列・object の要素型や形状が公式 OpenAPI の定義に合わない場合
     */
    public function __construct(array $data)
    {
        if (isset($data['group_ids']) && \is_array($data['group_ids'])) {
            self::assertIntList('group_ids', $data['group_ids']);
        }
        if (isset($data['stocks']) && \is_array($data['stocks'])) {
            self::assertIncrement('stocks', $data['stocks']);
        }
        if (isset($data['variants']) && \is_array($data['variants'])) {
            self::assertVariants($data['variants']);
        }
        parent::__construct($data);
    }

    /** @param array<mixed> $value */
    private static function assertIntList(string $apiField, array $value): void
    {
        if (! \array_is_list($value)) {
            throw InvalidFieldException::for(self::class, $apiField, 'list<int>', $value);
        }
        foreach ($value as $element) {
            if (! \is_int($element)) {
                throw InvalidFieldException::forArrayElement(
                    self::class,
                    $apiField,
                    'int',
                    new \TypeError('配列要素の型が不正です。'),
                );
            }
        }
    }

    /**
     * `stocks` の object 形状は `{"increment": int}` だけで、他のキーや型は受け付けない。
     * @param array<mixed> $value
     */
    private static function assertIncrement(string $apiField, array $value): void
    {
        if (\array_keys($value) !== ['increment'] || ! \is_int($value['increment'])) {
            throw InvalidFieldException::for(self::class, $apiField, self::STOCKS_SHAPE, $value);
        }
    }

    /** @param array<mixed> $value */
    private static function assertVariants(array $value): void
    {
        if (! \array_is_list($value)) {
            throw InvalidFieldException::for(self::class, 'variants', 'list<' . self::VARIANT_SHAPE . '>', $value);
        }
        foreach ($value as $variant) {
            if (! \is_array($variant) || ! self::isVariantShape($variant)) {
                throw InvalidFieldException::forArrayElement(
                    self::class,
                    'variants',
                    self::VARIANT_SHAPE,
                    new \TypeError('配列要素の型が不正です。'),
                );
            }
        }
    }

    /**
     * 空の配列は JSON で object ではなく `[]` になり OpenAPI の object 定義に合わないため、要素として認めない。
     * @param array<mixed> $variant
     */
    private static function isVariantShape(array $variant): bool
    {
        if ($variant === []) {
            return false;
        }
        foreach ($variant as $key => $element) {
            $valid = match ($key) {
                'option1_value', 'option2_value' => \is_string($element),
                'stocks' => \is_int($element)
                    || (\is_array($element) && \array_keys($element) === ['increment'] && \is_int($element['increment'])),
                default => false,
            };
            if (! $valid) {
                return false;
            }
        }

        return true;
    }
}
