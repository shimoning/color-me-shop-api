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
        parent::__construct($data);

        if (! \array_key_exists('fees', $data) || $data['fees'] === null) {
            return;
        }

        if (! \is_array($data['fees']) || ! \array_is_list($data['fees'])) {
            throw InvalidFieldException::for(static::class, 'fees', 'list', $data['fees']);
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
                    \sprintf('fees[%d]', $index),
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
     * 各区分の upperLimit は設定上の排他的上限（未満）。公式 OpenAPI の
     * 「3000円以下」は設定境界の説明として誤っている。実決済時の計算は未観測。
     * 欠損と明示 null は null、空配列は空リストとして返す。
     * 出典: docs/api-payment-structure.md。
     *
     * @return CodFee[]|null
     */
    public function getFees(): ?array
    {
        return $this->fees;
    }

    /**
     * 最後の区分がある場合、その upperLimit 以上に設定された手数料。
     * 公式 OpenAPI は nullable と
     * していないが、実 API では未設定時に null、固定手数料時にキー欠損を確認した。
     * null の場合の最終区分と実決済時の計算結果は未観測。
     * 出典: docs/api-payment-structure.md。
     *
     * @return int|null
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
     * @return bool|null
     */
    public function getChangeableByTotal(): ?bool
    {
        return $this->changeableByTotal;
    }
}
