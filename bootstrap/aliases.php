<?php

declare(strict_types=1);

/**
 * 0.14.0 で改名した要求側入力 Entity の旧クラス名を解決できるようにする。
 *
 * 対応表と解決の仕組みは Shimoning\ColorMeShopApi\Aliases を参照。
 */

require_once __DIR__ . '/../src/Aliases.php';

\Shimoning\ColorMeShopApi\Aliases::register();
