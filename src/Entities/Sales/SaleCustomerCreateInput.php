<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Sales;

use Shimoning\ColorMeShopApi\Constants\Prefecture;
use Shimoning\ColorMeShopApi\Constants\Sex;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;
use Shimoning\ColorMeShopApi\Values\Furigana;

/**
 * 受注作成時の顧客情報。
 *
 * `id` に登録済み顧客の ID を指定した場合、API は `id` 以外の顧客情報を無視する。
 * ゲスト購入では `id` を省略し、必要な顧客属性を指定する。
 * `birthday` は既存の顧客作成入力と同じく文字列をそのまま送信する。
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/createSale
 */
class SaleCustomerCreateInput extends Entity implements RequestEntity
{
    public const FIELD_TYPES = [
        'furigana' => ['allowNull' => true, 'value' => Furigana::class],
        'prefId' => ['enum' => Prefecture::class],
        'sex' => ['enum' => Sex::class],
    ];

    protected ?int $id;
    protected ?string $name;
    protected ?Furigana $furigana;
    protected ?string $postal;
    protected ?Prefecture $prefId;
    protected ?string $address1;
    protected ?string $address2;
    protected ?string $mail;
    protected ?string $tel;
    protected ?string $fax;
    protected ?string $telMobile;
    protected ?Sex $sex;
    protected ?string $birthday;

    /** @param array<string, mixed> $data ゲスト顧客属性、または顧客 ID を含む属性 */
    public function __construct(array $data)
    {
        parent::__construct($data);

        if (isset($this->sex) && ! \in_array($this->sex, [Sex::MALE, Sex::FEMALE], true)) {
            throw InvalidFieldException::for(
                static::class,
                'sex',
                'male|female',
                $this->sex,
            );
        }
    }

    /** 登録済み顧客の受注を作成する入力を返す。 */
    public static function existing(int $customerId): self
    {
        return new self(['id' => $customerId]);
    }
}
