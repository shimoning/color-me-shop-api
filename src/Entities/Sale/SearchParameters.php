<?php

namespace Shimoning\ColorMeShopApi\Entities\Sale;

use Shimoning\ColorMeShopApi\Aliases;

use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Entities\RequestEntity;
use Shimoning\ColorMeShopApi\Values\DateTime;
use Shimoning\ColorMeShopApi\Values\Furigana;
use Shimoning\ColorMeShopApi\Values\Limit;
use Shimoning\ColorMeShopApi\Values\Sale\Limit as SaleLimit;
use Shimoning\ColorMeShopApi\Constants\MailState;

/**
 * 受注一覧 (GET /v1/sales) の検索条件。
 *
 * ids / customer_ids / payment_ids は整数の配列、fields は文字列の配列で指定し、
 * クエリではカンマ区切りで送る。
 * before / after は直感的でないため扱わない。make_date_min / make_date_max を使うこと。
 *
 * @link https://api.shop-pro.jp/v1/spec/open_api.json
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
        'ids' => ['array' => true, 'scalar' => 'int', 'delimiter' => ','],
        'customerIds' => ['array' => true, 'scalar' => 'int', 'delimiter' => ','],
        'paymentIds' => ['array' => true, 'scalar' => 'int', 'delimiter' => ','],
        'fields' => ['array' => true, 'scalar' => 'string', 'delimiter' => ','],
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

}

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(SearchParameters::class);
