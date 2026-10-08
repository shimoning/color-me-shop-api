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
 * 顧客一覧 GET の検索条件。
 * ids は整数配列で指定し、クエリではカンマ区切りで送る。
 *
 * before/after は直感的でないためサポートしない。
 * make_date_max/make_date_min を使用すること。
 *
 * TODO: fields のサポート
 *
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
        'ids' => ['array' => true, 'scalar' => 'int'],
    ];

    /** @var list<int>|null */
    protected ?array $ids;

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

    /** @return array<string, mixed> */
    public function toArrayRecursive($ignoreNull = true): array
    {
        $parameters = parent::toArrayRecursive($ignoreNull);
        if (isset($parameters['ids']) && is_array($parameters['ids'])) {
            if ($parameters['ids'] === []) {
                unset($parameters['ids']);
            } else {
                $parameters['ids'] = implode(',', $parameters['ids']);
            }
        }
        return $parameters;
    }
}
