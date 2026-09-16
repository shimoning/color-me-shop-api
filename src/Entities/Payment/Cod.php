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
     * false の場合は Payment.fee の一律手数料が適用される。true の場合は
     * Payment.fee は使用されず、fees と fee_max の区分手数料が適用される。
     *
     * 2026-09-16 に実 API で確認した各1件のサンプルでは、false の場合は fees、
     * fee_max、changeable_by_total のキーがなく、true の場合は各キーが存在した。
     */
    protected bool $changeable;

    /**
     * 手数料が変わる決済金額の区分
     *
     * 各要素は [排他的上限金額, 手数料] の2要素タプルで、第1要素の金額は
     * 区分に含まれない（「未満」）。たとえば [[300, 100], [500, 70]] は、
     * 0円以上300円未満なら100円、300円以上500円未満なら70円を表す。
     *
     * 公式 OpenAPI の「[3000, 100] なら3000円以下の場合は100円」という説明は
     * 実際の境界と異なる。2026-09-16 に管理画面の「円未満」「上記金額以上」
     * という表記と実データで確認した。
     *
     * @var list<array{int, int}>|null
     */
    protected ?array $fees;

    /**
     * fees の最後の区分の排他的上限金額以上に適用される手数料
     *
     * たとえば fees が [[300, 100], [500, 70]]、fee_max が10なら、500円以上は
     * 10円となる。公式 OpenAPI の「fees に設定されている区分以上」という説明は
     * 実際の境界と異なり、最後の区分の閾値そのものを含む。2026-09-16 に
     * 管理画面の「上記金額以上」という表記と実データで確認した。
     *
     * 公式 OpenAPI スキーマには nullable の指定がないが、2026-09-16 の実 API では
     * null が返り、上限手数料が未設定であることを表していた。この場合、最後の区分の
     * 排他的上限金額以上の手数料は応答だけでは確定できない。
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
     * false の場合は Payment.fee の一律手数料が適用される。true の場合は
     * Payment.fee は使用されず、fees と fee_max の区分手数料が適用される。
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
     * 各要素は [排他的上限金額, 手数料] の2要素タプルで、第1要素の金額は
     * 区分に含まれない（「未満」）。たとえば [[300, 100], [500, 70]] は、
     * 0円以上300円未満なら100円、300円以上500円未満なら70円を表す。
     *
     * 公式 OpenAPI の「[3000, 100] なら3000円以下の場合は100円」という説明は
     * 実際の境界と異なる。2026-09-16 に管理画面の「円未満」「上記金額以上」
     * という表記と実データで確認した。
     *
     * @return list<array{int, int}>|null
     */
    public function getFees(): ?array
    {
        return $this->fees;
    }

    /**
     * fees の最後の区分の排他的上限金額以上に適用される手数料
     *
     * たとえば fees が [[300, 100], [500, 70]]、fee_max が10なら、500円以上は
     * 10円となる。公式 OpenAPI の「fees に設定されている区分以上」という説明は
     * 実際の境界と異なり、最後の区分の閾値そのものを含む。2026-09-16 に
     * 管理画面の「上記金額以上」という表記と実データで確認した。
     *
     * 公式 OpenAPI スキーマには nullable の指定がないが、2026-09-16 の実 API では
     * null が返り、上限手数料が未設定であることを表していた。この場合、最後の区分の
     * 排他的上限金額以上の手数料は応答だけでは確定できない。
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
