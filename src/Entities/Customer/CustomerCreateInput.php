<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Customer;

use Shimoning\ColorMeShopApi\Constants\Prefecture;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Values\Furigana;

/**
 * 顧客データの追加 (POST /v1/customers) の `customer` 入力。
 *
 * 公式 OpenAPI の作成 request だけに現れるフィールドを持ち、更新専用の `sex` / `tel_mobile` は持たない
 * (ADR 0015)。作成と更新で required 指定が異なるため、更新の CustomerUpdateInput とは別の型にしている。
 *
 * 直列化の契約:
 * - コンストラクタ配列で明示したフィールドだけを送信し、明示した `null` も送信する (ADR 0014)。
 * - 指定しなかったフィールドは送信しない。
 *
 * 公式 OpenAPI が required とする `name` / `mail` / `pref_id` / `postal` / `address1` / `tel` は
 * REQUIRED_FIELDS として公開し、Services\Customer::create() が送信前に明示を確認する
 * (ADR 0014 のピックアップ入力と同じく、検証は Service 側で行う)。これらは nullable 指定がないため
 * 非 null のプロパティとして宣言しており、明示した `null` は型として `InvalidFieldException` になる。
 *
 * `furigana` などの Value フィールドは生の値で指定する。Value のインスタンスは受け付けない
 * (Entity::buildObject() が常に Value のコンストラクタへ渡すためで、既存の Value フィールドと同じ契約)。
 *
 * `add_member` は作成専用で、公式 OpenAPI の既定は `false`。ライブラリ側では既定値を補わず、
 * 明示したときだけ送信する。値の範囲 (`maxLength` や `pattern` など) は API 側の検証に委ねる。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
class CustomerCreateInput extends Entity implements RequestEntity
{
    public const OBJECT_FIELDS = [
        'furigana' => ['allowNull' => true, 'value' => Furigana::class],
        'prefId' => ['enum' => Prefecture::class],
    ];

    /**
     * 公式 OpenAPI が required とする `customer` の子プロパティ。
     * Services\Customer::create() が送信前に明示を確認する。
     */
    public const REQUIRED_FIELDS = ['name', 'mail', 'pref_id', 'postal', 'address1', 'tel'];

    protected string $name;
    protected string $mail;
    protected Prefecture $prefId;
    protected string $postal;
    protected string $address1;
    protected string $tel;

    protected ?string $address2;
    protected ?Furigana $furigana;
    protected ?string $fax;
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
