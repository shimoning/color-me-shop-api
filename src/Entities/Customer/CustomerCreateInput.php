<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Customer;

use Shimoning\ColorMeShopApi\Constants\Prefecture;
use Shimoning\ColorMeShopApi\Constants\Sex;
use Shimoning\ColorMeShopApi\Entities\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Values\Furigana;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

/**
 * 顧客データの追加 (POST /v1/customers) の `customer` 入力。
 *
 * 明示したフィールドだけを送信し、明示した `null` も送信する。必須6項目の未指定と、必須項目の
 * `null` は構築時に拒否する。`add_member` は明示した場合だけ送信する。`furigana` は文字列または
 * `Furigana` のインスタンスで指定する。
 *
 * 公式 OpenAPI との差分: `sex` は作成 request にないが実 API で反映される。`tel_mobile` / `memo` /
 * `points` / `member` / `sales_count` は作成時に無視されるため入力に含めない (2026-09-25)。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 * @see docs/api-customer-structure.md
 * @see docs/adr/0015-model-customer-write-api.md
 * @final 0.32.0 で予告。次のメジャーな版で final にする。
 */
class CustomerCreateInput extends Entity implements RequestEntity
{
    public const FIELD_TYPES = [
        'furigana' => ['allowNull' => true, 'value' => Furigana::class],
        'prefId' => ['enum' => Prefecture::class],
        'sex' => ['enum' => Sex::class],
    ];

    /**
     * 公式 OpenAPI が required とする `customer` の子プロパティ。
     * コンストラクタが構築時に明示を確認する。
     */
    public const REQUIRED_FIELDS = ['name', 'mail', 'pref_id', 'postal', 'address1', 'tel'];

    /**
     * @param array<string, mixed> $data
     * @throws InvalidFieldException 必須フィールドが未指定、またはフィールド値が不正な場合
     */
    public function __construct(array $data)
    {
        self::assertRequiredFields($data, self::REQUIRED_FIELDS);
        parent::__construct($data);
    }

    protected string $name;
    protected string $mail;
    protected Prefecture $prefId;
    protected string $postal;
    protected string $address1;
    protected string $tel;

    protected ?string $address2;
    protected ?Furigana $furigana;
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
    protected ?bool $addMember;
}
