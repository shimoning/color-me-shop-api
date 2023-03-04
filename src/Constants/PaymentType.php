<?php

namespace Shimoning\ColorMeShopApi\Constants;

enum PaymentType: int
{
    case COD = 0;  // 商品代引
    case BANK_TRANSFER = 1;  // 銀行振込
    case POSTAL_TRANSFER = 2;   // ゆうちょ振替
    case CREDIT_ZEUS = 3; // クレジット(ZEUS)
    case KURONEKO_PAYMENT = 4; // クロネコ@ペイメント
    case NP_ATOBARAI  = 5; // NP後払い
    case CREDIT_EPSILON = 6; // クレジット(イプシロン)
    case CVS_EPSILON = 7; // コンビニ決済(イプシロン)
    case CREDIT_COLOR_ME = 8; // カラーミークレジット
    case OTHER = 9; // その他決済
    case WEB_MONEY = 10; // ウェブマネー
    case DEBIT_E_BANK = 11; // イーバンクデビット
    case NET_BANKING_EPSILON = 12; // ネット銀行(イプシロン)
    case E_MONEY_EPSILON = 13; // 電子マネー(イプシロン)
    case CVS_PAYGENT = 14; // ATM・コンビニ・ネット銀行決済(ペイジェント)
    case DO_LINK_EPSILON = 15; // Do-Link決済(イプシロン)
    case PAYEASY_EPSILON = 16; // ペイジー(イプシロン)
    case ATOBARAI_DOT_COM = 17; // 後払い.com
    case JAPAN_NET_BANK = 18; // ジャパンネット銀行
    case KURONEKO_WEB_DIRECT = 19; // クロネコwebダイレクト
    case PAYPAL_EPSILON = 20; // PayPal(イプシロン)
    case YAHOO_WALLET_EPSILON = 21; // Yahoo!ウォレット(イプシロン)
    case ALL_POINT = 22; // 全額ポイント利用
    case CARRIER_EPSILON = 23; // スマートフォンキャリア決済（イプシロン）
    case CREDIT_GMO = 24; // GMO PG マルチペイメントクレジットカード
    case NEOBANK_EPSILON = 25; // 住信SBIネット銀行（イプシロン）
    case GMO_ATOBARAI_EPSILON = 26; // GMO後払い（イプシロン）
    case GMO_ATOBARAI_GMO = 27; // GMO後払い（GMOペイメントサービス）
    case NOT_USE = 28; // -
    case PAYEASY_PAYGENT = 29; // ATM（ペイジー）（ペイジェント）
    case CREDIT_PAYGENT = 30; // カード（ペイジェント）
    case CVS_NUMBER_PAYGENT = 31; // コンビニ番号方式（ペイジェント）
    case NET_BANKING_PAYGENT = 32; // インターネットバンキング（ペイジェント）
    case PAYPAL_PAYPAL = 33; // PayPal（ペイパル）
    case CREDIT_SMBC_GMO = 34; // SMBC GMO PAYMENTクレジットカード
    case AMAZON_PAY_EPSILON = 35; // Amazon Pay（イプシロン）
    case RAKTEN_PAY_ONLINE = 36; // 楽天ペイ（オンライン決済）
    case VIRTUAL_BANK_TRANSFER_EPSILON = 37; // 銀行振込（バーチャル口座）（イプシロン）
    case AMAZON_PAY_AMAZON = 38; // Amazon Pay（アマゾンペイ）
    case CREDIT_SG_SYSTEM = 39; // クレジットカード（SGシステム）
    case LINE_PAY_EPSILON = 40; // LINE Pay（イプシロン）
    case PAYPAL_CP_PAYPAL = 41; // PayPal Commerce Platform（ペイパル）
    case PAYPAY_EPSILON = 42; // PayPay（イプシロン）
    case AMAZON_PAY_V2_AMAZON = 43; // Amazon Pay V2（アマゾンペイ）
    case AMAZON_PAY_V2_EPSILON = 44; // Amazon Pay V2（イプシロン）
    case SQUARE_F2F = 45; // Square対面決済
}
