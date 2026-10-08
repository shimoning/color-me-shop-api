<?php

namespace Shimoning\ColorMeShopApi\Entities\Sale;

use Shimoning\ColorMeShopApi\Aliases;

use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Values\DateTime;
use Shimoning\ColorMeShopApi\Values\Furigana;
use Shimoning\ColorMeShopApi\Values\Limit;
use Shimoning\ColorMeShopApi\Values\Sale\Limit as SaleLimit;
use Shimoning\ColorMeShopApi\Constants\MailState;

/**
 * 受注一覧 GET の検索条件。
 * ids / customer_ids / payment_ids / fields は配列をカンマ区切りで送る。
 *
 * before/after は直感的でないためサポートしない。make_date_max/make_date_min を使用すること。
 *
 * @see docs/api-search-query-format-observation.md
 */
class SearchParameters extends Entity implements RequestEntity
{
    const FIELD_TYPES = [
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

        'customerFurigana' => [
            'value' => Furigana::class,
        ],

        'acceptedMailState' => [
            'enum' => MailState::class,
        ],
        'paidMailState' => [
            'enum' => MailState::class,
        ],
        'deliveredMailState' => [
            'enum' => MailState::class,
        ],
        'limit' => [
            'value' => SaleLimit::class,
        ],
        'ids' => ['array' => true, 'scalar' => 'int'],
        'customerIds' => ['array' => true, 'scalar' => 'int'],
        'paymentIds' => ['array' => true, 'scalar' => 'int'],
        'fields' => ['array' => true, 'scalar' => 'string'],
    ];

    /** @var list<int>|null */
    protected ?array $ids;
    protected ?DateTime $makeDateMin;   // after
    protected ?DateTime $makeDateMax;   // before
    protected ?DateTime $updateDateMin;
    protected ?DateTime $updateDateMax;
    /** @var list<int>|null */
    protected ?array $customerIds;
    protected ?string $customerName;
    protected ?string $customerMail;
    protected ?Furigana $customerFurigana;
    protected ?MailState $acceptedMailState;
    protected ?MailState $paidMailState;
    protected ?MailState $deliveredMailState;
    protected ?bool $mobile;
    protected ?bool $paid;
    protected ?bool $delivered;
    protected ?bool $canceled;
    /** @var list<int>|null */
    protected ?array $paymentIds;
    /** @var list<string>|null */
    protected ?array $fields;
    protected ?Limit $limit;
    protected ?int $offset;

    /** @return array<string, mixed> */
    public function toArrayRecursive($ignoreNull = true): array
    {
        $parameters = parent::toArrayRecursive($ignoreNull);
        foreach (['ids', 'customer_ids', 'payment_ids', 'fields'] as $field) {
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

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(SearchParameters::class);
