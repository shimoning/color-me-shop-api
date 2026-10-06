<?php

namespace Shimoning\ColorMeShopApi\Entities\Sales;

use Shimoning\ColorMeShopApi\Aliases;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * 商品のカスタマイズ情報
 *
 * @link https://developer.shop-pro.jp/docs/colorme-api#tag/sale/operation/getSale
 */
class Customization extends Entity
{
    protected string $title;
    protected string $value;

    /**
     * カスタマイズ項目名
     * @return string
     */
    public function getTitle(): string
    {
        $this->assertFieldInitialized('title');
        return $this->title;
    }

    /**
     * カスタマイズ値
     * @return string
     */
    public function getValue(): string
    {
        $this->assertFieldInitialized('value');
        return $this->value;
    }
}

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーで削除予定。
Aliases::defineLegacyAlias(Customization::class);
