<?php

namespace Shimoning\ColorMeShopApi\Entities\Product\Category;

use Shimoning\ColorMeShopApi\Aliases;


/**
 * 小カテゴリー。実測レスポンスに children キーはない。
 */
class SmallCategory extends Category
{
}

// 0.24.0 の後方互換措置として、旧名での instanceof と型宣言を成立させるための副作用。
// 次のメジャーバージョンで Aliases::MAP とともに削除する。
Aliases::defineLegacyAlias(SmallCategory::class);
