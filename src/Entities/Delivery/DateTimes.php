<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Entities\Delivery;

use Shimoning\ColorMeShopApi\Aliases;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 配送時間帯の設定。
 */
class DateTimes extends Entity
{
    public const FIELD_TYPES = [
        'periods' => ['array' => true, 'scalar' => 'string'],
    ];

    protected ?bool $enabled;
    /** @var list<string> */
    protected array $periods;
    protected ?string $comment;

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

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーで削除予定。
Aliases::defineLegacyAlias(DateTimes::class);
