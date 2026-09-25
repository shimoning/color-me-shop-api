# ADR 0016: 要求側入力 Entity の命名を統一し、旧名を非推奨の別名で残す

- 状態: 採用
- 決定日: 2026-09-25

## 文脈

要求側 Entity の命名が3つの体系に分かれていた。出典: `bbff143`。

- JSON ボディの入力: `Product\ProductInput`、`GroupInput`、`CategoryInput`、`CategoryChildInput`、
  `PickupInput`、`MetaTagInput`、`VariantInput`、`OptionInput`、`OptionValueInput`
- 受注の更新入力: `Sales\SaleUpdater`、`Sales\SaleDeliveryUpdater`
- GET クエリの検索条件: `Customer\SearchParameters`、`Product\SearchParameters`、
  `Sales\SearchParameters`、`Product\VariantSearchParameters`、`Product\AdvertisingSearchParameters`

同じ「更新の入力」であるのに `VariantInput` と `SaleUpdater` で接尾辞が異なっていた。また接尾辞
`Input` が「作成専用」「更新専用」「作成と更新で共用」の3種を区別せず、クラス名から対応する操作が
読み取れなかった。[ADR 0014](0014-model-product-write-api.md) が商品の作成と更新で1つの入力 Entity を
共用する判断をしたのは、両 request の `required` 指定が同一（いずれも指定なし）で必須性の差を型で
表せなかったためである。一方、顧客の作成と更新は `required` 指定が異なるため別の型で表せる
（[ADR 0015](0015-model-customer-write-api.md)）。別の型を導入するにあたり、既存の命名では
`CustomerInput` と `CustomerUpdateInput` のような非対称な組になる。

このライブラリはバージョン 0.13.0 であり、公開クラス名の変更は利用者にとって破壊的変更である。
`BackwardCompatibilityTest` で公開シグネチャを固定してきた経緯もある。さらに、`RequestEntity` は
`serialize()` できるため、旧クラス名で直列化されたデータが利用者の手元に存在しうる。実際に
`tests/Fixtures/request_entity_with_setter_before_explicit_fields.base64` は旧名 `Sales\SaleUpdater` で
直列化された固定データである。出典: `01ba9bd`。

## 判断

- JSON ボディの入力 Entity の命名を `<対象><操作>Input` に統一する。作成と更新で共用するものは
  操作名を持たず `<対象>Input` のままとする。子要素の入力も操作名を持たない。
- この規則により、次のとおり改名する。
  - `Product\OptionInput` → `Product\OptionCreateInput`（作成専用）
  - `Product\OptionValueInput` → `Product\OptionValueCreateInput`（作成専用）
  - `Product\VariantInput` → `Product\VariantUpdateInput`（更新専用）
  - `Sales\SaleUpdater` → `Sales\SaleUpdateInput`（更新専用）
  - `Sales\SaleDeliveryUpdater` → `Sales\SaleDeliveryUpdateInput`（更新専用）
- `Product\ProductInput`、`GroupInput`、`CategoryInput`、`CategoryChildInput`、`PickupInput` は
  作成と更新で共用するため据え置く。`MetaTagInput` は子要素のため据え置く。
- 検索条件の `SearchParameters` 系は今回の統一対象に含めない。GET クエリ専用で性質が異なり、
  現行の命名で用途が明確なためである。
- 旧クラス名は `Shimoning\ColorMeShopApi\Aliases` の対応表に載せ、非推奨の別名として残す。
  別名の登録経路は2つ用意し、どちらから先に参照されても旧名が成立するようにする。
  - 旧名が先に参照される経路（旧名での `new`、旧名での `unserialize()`）は、
    `spl_autoload_register()` に登録した遅延 autoloader が解決する。登録は `composer.json` の
    `autoload.files` に置いた `bootstrap/aliases.php` から1度だけ行う。
  - 新名が先に参照される経路は、改名した5クラスの定義直後に `Aliases::defineLegacyAlias()` を
    呼んで解決する。
- 新名の経路を別に用意するのは、遅延 autoloader だけでは新クラスから生成したオブジェクトに対する
  旧名の `instanceof` が成立しないためである。`instanceof` は右辺クラスの autoload を起こさないため、
  旧名が一度も autoload されていない状態では `false` になる。旧名を引数型に宣言した関数への
  受け渡しも失敗する。後方互換のための別名がその目的を果たせない。
- 対応表は `Aliases::MAP` を唯一の出典とし、二重定義は `class_exists($legacy, false)` で防ぐ。
  どちらの経路でも対応先クラスを網羅的に読み込むことはない。
- クラス定義ファイルで `class_alias()` を呼ぶことは、ファイルに副作用を持たせないという PSR-1 の
  推奨に反する。後方互換のための一時的な措置であり、次のメジャーな変更で旧名ごと削除するため、
  各呼び出し箇所にその旨をコメントで残す。
- `class_alias()` による真の別名であり、旧名で `unserialize()` された直列化データも復元できる。
  `allowed_classes` には直列化された旧名を渡す必要がある。
- 旧名は次のメジャーな変更で削除する。README に移行表を載せて告知する。

## 代替案と却下理由

- 旧クラスファイルを残して新クラスを継承させる案は、PSR-4 のまま遅延読み込みできる。しかし
  `get_class()` の結果が旧名のままになり、新名で型宣言した箇所は通るものの、旧名で型宣言した箇所へ
  新名のインスタンスを渡せない非対称が残るため採用しない。
- `class_alias()` を `autoload.files` で5つとも即時実行する案は、新旧どちらの向きも確実に成立し、
  クラス定義ファイルに副作用を持たせずに済む。しかし対応先の5クラスを常に eager に読み込むため
  採用しない。旧名を使わない利用者にまで読み込みの費用を負わせることになる。
- 遅延 autoloader だけを残す案は PSR-1 に沿うが、新クラスから生成したオブジェクトに対する旧名の
  `instanceof` と型宣言が成立せず、後方互換措置として機能しないため採用しない。
- 旧名を残さず 0.14.0 で削除する案は、コードベースが最も簡潔になる。しかし直列化データの復元が
  黙って壊れるため採用しない。
- 動詞を先頭に置く `CreateCustomerInput` 形式は Service のメソッド名と語順が揃う。しかし同じ対象の
  クラスがディレクトリ内で離れて並ぶため採用しない。
- 入力専用のサブ名前空間へ分ける案（`Entities\Customer\Input\Create`）はクラス名が短くなる。しかし
  利用側で `use ... as` の別名が必要になる場面が増えるため採用しない。

## 帰結

要求側の JSON 入力 Entity は、クラス名から対応する操作が読み取れるようになる。作成と更新で入力を
分ける Entity を追加しても、命名が非対称にならない。

利用者は旧名のままでも動作する。旧名での生成、旧名での `instanceof` と型宣言、旧名で直列化された
データの復元のいずれも成立する。ただし非推奨であり、次のメジャーな変更で削除される。

改名した5クラスの定義ファイルは、クラス定義以外の副作用を持つ。旧名を削除するまでこの状態が続く。検索条件の命名は統一対象外のため、要求側 Entity 全体を
1つの規則では説明できない状態が残る。

## 関連

- [ADR 0000: アーキテクチャ上の意思決定を記録する](0000-record-architecture-decisions.md)
- [ADR 0014: 商品書き込み API の入力と空レスポンスを表現する](0014-model-product-write-api.md)
- [ADR 0015: 顧客書き込み API の入力を作成と更新で分ける](0015-model-customer-write-api.md)
