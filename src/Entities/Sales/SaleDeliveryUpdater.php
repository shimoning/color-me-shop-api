<?php

namespace Shimoning\ColorMeShopApi\Entities\Sales;

use Shimoning\ColorMeShopApi\Values\Furigana;
use Shimoning\ColorMeShopApi\Constants\Prefecture;

/**
 * お届け先
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/updateSale
 */
class SaleDeliveryUpdater extends SaleDelivery
{
    /**
     * 宛名を設定
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * 宛名のフリガナを設定
     * @param Furigana $furigana
     */
    public function setFurigana(Furigana $furigana)
    {
        $this->furigana = $furigana;
    }

    /**
     * 郵便番号を設定
     * @param string $postal
     * @return void
     */
    public function setPostal(string $postal)
    {
        $this->postal = $postal;
    }

    /**
     * 都道府県の通し番号
     * @param Prefecture $prefId
     */
    public function setPrefId(Prefecture $prefId)
    {
        $this->prefId = $prefId;
    }

    /**
     * 都道府県名を設定
     * @param string $prefName
     */
    public function setPrefName(string $prefName)
    {
        $this->prefName = $prefName;
    }

    /**
     * 住所1を設定
     * @param string $address1
     */
    public function setAddress1(string $address1)
    {
        $this->address1 = $address1;
    }
    /**
     * 住所2を設定
     * @param string $address2
     */
    public function setAddress2(string $address2)
    {
        $this->address2 = $address2;
    }

    /**
     * 電話番号を設定
     * @param string $tel
     */
    public function setTel(string $tel)
    {
        $this->tel = $tel;
    }

    /**
     * 配送希望日を設定
     * @param string $preferredDate
     */
    public function setPreferredDate(string $preferredDate)
    {
        $this->preferredDate = $preferredDate;
    }

    /**
     * 配送希望時間帯を設定
     * @param string $preferredPeriod
     */
    public function setPreferredPeriod(string $preferredPeriod)
    {
        $this->preferredPeriod = $preferredPeriod;
    }

    /**
     * 配送伝票番号を設定
     * @param string $slipNumber
     */
    public function setSlipNumber(string $slipNumber)
    {
        $this->slipNumber = $slipNumber;
    }

    /**
     * 熨斗の文言の設定
     * @param string $noshiText
     */
    public function setNoshiText(string $noshiText)
    {
        $this->noshiText = $noshiText;
    }

    /**
     * 熨斗の料金の設定
     * @param integer $noshiCharge
     */
    public function setNoshiCharge(int $noshiCharge)
    {
        $this->noshiCharge = $noshiCharge;
    }

    /**
     * メッセージカードの表示名の設定
     * @param string $cardName
     */
    public function setCardName(string $cardName)
    {
        $this->cardName = $cardName;
    }

    /**
     * メッセージカードのテキストの設定
     * @param string $cardText
     */
    public function setCardText(string $cardText)
    {
        $this->cardText = $cardText;
    }

    /**
     * メッセージカードの料金の設定
     * @param integer $cardCharge
     */
    public function setCardCharge(int $cardCharge)
    {
        $this->cardCharge = $cardCharge;
    }

    /**
     * ラッピングの表示名の設定
     * @param string $wrappingName
     */
    public function setWrappingName(string $wrappingName)
    {
        $this->wrappingName = $wrappingName;
    }

    /**
     * ラッピングの料金の設定
     * @param integer $wrappingCharge
     */
    public function setWrappingCharge(int $wrappingCharge)
    {
        $this->wrappingCharge = $wrappingCharge;
    }

    /**
     * 配送料の設定
     * @param integer $deliveryCharge
     */
    public function setDeliveryCharge(int $deliveryCharge)
    {
        $this->deliveryCharge = $deliveryCharge;
    }

    /**
     * 配送料・手数料の小計
     * @return int
     */
    public function setTotalCharge(int $totalCharge)
    {
        $this->totalCharge = $totalCharge;
    }

    /**
     * 配送状況確認URLの設定
     * @param string $trackingUrl
     */
    public function setTrackingUrl(string $trackingUrl)
    {
        $this->trackingUrl = $trackingUrl;
    }

    /**
     * 備考の設定
     * @param string $memo
     */
    public function setMemo(string $memo) {
        $this->memo = $memo;
    }

    /**
     * 発送済みであるか否かの設定
     * @param bool $delivered
     */
    public function setDelivered(bool $delivered)
    {
        $this->delivered = $delivered;
    }
}
