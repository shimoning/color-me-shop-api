# ColorMe Shop API カテゴリー応答構造の実測記録

## この文書の位置づけ

この文書は、ColorMe Shop API の実 API から収集したカテゴリー応答の一次情報を、ライブラリの型設計と将来の判断の根拠として保存するものである。2026-09-12 にテスト用ショップで `GET /v1/categories` を実行し、親カテゴリー2件と、その `children` に含まれる子カテゴリー4件を収集した。

ここに記録した内容は収集時点の実測結果であり、API 側の仕様変更によって古くなる可能性がある。内容を更新するときは、推測や過去の応答の流用ではなく、テスト用ショップで再収集する必要がある。

公開リポジトリへ保存するため、`account_id` は `<account_id>` に置換し、カテゴリー ID と日時は明らかなダミー値へ置換した。カテゴリー名と説明文は「親カテゴリ」「小カテゴリ」のようにテスト用と明らかなダミー名だったため、そのまま掲載する。アクセストークンなどの認証情報、ショップ名、ショップ URL は掲載しない。

## 親子で異なる応答構造

親カテゴリーと子カテゴリーでは、実際の応答に現れたキーが異なっていた。

| | `id_small` | `children` | `meta_tag` | その他 |
| --- | --- | --- | --- | --- |
| 親（大カテゴリー）2件 | 全件 `0` | 全件にあり、いずれも非空（3件、1件） | 2件中1件にあり | 下記の共通10キーは全件にあり |
| 子（小カテゴリー）4件 | 全件1以上（個別の ID はマスク） | **キー自体なし** | 4件全件にあり | 下記の共通10キーは全件にあり |

共通して出現した10キーは、`id_big`、`id_small`、`account_id`、`name`、`image_url`、`expl`、`sort`、`display_state`、`make_date`、`update_date` である。

### 実際に出現したキーの一覧

親2件では `meta_tag` の有無だけが異なり、子4件のキー集合はすべて同じだった。JSON 内で観測した順序で示す。

- 親（`meta_tag` なし、1件）: `id_big`, `id_small`, `account_id`, `name`, `image_url`, `expl`, `sort`, `display_state`, `make_date`, `update_date`, `children`
- 親（`meta_tag` あり、1件）: `id_big`, `id_small`, `account_id`, `name`, `image_url`, `expl`, `sort`, `display_state`, `make_date`, `update_date`, `meta_tag`, `children`
- 子（4件すべて）: `id_big`, `id_small`, `account_id`, `name`, `image_url`, `expl`, `sort`, `display_state`, `make_date`, `update_date`, `meta_tag`

したがって、`meta_tag` の有無は親子の判別には使えない。一方、今回の実測では、親は `id_small === 0` かつ非空の `children` を持ち、子は `id_small >= 1` かつ `children` キー自体を持たなかった。

## 代表的な生レスポンス

収集時のレスポンスを JSON として整形し、Unicode エスケープを日本語へ戻した。識別につながる値は前述の方針でマスクしているが、キーの有無、値の型、`null`、空文字は実際の応答どおりである。

### 親カテゴリー（`children` を含む）

`meta_tag` を持つ親カテゴリー1件と、その `children` に含まれた子カテゴリー1件の例。

```json
{
  "id_big": 999999999,
  "id_small": 0,
  "account_id": "<account_id>",
  "name": "親カテゴリ",
  "image_url": null,
  "expl": null,
  "sort": null,
  "display_state": "showing",
  "make_date": 1700000000,
  "update_date": 1700000100,
  "meta_tag": {
    "title": "",
    "keywords": "",
    "description": ""
  },
  "children": [
    {
      "id_big": 999999999,
      "id_small": 999999998,
      "account_id": "<account_id>",
      "name": "小カテゴリ",
      "image_url": null,
      "expl": "",
      "sort": null,
      "display_state": "showing",
      "make_date": 1700000000,
      "update_date": 1700000100,
      "meta_tag": {
        "title": "",
        "keywords": "",
        "description": ""
      }
    }
  ]
}
```

### 子カテゴリー

上の `children` から子カテゴリー1件だけを抜き出した例。親と異なり、`children` キー自体がない。

```json
{
  "id_big": 999999999,
  "id_small": 999999998,
  "account_id": "<account_id>",
  "name": "小カテゴリ",
  "image_url": null,
  "expl": "",
  "sort": null,
  "display_state": "showing",
  "make_date": 1700000000,
  "update_date": 1700000100,
  "meta_tag": {
    "title": "",
    "keywords": "",
    "description": ""
  }
}
```

## 公式 OpenAPI との対応

[公式 OpenAPI](https://api.shop-pro.jp/v1/spec/open_api.json) を 2026-09-16 に取得して確認した。公式スキーマは、親を `components.schemas.productCategory`、子を `components.schemas.productCategoryChild` として別々に定義している。

ただし、両スキーマに `discriminator` と `required` はない。さらに、`productCategoryChild` の example でも `id_small` は `0` である。したがって、公式 OpenAPI だけでは、レスポンスを親子のどちらとして扱うべきか機械的に判別できない。

`meta_tag` は公式スキーマの親子双方に定義されている。その内部の `title`、`keywords`、`description` は、いずれも `type: string` かつ `nullable: true` である。この点は、`meta_tag` が実データでも親子双方に出現し得ることと整合する。

## ライブラリでの扱い

現状の `Entities\Product\Category` は `children` を非 nullable の `array` として持つ。実 API の子カテゴリーには `children` キーがないため、子カテゴリーで `getChildren()` を呼ぶと `MissingFieldException` になる。

ADR 0009 では、実測値と公式説明に基づいて `id_small === 0` を大カテゴリーの判別条件とし、`Category` を `BigCategory` と `SmallCategory` に分割する方針を採用した。`children` と `getChildren()` は `BigCategory` のみに持たせ、親子に共通し得る `meta_tag` は共通の `Category` に置く。

## 再収集の概要

再収集はテスト用ショップで行い、認証情報やマスク前のレスポンスを公開リポジトリへ保存しない。

1. `read_products` スコープを持つテスト用アクセストークンを使い、`GET https://api.shop-pro.jp/v1/categories` を実行する。
2. HTTP ステータスとレスポンスボディを一時的な非公開領域へ保存し、トップレベルの `categories` が配列であることを確認する。
3. `categories` の各要素について、全キー、`id_small`、`children` キーの有無と要素数、`meta_tag` キーの有無を記録する。
4. 各 `children` 要素についても、全キー、`id_small`、`children` キーの有無、`meta_tag` キーの有無を記録する。キーがない場合と、キーの値が `null` または空配列の場合を区別する。
5. 公開用文書へ転記する前に、`account_id`、カテゴリー ID、アクセストークン、ショップ名、ショップ URL、ショップを特定し得るカテゴリー名・説明文・画像 URL が含まれていないことを再確認する。

API の仕様変更を検知するため、再収集後はこの文書の件数、キー一覧、出現数、代表レスポンス、収集日を新しい実測値で更新する。
