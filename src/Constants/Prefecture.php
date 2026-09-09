<?php

namespace Shimoning\ColorMeShopApi\Constants;

/**
 * 都道府県および海外を表す通し番号。
 */
enum Prefecture: int
{
    // 北海道
    case HOKKAIDO  = 1; // 北海道

    // 東北
    case AOMORI    = 2; // 青森県
    case IWATE     = 3; // 岩手県
    case AKITA     = 4; // 秋田県
    case MIYAGI    = 5; // 宮城県
    case YAMAGATA  = 6; // 山形県
    case FUKUSIMA  = 7; // 福島県

    // 関東
    case IBARAKI   = 8; // 茨城県
    case TOCHIGI   = 9; // 栃木県
    case GUNMMA    = 10; // 群馬県
    case SAITAMA   = 11; // 埼玉県
    case CHIBA     = 12; // 千葉県
    case TOKYO     = 13; // 東京都
    case KANAGAWA  = 14; // 神奈川県

    // 中部
    case NIIGATA   = 15; // 新潟県
    case FUKUI     = 16; // 福井県
    case ISHIKAWA  = 17; // 石川県
    case TOYAMA    = 18; // 富山県
    case SHIZUOKA  = 19; // 静岡県
    case YAMANASHI = 20; // 山梨県
    case NAGANO    = 21; // 長野県
    case AICHI     = 22; // 愛知県
    case GIFU      = 23; // 岐阜県

    // 近畿
    case MIE       = 24; // 三重県
    case WAKAYAMA  = 25; // 和歌山県
    case SHIGA     = 26; // 滋賀県
    case NARA      = 27; // 奈良県
    case KYOTO     = 28; // 京都府
    case OSAKA     = 29; // 大阪府
    case HYOGO     = 30; // 兵庫県

    // 中国
    case OKAYAMA   = 31; // 岡山県
    case HIROSHIMA = 32; // 広島県
    case TOTTORI   = 33; // 鳥取県
    case SHIMANE   = 34; // 島根県
    case YAMAGUCHI = 35; // 山口県

    // 四国
    case KAGAWA    = 36; // 香川県
    case TOKUSHIMA = 37; // 徳島県
    case EHIME     = 38; // 愛媛県
    case KOCHI     = 39; // 高知県

    // 九州
    case FUKUOKA   = 40; // 福岡県
    case SAGA      = 41; // 佐賀県
    case NAGASAKI  = 42; // 長崎県
    case OITA      = 43; // 大分県
    case KUMAMOTO  = 44; // 熊本県
    case MIYAZAKI  = 45; // 宮崎県
    case KAGOSHIMA = 46; // 鹿児島県
    case OKINAWA   = 47; // 沖縄県

    case FOREIGN   = 48; // 海外


    /**
     * 都道府県の日本語名を取得する。
     *
     * @return string
     */
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

    /**
     * 海外を表す値かを判定する。
     *
     * @return bool
     */
    public function isForeign(): bool
    {
        return $this === self::FOREIGN;
    }
}
