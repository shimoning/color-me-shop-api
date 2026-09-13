<?php

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

if (\PHP_VERSION_ID >= 80400) {
    // ライブラリの最低要件である PHP 8.1 でもテスト群を読み込めるよう、
    // PHP 8.4 で追加された property hooks の構文だけを条件付きで評価する。
    eval(<<<'PHP'
        namespace Shimoning\ColorMeShopApi\Tests\Doubles;

        class VirtualFieldEntity extends \Shimoning\ColorMeShopApi\Entities\Entity
        {
            private static array $values = [
                'public' => null,
                'protected' => null,
                'private' => null,
            ];

            private static array $setCalls = [
                'public' => 0,
                'protected' => 0,
                'private' => 0,
            ];

            public ?string $publicVirtual {
                get => self::$values['public'];
                set (?string $value) {
                    self::$setCalls['public']++;
                    self::$values['public'] = $value;
                }
            }

            protected ?string $protectedVirtual {
                get => self::$values['protected'];
                set (?string $value) {
                    self::$setCalls['protected']++;
                    self::$values['protected'] = $value;
                }
            }

            private ?string $privateVirtual {
                get => self::$values['private'];
                set (?string $value) {
                    self::$setCalls['private']++;
                    self::$values['private'] = $value;
                }
            }

            public static function resetVirtualState(): void
            {
                self::$values = [
                    'public' => null,
                    'protected' => null,
                    'private' => null,
                ];
                self::$setCalls = [
                    'public' => 0,
                    'protected' => 0,
                    'private' => 0,
                ];
            }

            public static function getVirtualState(): array
            {
                return [
                    'values' => self::$values,
                    'set_calls' => self::$setCalls,
                ];
            }

            public function assertPublicVirtualInitialized(): void
            {
                $this->assertFieldInitialized('publicVirtual');
            }
        }
        PHP);
}
