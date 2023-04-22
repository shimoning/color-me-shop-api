<?php

namespace Shimoning\ColorMeShopApi\Entities\Customer;

use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Values\Furigana;
use Shimoning\ColorMeShopApi\Values\DateTime;
use Shimoning\ColorMeShopApi\Values\Limit;
use Shimoning\ColorMeShopApi\Constants\Sex;

/**
 * before/after は直感的でないためサポートしない。
 * make_date_max/make_date_min を使用すること。
 *
 * TODO: fields のサポート
 */
class SearchParameters extends Entity
{
    const OBJECT_FIELDS = [
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
            'value' => Limit::class,
        ],
    ];

    protected ?array $ids;

    protected ?string $name;
    protected ?Furigana $furigana;

    protected ?string $mail;
    protected ?string $postal;
    protected ?string $tel;
    protected ?Sex $sex;
    protected ?bool $member;

    protected ?DateTime $makeDateMin;   // after
    protected ?DateTime $makeDateMax;   // before
    protected ?DateTime $updateDateMin;
    protected ?DateTime $updateDateMax;

    protected ?Limit $limit;
    protected ?int $offset;
}
