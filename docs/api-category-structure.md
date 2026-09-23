# ColorMe Shop API カテゴリー応答構造の実測記録

## この文書の位置づけ

この文書は、ColorMe Shop API の実 API から収集したカテゴリー応答の一次情報を、ライブラリの型設計と将来の判断の根拠として保存するものである。現在のライブラリ仕様を記述するものではない。2026-09-12 にテスト用ショップで `GET /v1/categories` を実行し、親カテゴリーとその `children` に含まれる子カテゴリーを観測した。

ここに記録した内容は収集時点の実測結果であり、API 側の仕様変更によって古くなる可能性がある。公開リポジトリには `account_id`、カテゴリー ID、日時の実値、カテゴリー名・説明文、画像 URL、認証情報、ショップ名、ショップ URL を掲載しない。

## 親子で異なる応答構造

親カテゴリーと子カテゴリーでは、実際の応答に現れたキーが異なっていた。

| 種別 | `id_small` | `children` | `meta_tag` | 共通キー |
| --- | --- | --- | --- | --- |
| 親（大カテゴリー） | `0` | 常に存在し、観測範囲では非空 | 欠損することがある | 下記のキーが常に存在 |
| 子（小カテゴリー） | 正の integer | キー自体が欠損 | 常に存在 | 下記のキーが常に存在 |

共通して出現したキーは `id_big`、`id_small`、`account_id`、`name`、`image_url`、`expl`、`sort`、`display_state`、`make_date`、`update_date` である。親の `meta_tag` は存在する場合と欠損する場合があり、子にも存在したため、`meta_tag` の有無は親子の判別には使えない。

観測範囲では、親は `id_small === 0` かつ非空の `children` を持ち、子は正の `id_small` を持って `children` キー自体を持たなかった。親で空の `children` やキー欠損が起こるか、トップレベルに子カテゴリーが現れるかは未観測である。

## キー別の観測

| キー | 観測型 | 親の観測結果 | 子の観測結果 |
| --- | --- | --- | --- |
| `id_big` | integer | 常に存在 | 常に存在 |
| `id_small` | integer | `0` | 正の値 |
| `account_id` | string | 常に存在 | 常に存在 |
| `name` | string | 常に存在 | 常に存在 |
| `image_url` | null | 常に存在し、`null` を観測 | 常に存在し、`null` を観測 |
| `expl` | null, string | 常に存在し、`null` を観測 | 常に存在し、空文字列を観測 |
| `sort` | null | 常に存在し、`null` を観測 | 常に存在し、`null` を観測 |
| `display_state` | string | 常に存在 | 常に存在 |
| `make_date` | integer | 常に存在 | 常に存在 |
| `update_date` | integer | 常に存在 | 常に存在 |
| `meta_tag` | object | 欠損することがある | 常に存在 |
| `children` | array<object> | 常に存在し、非空を観測 | 欠損 |

観測した `meta_tag` object は `title`、`keywords`、`description` を持ち、各値には空文字列があり得た。識別につながる生レスポンスやダミー置換した個別レコードは掲載しない。

## 公式 OpenAPI との対応

[公式 OpenAPI](https://api.shop-pro.jp/v1/spec/open_api.json) を 2026-09-16 に取得して確認した。公式スキーマは、親を `components.schemas.productCategory`、子を `components.schemas.productCategoryChild` として別々に定義している。

ただし、両スキーマに `discriminator` と `required` はない。さらに `productCategoryChild` の example でも `id_small` は `0` であるため、公式 OpenAPI だけでは親子を機械的に判別できない。

`meta_tag` は公式スキーマの親子双方に定義され、内部の `title`、`keywords`、`description` は `type: string` かつ `nullable: true` である。一方、実 API では親の `meta_tag` キー欠損を観測した。これは OpenAPI がキー自体を nullable としていない点との差異であり、[ADR 0012](adr/0012-allow-nullability-from-api-observations.md) の nullable 化の根拠である。

## 現在のライブラリ実装との関係

現在のライブラリは [ADR 0010](adr/0010-split-category-into-big-and-small.md) に従い、抽象基底クラス `Entities\Product\Category` から `BigCategory` / `SmallCategory` を生成する。`Category::fromArray()` は `id_small === 0` を大カテゴリー、それ以外の integer を小カテゴリーとして扱い、`id_small` の欠損や integer 以外を不正な応答として例外にする。`children` と `getChildren()` は `BigCategory` のみにあり、子要素は `SmallCategory` へ変換する。

`meta_tag` は共通基底に置かれ、`Category::$metaTag` は nullable である。キー欠損と明示的な `null` は `getMetaTag()` で `null` になり、空 object は `MetaTag` として保持する。`MetaTag` 内の3フィールドも nullable である。`BigCategory::$children` は非 nullable の array で、親応答で欠損した場合は `MissingFieldException` になる。`SmallCategory` には `getChildren()` がない。

## 書き込み時の観測

2026-09-21 に本ライブラリのカテゴリー作成・更新メソッドで行った smoke test の詳細は、[商品 API 応答構造の実測記録](api-product-structure.md#2026-09-21-の追加観測グループカテゴリーの書き込み-smoke-test)に記録している。

- 大カテゴリーの `display_state` は `showing`、`hidden`、`members_only` を受理し、`CategoryDisplayState` と一致した。`showing_for_members` と `sale_for_members` は 422 になった。小カテゴリーでは `hidden` だけを送信しており、他の値は未観測である。
- `expl: null` を送っても応答と直後の GET は旧値のままで、明示的な `null` ではクリアされない。空文字列は保存された。
- `meta_tag` の部分更新はマージではなく置換であり、送らなかった `keywords` と `description` は `null` になった。
- 大カテゴリーは作成直後の GET で `meta_tag` キーが欠損した。一度設定すると、子の3キーをすべて `null` に戻しても `meta_tag` キーは残った。
- 空入力 `{"category":{}}` は 422（`code: 422210`）、存在しない ID は 404（`code: 404100`）、`sort: -1` は 422（`code: 422014`、`field: "product_category.order_num"`）だった。

## 再収集の概要

1. `read_products` スコープを持つテスト用アクセストークンで `GET https://api.shop-pro.jp/v1/categories` を実行する。
2. HTTP ステータスとレスポンスボディを一時的な非公開領域へ保存し、トップレベルの `categories` が配列であることを確認する。
3. 各カテゴリーと `children` 要素について、全キー、`id_small`、`children` と `meta_tag` のキーの有無、`null`、空配列を区別して記録する。
4. 公開前に ID、日時の実値、認証情報、ショップ識別情報、カテゴリー固有の名前・説明・画像 URL が含まれていないことを確認する。

API の仕様変更を検知するため、再収集後はキーの有無、型、親子の構造差、OpenAPI との差分、収集日を新しい観測へ更新する。
