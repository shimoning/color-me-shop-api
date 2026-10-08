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
 * ids / fields は配列で指定し、クエリではカンマ区切りで送る。
 *
 * before/after は直感的でないためサポートしない。
 * make_date_max/make_date_min を使用すること。
 *
 * 公式 OpenAPI との差分: fields の記載はないが、実 API は受け付ける (2026-10-08)。
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
        'fields' => ['array' => true, 'scalar' => 'string'],
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

    /** @return array<string, mixed> */
    public function toArrayRecursive($ignoreNull = true): array
    {
        $parameters = parent::toArrayRecursive($ignoreNull);
        foreach (['ids', 'fields'] as $field) {
            if (isset($parameters[$field]) && is_array($parameters[$field])) {
                if ($parameters[$field] === []) {
                    unset($parameters[$field]);
                } else {
                    $parameters[$field] = implode(',', $parameters[$field]);
                }
            }
        }
        return $parameters;
    }
}
