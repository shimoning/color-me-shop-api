<?php

namespace Shimoning\ColorMeShopApi\Entities\Sales;

use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Values\DateTime;
use Shimoning\ColorMeShopApi\Values\Furigana;
use Shimoning\ColorMeShopApi\Values\Limit;
use Shimoning\ColorMeShopApi\Constants\MailState;

class SearchParameters extends Entity
{
    const OBJECT_FIELDS = [
        'makeDateMin'=> [
            'entity' => DateTime::class,
        ],
        'makeDateMax'=> [
            'entity' => DateTime::class,
        ],
        'updateDateMin'=> [
            'entity' => DateTime::class,
        ],
        'updateDateMax'=> [
            'entity' => DateTime::class,
        ],

        'customerFurigana'=> [
            'entity' => Furigana::class,
        ],

        'acceptedMailState'=> [
            'entity' => MailState::class,
        ],
        'paidMailState'=> [
            'entity' => MailState::class,
        ],
        'deliveredMailState'=> [
            'entity' => MailState::class,
        ],
        'limit'=> [
            'entity' => Limit::class,
        ],
    ];

    protected ?array $ids;
    protected ?DateTime $makeDateMin;
    protected ?DateTime $makeDateMax;
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
    protected ?array $fields;   // TODO: SalesFields
    protected ?Limit $limit;
    protected ?int $offset;
}
