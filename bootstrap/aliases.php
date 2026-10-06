<?php

declare(strict_types=1);

/**
 * 改名した Entity の旧クラス名を解決できるようにする。
 *
 * 対応表と解決の仕組みは Shimoning\ColorMeShopApi\Aliases を参照。
 */

require_once __DIR__ . '/../src/Aliases.php';

\Shimoning\ColorMeShopApi\Aliases::register();
