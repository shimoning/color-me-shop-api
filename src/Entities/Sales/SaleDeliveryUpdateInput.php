<?php

namespace Shimoning\ColorMeShopApi\Entities\Sales;

use Shimoning\ColorMeShopApi\Contracts\RequestEntity;
use Shimoning\ColorMeShopApi\Values\Furigana;
use Shimoning\ColorMeShopApi\Constants\Prefecture;

/**
 * お届け先
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/updateSale
 */
class SaleDeliveryUpdateInput extends SaleDelivery implements RequestEntity
{
    /**
     * 宛名を設定
     * @param string $name
     * @return void
     */
    public function setName(string $name)
    {
        $this->name = $name;
        $this->markRequestField('name');
    }

    /**
     * 宛名のフリガナを設定
     * @param Furigana $furigana
     * @return void
     */
    public function setFurigana(Furigana $furigana)
    {
        $this->furigana = $furigana;
        $this->markRequestField('furigana');
    }

    /**
     * 郵便番号を設定
     * @param string $postal
     * @return void
     */
    public function setPostal(string $postal)
    {
        $this->postal = $postal;
        $this->markRequestField('postal');
    }

    /**
     * 都道府県の通し番号
     * @param Prefecture $prefId
     * @return void
     */
    public function setPrefId(Prefecture $prefId)
    {
        $this->prefId = $prefId;
        $this->markRequestField('prefId');
    }

    /**
     * 都道府県名を設定
     * @param string $prefName
     * @return void
     */
    public function setPrefName(string $prefName)
    {
        $this->prefName = $prefName;
        $this->markRequestField('prefName');
    }

    /**
     * 住所1を設定
     * @param string $address1
     * @return void
     */
    public function setAddress1(string $address1)
    {
        $this->address1 = $address1;
        $this->markRequestField('address1');
    }
    /**
     * 住所2を設定
     * @param string $address2
     * @return void
     */
    public function setAddress2(string $address2)
    {
        $this->address2 = $address2;
        $this->markRequestField('address2');
    }

    /**
     * 電話番号を設定
     * @param string $tel
     * @return void
     */
    public function setTel(string $tel)
    {
        $this->tel = $tel;
        $this->markRequestField('tel');
    }

    /**
     * 配送希望日を設定
     * @param string $preferredDate
     * @return void
     */
    public function setPreferredDate(string $preferredDate)
    {
        $this->preferredDate = $preferredDate;
        $this->markRequestField('preferredDate');
    }

    /**
     * 配送希望時間帯を設定
     * @param string $preferredPeriod
     * @return void
     */
    public function setPreferredPeriod(string $preferredPeriod)
    {
        $this->preferredPeriod = $preferredPeriod;
        $this->markRequestField('preferredPeriod');
    }

    /**
     * 配送伝票番号を設定
     * @param string $slipNumber
     * @return void
     */
    public function setSlipNumber(string $slipNumber)
    {
        $this->slipNumber = $slipNumber;
        $this->markRequestField('slipNumber');
    }

    /**
     * 熨斗の文言の設定
     * @param string $noshiText
     * @return void
     */
    public function setNoshiText(string $noshiText)
    {
        $this->noshiText = $noshiText;
        $this->markRequestField('noshiText');
    }

    /**
     * 熨斗の料金の設定
     * @param integer $noshiCharge
     * @return void
     */
    public function setNoshiCharge(int $noshiCharge)
    {
        $this->noshiCharge = $noshiCharge;
        $this->markRequestField('noshiCharge');
    }

    /**
     * メッセージカードの表示名の設定
     * @param string $cardName
     * @return void
     */
    public function setCardName(string $cardName)
    {
        $this->cardName = $cardName;
        $this->markRequestField('cardName');
    }

    /**
     * メッセージカードのテキストの設定
     * @param string $cardText
     * @return void
     */
    public function setCardText(string $cardText)
    {
        $this->cardText = $cardText;
        $this->markRequestField('cardText');
    }

    /**
     * メッセージカードの料金の設定
     * @param integer $cardCharge
     * @return void
     */
    public function setCardCharge(int $cardCharge)
    {
        $this->cardCharge = $cardCharge;
        $this->markRequestField('cardCharge');
    }

    /**
     * ラッピングの表示名の設定
     * @param string $wrappingName
     * @return void
     */
    public function setWrappingName(string $wrappingName)
    {
        $this->wrappingName = $wrappingName;
        $this->markRequestField('wrappingName');
    }

    /**
     * ラッピングの料金の設定
     * @param integer $wrappingCharge
     * @return void
     */
    public function setWrappingCharge(int $wrappingCharge)
    {
        $this->wrappingCharge = $wrappingCharge;
        $this->markRequestField('wrappingCharge');
    }

    /**
     * 配送料の設定
     * @param integer $deliveryCharge
     * @return void
     */
    public function setDeliveryCharge(int $deliveryCharge)
    {
        $this->deliveryCharge = $deliveryCharge;
        $this->markRequestField('deliveryCharge');
    }

    /**
     * 配送料・手数料の小計を設定
     * @param int $totalCharge
     * @return void
     */
    public function setTotalCharge(int $totalCharge)
    {
        $this->totalCharge = $totalCharge;
        $this->markRequestField('totalCharge');
    }

    /**
     * 配送状況確認URLの設定
     * @param string $trackingUrl
     * @return void
     */
    public function setTrackingUrl(string $trackingUrl)
    {
        $this->trackingUrl = $trackingUrl;
        $this->markRequestField('trackingUrl');
    }

    /**
     * 備考の設定
     * @param string $memo
     * @return void
     */
    public function setMemo(string $memo) {
        $this->memo = $memo;
        $this->markRequestField('memo');
    }

    /**
     * 発送済みであるか否かの設定
     * @param bool $delivered
     * @return void
     */
    public function setDelivered(bool $delivered)
    {
        $this->delivered = $delivered;
        $this->markRequestField('delivered');
    }
}
