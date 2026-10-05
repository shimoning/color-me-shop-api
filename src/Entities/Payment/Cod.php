<?php

namespace Shimoning\ColorMeShopApi\Entities\Payment;

use Shimoning\ColorMeShopApi\Entities\Entity;
use Shimoning\ColorMeShopApi\Exceptions\InvalidFieldException;

/**
 * 決済設定
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/payment/operation/getPayments
 */
class Cod extends Entity
{
    /**
     * 手数料が決済金額によって変わるか否か
     *
     * 2026-09-16 の実測では、false は一律手数料、true は区分手数料が
     * 管理画面で選択されていた。実決済時の計算結果は未観測。
     */
    protected bool $changeable;

    /**
     * 手数料が変わる決済金額の区分
     *
     * @var list<CodFee>|null
     */
    protected ?array $fees;

    /**
     * 最後の区分がある場合、その排他的上限以上に設定された手数料
     *
     * @var int|null
     */
    protected ?int $feeMax;

    /**
     * 手数料計算に用いる金額の種類
     *
     * true の場合は決済総額、false の場合は商品合計額で計算する。
     *
     * @var bool|null
     */
    protected ?bool $changeableByTotal;

    /**
     * API の手数料タプルを意味付き Entity に変換する。
     *
     * @param array<string, mixed> $data API レスポンスデータ
     */
    public function __construct(array $data)
    {
        if (
            \array_key_exists('fees', $data)
            && $data['fees'] !== null
            && ! \is_array($data['fees'])
        ) {
            throw InvalidFieldException::for(static::class, 'fees', 'array', $data['fees']);
        }

        parent::__construct($data);

        if (! \array_key_exists('fees', $data) || $data['fees'] === null) {
            return;
        }

        $this->fees = [];
        foreach ($data['fees'] as $index => $tuple) {
            try {
                if (
                    ! \is_array($tuple)
                    || ! \array_is_list($tuple)
                    || \count($tuple) !== 2
                    || ! \is_int($tuple[0])
                    || ! \is_int($tuple[1])
                ) {
                    throw new \UnexpectedValueException('代引き手数料区分は2整数のタプルである必要があります。');
                }

                $this->fees[] = new CodFee([
                    'upper_limit' => $tuple[0],
                    'fee' => $tuple[1],
                ]);
            } catch (\Throwable $error) {
                throw InvalidFieldException::forArrayElement(
                    static::class,
                    \sprintf('fees[%s]', $index),
                    CodFee::class,
                    $error,
                );
            }
        }
    }

    /**
     * 手数料が決済金額によって変わるか否か
     *
     * 2026-09-16 の実測では、false は一律手数料、true は区分手数料が
     * 管理画面で選択されていた。実決済時の計算結果は未観測。
     *
     * @return bool
     */
    public function getChangeable(): bool
    {
        $this->assertFieldInitialized('changeable');

        return $this->changeable;
    }

    /**
     * 手数料が変わる決済金額の区分
     *
     * 各区分の upperLimit は設定上の排他的上限。欠損と明示 null は null、空配列は空リストとして返す。
     *
     * 公式 OpenAPI との差分: upperLimit の「以下」という説明は誤りで、値そのものは区分に含まれない
     * (2026-09-16)。
     *
     * @return list<CodFee>|null
     * @see docs/api-payment-structure.md
     */
    public function getFees(): ?array
    {
        return $this->fees;
    }

    /**
     * 最後の区分がある場合、その upperLimit 以上に設定された手数料。
     *
     * 公式 OpenAPI との差分: nullable ではないが、実 API では null またはキー欠損になる (2026-09-16)。
     *
     * @return int|null
     * @see docs/api-payment-structure.md
     */
    public function getFeeMax(): ?int
    {
        return $this->feeMax;
    }

    /**
     * 手数料計算に用いる金額の種類
     *
     * true の場合は決済総額、false の場合は商品合計額で計算する。
     *
     * 公式 OpenAPI との差分: 非 nullable だが、固定手数料ではキー欠損になるため null を返す (2026-09-16)。
     *
     * @return bool|null
     * @see docs/api-payment-structure.md
     */
    public function getChangeableByTotal(): ?bool
    {
        return $this->changeableByTotal;
    }
}
