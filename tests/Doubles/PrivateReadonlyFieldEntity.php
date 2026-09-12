<?php

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

// Entity がリフレクションで初期化する readonly プロパティは PHPStan から追跡できないため、
// 実行時契約を検証するクラスだけを動的に定義する。
eval(<<<'PHP'
    namespace Shimoning\ColorMeShopApi\Tests\Doubles;

    class PrivateReadonlyFieldEntity extends \Shimoning\ColorMeShopApi\Entities\Entity
    {
        private readonly ?string $name;

        public function getName(): ?string
        {
            return $this->name;
        }
    }
    PHP);
