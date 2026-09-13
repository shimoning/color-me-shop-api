<?php

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

if (\PHP_VERSION_ID >= 80400) {
    eval(<<<'PHP'
        namespace Shimoning\ColorMeShopApi\Tests\Doubles;

        class VirtualShadowingPrivateFieldEntity extends ShadowedPrivateFieldParentEntity
        {
            private static int $getCalls = 0;
            private static int $setCalls = 0;

            public string $name {
                get {
                    self::$getCalls++;
                    throw new \LogicException('virtual getter must not run');
                }
                set (string $value) {
                    self::$setCalls++;
                    throw new \LogicException('virtual setter must not run');
                }
            }

            public static function hookCalls(): array
            {
                return ['get' => self::$getCalls, 'set' => self::$setCalls];
            }

            public static function resetHookCalls(): void
            {
                self::$getCalls = 0;
                self::$setCalls = 0;
            }
        }
        PHP);
}
