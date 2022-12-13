<?php

namespace Shimoning\ColorMeShopApi\Constants;

enum Prefecture: int
{
    // 北海道
    case HOKKAIDO  = 1;

    // 東北
    case AOMORI    = 2;
    case IWATE     = 3;
    case AKITA     = 4;
    case MIYAGI    = 5;
    case YAMAGATA  = 6;
    case FUKUSIMA  = 7;

    // 関東
    case IBARAKI   = 8;
    case TOCHIGI   = 9;
    case GUNMMA    = 10;
    case SAITAMA   = 11;
    case CHIBA     = 12;
    case TOKYO     = 13;
    case KANAGAWA  = 14;

    // 中部
    case NIIGATA   = 15;
    case FUKUI     = 16;
    case ISHIKAWA  = 17;
    case TOYAMA    = 18;
    case SHIZUOKA  = 19;
    case YAMANASHI = 20;
    case NAGANO    = 21;
    case AICHI     = 22;
    case GIFU      = 23;

    // 近畿
    case MIE       = 24;
    case WAKAYAMA  = 25;
    case SHIGA     = 26;
    case NARA      = 27;
    case KYOTO     = 28;
    case OSAKA     = 29;
    case HYOGO     = 30;

    // 中国
    case OKAYAMA   = 31;
    case HIROSHIMA = 32;
    case TOTTORI   = 33;
    case SHIMANE   = 34;
    case YAMAGUCHI = 35;

    // 四国
    case KAGAWA    = 36;
    case TOKUSHIMA = 37;
    case EHIME     = 38;
    case KOCHI     = 39;

    // 九州
    case FUKUOKA   = 40;
    case SAGA      = 41;
    case NAGASAKI  = 42;
    case OITA      = 43;
    case KUMAMOTO  = 44;
    case MIYAZAKI  = 45;
    case KAGOSHIMA = 46;
    case OKINAWA   = 47;

    case FOREIGN   = 48;


    public function name(): string
    {
        return match ($this) {
            self::HOKKAIDO  => '北海道',

            self::AOMORI    => '青森県',
            self::IWATE     => '岩手県',
            self::AKITA     => '秋田県',
            self::MIYAGI    => '宮城県',
            self::YAMAGATA  => '山形県',
            self::FUKUSIMA  => '福島県',

            self::IBARAKI   => '茨城県',
            self::TOCHIGI   => '栃木県',
            self::GUNMMA    => '群馬県',
            self::SAITAMA   => '埼玉県',
            self::CHIBA     => '千葉県',
            self::TOKYO     => '東京都',
            self::KANAGAWA  => '神奈川県',

            self::NIIGATA   => '新潟県',
            self::FUKUI     => '福井県',
            self::ISHIKAWA  => '石川県',
            self::TOYAMA    => '富山県',
            self::SHIZUOKA  => '静岡県',
            self::YAMANASHI => '山梨県',
            self::NAGANO    => '長野県',
            self::AICHI     => '愛知県',
            self::GIFU      => '岐阜県',

            self::MIE       => '三重県',
            self::WAKAYAMA  => '和歌山県',
            self::SHIGA     => '滋賀県',
            self::NARA      => '奈良県',
            self::KYOTO     => '京都府',
            self::OSAKA     => '大阪府',
            self::HYOGO     => '兵庫県',

            self::OKAYAMA   => '岡山県',
            self::HIROSHIMA => '広島県',
            self::TOTTORI   => '鳥取県',
            self::SHIMANE   => '島根県',
            self::YAMAGUCHI => '山口県',

            self::KAGAWA    => '香川県',
            self::TOKUSHIMA => '徳島県',
            self::EHIME     => '愛媛県',
            self::KOCHI     => '高知県',

            self::FUKUOKA   => '福岡県',
            self::SAGA      => '佐賀県',
            self::NAGASAKI  => '長崎県',
            self::OITA      => '大分県',
            self::KUMAMOTO  => '熊本県',
            self::MIYAZAKI  => '宮崎県',
            self::KAGOSHIMA => '鹿児島県',
            self::OKINAWA   => '沖縄県',

            self::FOREIGN   => '海外',
        };
    }

    public function isForeign(): bool
    {
        return $this === self::FOREIGN;
    }
}
