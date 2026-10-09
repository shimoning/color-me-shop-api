<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Customer;

use Shimoning\ColorMeShopApi\Constants\Prefecture;
use Shimoning\ColorMeShopApi\Constants\Sex;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Values\Furigana;

/**
 * 顧客データの更新 (PUT /v1/customers/{customer_id}) の `customer` 入力。
 *
 * 明示したフィールドだけを送信する部分更新で、明示した `null` も送信する。`name` / `address1` /
 * `address2` の未指定は送信前に拒否する。nullable でないフィールドの `null` と不正な `sex` は
 * 構築時に拒否する。作成専用の `add_member` は持たない。`furigana` は文字列または `Furigana` の
 * インスタンスで指定する。
 *
 * 公式 OpenAPI との差分: required 指定はないが `name` / `address1` は実 API で必須である (2026-09-25)。
 * `address2` は省略すると空になるため必須として扱う (2026-10-09)。`tel_mobile` は request にあるが
 * 書き込めないため入力に含めない (2026-09-25)。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 * @see docs/api-partial-update-observation.md
 * @see docs/api-customer-structure.md
 * @see docs/adr/0015-model-customer-write-api.md
 */
class CustomerUpdateInput extends Entity implements RequestEntity
{
    public const FIELD_TYPES = [
        'furigana' => ['allowNull' => true, 'value' => Furigana::class],
        'prefId' => ['enum' => Prefecture::class],
        'sex' => ['enum' => Sex::class],
    ];

    /** 実 API で必須と確認したフィールド。送信前に Service 側で明示を確認する。 */
    public const REQUIRED_FIELDS = ['name', 'address1', 'address2'];

    protected string $name;
    protected string $mail;
    protected Prefecture $prefId;
    protected string $postal;
    protected string $address1;
    protected string $tel;

    protected ?Furigana $furigana;
    protected ?string $address2;
    protected ?string $fax;
    protected ?Sex $sex;
    protected ?string $birthday;
    protected ?string $hojin;
    protected ?string $busho;
    protected ?bool $receiveMailMagazine;
    protected ?string $answerFreeForm1;
    protected ?string $answerFreeForm2;
    protected ?string $answerFreeForm3;
    protected ?string $other;
}
