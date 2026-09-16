<?php

namespace Shimoning\ColorMeShopApi\Entities\Payment;

use Shimoning\ColorMeShopApi\Entities\Entity;

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
     * 2026-09-16 に実 API で確認した各1件のサンプルでは、false の場合は fees、
     * fee_max、changeable_by_total のキーがなく、true の場合は各キーが存在した。
     */
    protected bool $changeable;

    /**
     * 手数料が変わる決済金額の区分
     *
     * 各要素は [上限金額, 手数料] の2要素タプル。公式の説明では、
     * [3000, 100] は3000円以下の場合に手数料が100円であることを表す。
     *
     * @var list<array{int, int}>|null
     */
    protected ?array $fees;

    /**
     * fees に設定されている区分以上の金額の場合の手数料
     *
     * 公式 OpenAPI スキーマには nullable の指定がないが、2026-09-16 の実 API では
     * null が返り、上限手数料が未設定であることを表していた。この場合、最大区分を
     * 超えた金額の手数料は応答だけでは確定できない。
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
     * 手数料が決済金額によって変わるか否か
     *
     * 2026-09-16 に実 API で確認した各1件のサンプルでは、false の場合は fees、
     * fee_max、changeable_by_total のキーがなく、true の場合は各キーが存在した。
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
     * 各要素は [上限金額, 手数料] の2要素タプル。公式の説明では、
     * [3000, 100] は3000円以下の場合に手数料が100円であることを表す。
     *
     * @return list<array{int, int}>|null
     */
    public function getFees(): ?array
    {
        return $this->fees;
    }

    /**
     * fees に設定されている区分以上の金額の場合の手数料
     *
     * 公式 OpenAPI スキーマには nullable の指定がないが、2026-09-16 の実 API では
     * null が返り、上限手数料が未設定であることを表していた。この場合、最大区分を
     * 超えた金額の手数料は応答だけでは確定できない。
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
