<?php

namespace Shimoning\ColorMeShopApi\Entities\Sales;

use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Values\DateTime;
use Shimoning\ColorMeShopApi\Values\Furigana;
use Shimoning\ColorMeShopApi\Values\Limit;
use Shimoning\ColorMeShopApi\Constants\MailState;

/**
 * before/after は直感的でないためサポートしない。
 * make_date_max/make_date_min を使用すること。
 *
 * TODO: fields のサポート
 */
class SearchParameters extends Entity
{
    const OBJECT_FIELDS = [
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
            'value' => Limit::class,
        ],
    ];

    protected ?array $ids;
    protected ?DateTime $makeDateMin;   // after
    protected ?DateTime $makeDateMax;   // before
    protected ?DateTime $updateDateMin;
    protected ?DateTime $updateDateMax;
    protected ?array $customerIds;
    protected ?string $customerName;
    protected ?Furigana $customerFurigana;
    protected ?MailState $acceptedMailState;
    protected ?MailState $paidMailState;
    protected ?MailState $deliveredMailState;
    protected ?bool $mobile;
    protected ?bool $paid;
    protected ?bool $delivered;
    protected ?bool $canceled;
    protected ?array $paymentIds;
    protected ?array $fields;   // TODO: SaleFields
    protected ?Limit $limit;
    protected ?int $offset;
}
