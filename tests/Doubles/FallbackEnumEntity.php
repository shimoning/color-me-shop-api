<?php

namespace Shimoning\ColorMeShopApi\Tests\Doubles;

use Shimoning\ColorMeShopApi\Constants\ExternalAccountProvider;
use Shimoning\ColorMeShopApi\Entities\Entity;

/**
 * フォールバック対応 enum の単体・配列変換を検証するテストダブル。
 */
class FallbackEnumEntity extends Entity
{
    const OBJECT_FIELDS = [
        'provider' => ['enum' => ExternalAccountProvider::class],
        'providers' => ['array' => true, 'enum' => ExternalAccountProvider::class],
    ];

    protected ?ExternalAccountProvider $provider;

    /** @var list<ExternalAccountProvider>|null */
    protected ?array $providers;

    public function getProvider(): ?ExternalAccountProvider
    {
        return $this->provider;
    }

    /** @return list<ExternalAccountProvider>|null */
    public function getProviders(): ?array
    {
        return $this->providers;
    }
}
