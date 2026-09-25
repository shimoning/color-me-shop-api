<?php

declare(strict_types=1);

namespace Shimoning\ColorMeShopApi\Tests\Entities\Customer;

use Shimoning\ColorMeShopApi\Entities\Customer\Points;
use Shimoning\ColorMeShopApi\Exceptions\MissingFieldException;
use Shimoning\ColorMeShopApi\Tests\TestCase;

class PointsTest extends TestCase
{
    public function test_ラップされないトップレベルの応答から組み立てる(): void
    {
        $points = new Points(['customer_id' => 501, 'points' => 216]);

        $this->assertSame(501, $points->getCustomerId());
        $this->assertSame(216, $points->getPoints());
    }

    public function test_応答に欠けたフィールドは固有例外を投げる(): void
    {
        $points = new Points([]);

        $this->expectException(MissingFieldException::class);
        $this->expectExceptionMessage('points');

        $points->getPoints();
    }

    public function test_直列化はAPIのフィールド名に戻す(): void
    {
        $points = new Points(['customer_id' => 501, 'points' => 0]);

        $this->assertSame(['customer_id' => 501, 'points' => 0], $points->toArrayRecursive());
    }
}
