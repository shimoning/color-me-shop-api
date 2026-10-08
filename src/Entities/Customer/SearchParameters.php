<?php

namespace Shimoning\ColorMeShopApi\Entities\Customer;

use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Values\Furigana;
use Shimoning\ColorMeShopApi\Values\DateTime;
use Shimoning\ColorMeShopApi\Values\Limit;
use Shimoning\ColorMeShopApi\Values\Customer\Limit as CustomerLimit;
use Shimoning\ColorMeShopApi\Constants\Sex;

/**
 * 顧客一覧 (GET /v1/customers) の検索条件。
 *
 * ids は整数の配列、fields は文字列の配列で指定し、クエリではカンマ区切りで送る。
 * before / after は直感的でないため扱わない。make_date_min / make_date_max を使うこと。
 *
 * 公式 OpenAPI との差分: fields の記載はないが、実 API は受け付ける (2026-10-08)。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
 * @see docs/api-search-query-format-observation.md
 */
class SearchParameters extends Entity implements RequestEntity
{
    const FIELD_TYPES = [
        'furigana' => [
            'value' => Furigana::class,
        ],
        'sex' => [
            'enum' => Sex::class,
        ],

        'makeDateMin' => [
            'value' => DateTime::class,
        ],
        'makeDateMax' => [
            'value' => DateTime::class,
        ],
        'updateDateMin' => [
            'value' => DateTime::class,
        ],
        'updateDateMax' => [
            'value' => DateTime::class,
        ],

        'limit' => [
            'value' => CustomerLimit::class,
        ],
        'ids' => ['array' => true, 'scalar' => 'int', 'delimiter' => ','],
        'fields' => ['array' => true, 'scalar' => 'string', 'delimiter' => ','],
    ];

    /** @var list<int>|null */
    protected ?array $ids;
    /** @var list<string>|null */
    protected ?array $fields;

    protected ?string $name;
    protected ?Furigana $furigana;

    protected ?string $mail;
    protected ?string $postal;
    protected ?string $tel;
    protected ?string $lineUid;
    protected ?string $membershipId;
    protected ?Sex $sex;
    protected ?bool $member;
    protected ?bool $receiveMailMagazine;

    protected ?DateTime $makeDateMin;   // after
    protected ?DateTime $makeDateMax;   // before
    protected ?DateTime $updateDateMin;
    protected ?DateTime $updateDateMax;

    protected ?Limit $limit;
    protected ?int $offset;

}
