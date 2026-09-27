<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Delivery;

use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

/**
 * 配送時間帯の設定。
 */
class DeliveryDateTimes extends Entity
{
    protected ?bool $enabled;
    /** @var list<string> */
    protected array $periods;
    protected ?string $comment;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        if (isset($data['periods']) && \is_array($data['periods'])) {
            foreach ($data['periods'] as $value) {
                if (! \is_string($value)) {
                    throw InvalidFieldException::forArrayElement(
                        self::class,
                        'periods',
                        'string',
                        new \TypeError('配列要素の型が不正です。'),
                    );
                }
            }
        }
        parent::__construct($data);
    }

    /**
     * 配送時間帯選択が有効であるか。
     */
    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    /**
     * 配送時間帯の選択肢。
     *
     * @return list<string>
     */
    public function getPeriods(): array
    {
        $this->assertFieldInitialized('periods');
        return $this->periods;
    }

    /**
     * 配送時間帯に関する注意事項。
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }
}
