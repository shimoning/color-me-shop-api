<?php

namespace Shimoning\ColorMeShopApi\Contracts;

/**
 * 利用者が送信する値を組み立てる Entity の印。
 * FallbackEnum の未知値や番兵値を API リクエストに流さない。
 * OBJECT_FIELDS で構築する未マークの子 Entity にも厳格な検証が伝わる。
 */
interface RequestEntity
{
}
