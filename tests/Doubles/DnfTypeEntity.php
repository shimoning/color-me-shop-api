<?php

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

if (\PHP_VERSION_ID >= 80200) {
    // ライブラリの最低要件である PHP 8.1 でもテスト群を読み込めるよう、
    // PHP 8.2 で追加された DNF 型の構文だけを条件付きで評価する。
    eval(<<<'PHP'
        namespace Shimoning\ColorMeShopApi\Tests\Doubles;

        class DnfTypeEntity extends \Shimoning\ColorMeShopApi\Entities\Entity
        {
            protected (\Shimoning\ColorMeShopApi\Tests\Doubles\DnfLeft&\Shimoning\ColorMeShopApi\Tests\Doubles\DnfRight)|\stdClass $subject;

            public function getSubject(): (\Shimoning\ColorMeShopApi\Tests\Doubles\DnfLeft&\Shimoning\ColorMeShopApi\Tests\Doubles\DnfRight)|\stdClass
            {
                return $this->subject;
            }
        }
        PHP);
}
