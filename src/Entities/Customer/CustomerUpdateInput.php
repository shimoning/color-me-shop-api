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
 * 公式 OpenAPI の更新 request だけに現れる `sex` / `tel_mobile` を持ち、作成専用の `add_member` は
 * 持たない。作成の `customer` には required 指定の子プロパティが6つあるのに対し、更新の `customer` には
 * required 指定がないため、作成の CustomerCreateInput とは別の型にしている (ADR 0015)。
 *
 * 直列化の契約:
 * - コンストラクタ配列で明示したフィールドだけを送信し、明示した `null` も送信する (ADR 0014)。
 * - 指定しなかったフィールドは送信しない (部分更新)。
 *
 * null 許容は公式 OpenAPI の `nullable` 指定にそのまま従う。`name` / `mail` / `pref_id` / `postal` /
 * `address1` / `tel` は nullable 指定がなく、作成では required でもあるため、明示した `null` を
 * 型として拒否する。これらをクリアする意味がないためで、ProductInput が全フィールドを nullable に
 * した判断 (ADR 0014) はこの Entity には適用しない。
 *
 * `sex` は応答と同じ `Sex` を使うが、要求側では未知値のフォールバックが働かないため、番兵の
 * `UNKNOWN` と未定義の文字列は構築時に拒否する (ADR 0013)。
 * `furigana` などの Value フィールドは生の値で指定する。Value のインスタンスは受け付けない。
 * 値の範囲 (`maxLength` や `pattern` など) は API 側の検証に委ねる。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 */
class CustomerUpdateInput extends Entity implements RequestEntity
{
    public const OBJECT_FIELDS = [
        'furigana' => ['allowNull' => true, 'value' => Furigana::class],
        'prefId' => ['enum' => Prefecture::class],
        'sex' => ['enum' => Sex::class],
    ];

    protected string $name;
    protected string $mail;
    protected Prefecture $prefId;
    protected string $postal;
    protected string $address1;
    protected string $tel;

    protected ?Furigana $furigana;
    protected ?string $address2;
    protected ?string $telMobile;
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
