# ADR 0026: 商品の入力を作成用と更新用に分ける

- 状態: 採用
- 決定日: 2026-10-02

## 文脈

[ADR 0014](0014-model-product-write-api.md) は、商品の作成（`POST /v1/products`）と更新（`PUT
/v1/products/{product_id}`）の入力を、1 つの `Entities\Product\ProductInput` で共用すると決めた。理由は
「両者に required 指定がなく、作成専用型と更新専用型の必須性を型で区別できない」ことで、両操作の
プロパティの和集合を表すとした。分ける案は「重複する多数のプロパティと検証規則を二重管理するだけ」と
して却下し、「利用者は操作ごとの有効フィールドを公式 API 契約に従って選ぶ」ことを帰結とした。

しかし両者の違いは**必須性ではなく、項目の有無**である。公式 OpenAPI を突き合わせると次のとおりで、
共通の 13 項目は型・enum・制約・説明文まで一致する。

| | 作成 | 更新 |
| --- | --- | --- |
| `name`、`price`、`category_id_big`、`cost`、`sales_price`、`members_price`、`model_number`、`expl`、`simple_expl`、`smartphone_expl`、`display_state`、`stock_managed`、`tax_reduced` | あり | あり |
| `category_id_small`、`stocks`、`group_ids`、`variants` | **なし** | あり |

`ProductInput` は和集合であるため、作成時に更新専用の 4 項目を指定しても止めずに送っていた。2026-10-02 に
実測した。詳細は[ColorMe Shop API 商品応答構造の実測記録](../api-product-structure.md)の「2026-10-02 の追加観測」にある。
出典: `002dbd2`。

- **作成時に送った更新専用の 4 項目は、いずれも反映されず、エラーにもならなかった**（HTTP 200）
- 共通の項目は反映された

利用者が作成時に在庫数やグループを指定したつもりでも、何も設定されず、それに気づく手段もない。ADR 0014 の
「利用者が有効フィールドを選ぶ」という帰結は、この落とし穴を利用者に渡していた。

顧客（[ADR 0015](0015-model-customer-write-api.md)）と受注（[ADR 0022](0022-take-sale-id-as-update-argument.md)、
[ADR 0024](0024-model-sale-create-api.md)）は、作成と更新で入力クラスをすでに分けている。

## 判断

- **商品の入力を、作成用の `ProductCreateInput`（13 項目）と更新用の `ProductUpdateInput`（17 項目）に
  分ける。** 更新専用の項目を作成の入力の項目から外す。命名は
  [ADR 0016](0016-unify-request-input-entity-names.md) に従う。実測で、作成時に更新専用の項目を送っても
  黙って無視されることを確かめた。出典: `002dbd2`、`af1fc31`。
- `ProductUpdateInput` は、`ProductInput` の実装（`group_ids` / `stocks` / `variants` の個別の検証を含む）を
  そのまま引き継ぐ。`ProductCreateInput` は、共通の 13 項目について同じ型・同じ契約（明示した `null` も
  送る、指定しなかった項目は送らない、`display_state` の扱い）を持つ。出典: `af1fc31`。
- `Services\Product::create()` は `ProductCreateInput`、`update()` は `ProductUpdateInput` を受け取る。
  `Client` の対応メソッドも同じ型にする。出典: `af1fc31`。
- **`ProductInput` は削除し、非推奨の別名などの移行措置は設けない。** 利用者と協議のうえ決めた。出典: `af1fc31`。
- `ProductCreateInput` に更新専用の 4 項目を渡した場合は、他の入力クラスと同じく宣言のないキーとして
  無視し、送らない。出典: `af1fc31`。

ADR 0014 の「商品の作成と更新の入力は `ProductInput` で共用する」という判断は、本 ADR で更新する。
ADR 0014 のその他の判断（`display_state` の扱い、明示した `null` の送信など）はそのまま残る。

## 代替案と却下理由

- **`ProductInput` で共用したまま、作成時に更新専用の 4 項目が指定されていたら送信前に
  `ParameterException` にする案**は、変更が小さく、型を変えずに済む。しかし作成と更新で受け付ける項目が
  違うことを入力の項目として表せず、利用者は例外が出るまで気づけない。顧客と受注が入力を分けていることとも揃わない
  ため、利用者と協議のうえ採用しない。
- **`ProductInput` を非推奨として残し、`create()` / `update()` が新旧両方の型を受け付ける移行期間を設ける案**は、
  既存の呼び出しを壊さない。しかし引数の型が合併型になり、移行措置を消すまで形が揃わない。利用者と
  協議のうえ採用しない。
- **`ProductInput` を `ProductUpdateInput` の非推奨の別名にする案**（ADR 0016 の改名と同じ方式）は、更新の
  呼び出しを壊さない。しかし作成の呼び出しは型が合わず結局壊れ、改名ではなく分割である今回には
  当てはまりにくい。利用者と協議のうえ採用しない。
- **何も変えず、PHPDoc と README の説明だけを直す案**は、変更が最小である。しかし作成時に更新専用の
  項目が黙って無視される落とし穴がそのまま残る。

## 帰結

更新専用の項目は作成の入力の項目に含まれなくなり、指定しても送られない。コンストラクタは配列を受け取るため
書くこと自体は防げないが、宣言のないキーとして無視される。作成した商品の在庫数・グループ・
小カテゴリー・バリエーションの在庫は、作成後に更新で設定する。商品の入力は、顧客と受注と同じく作成と
更新で分かれた形になる。

次の点が変わる。破壊的変更のため、次のマイナーバージョン（0.22.0）で行う。

- `ProductInput` がなくなる。更新は `ProductUpdateInput`、作成は `ProductCreateInput` に書き換える必要がある
- `Services\Product::create()` / `update()` と `Client` の対応メソッドの引数の型が変わる
- 作成で更新専用の 4 項目を指定していた場合、その指定は `ProductCreateInput` では無視される。もともと
  実 API に反映されていなかった指定である

共通の 13 項目は 2 つのクラスに重複して宣言される。ADR 0014 が懸念した二重管理であるが、検証規則の多くは
更新専用の項目にあり、共通部分の検証は型と `FIELD_TYPES` の宣言に収まる。

## 関連

- [ADR 0000: アーキテクチャ上の意思決定を記録する](0000-record-architecture-decisions.md)
- [ADR 0014: 商品書き込み API の入力と空レスポンスを表現する](0014-model-product-write-api.md)（本 ADR により更新）
- [ADR 0015: 顧客書き込み API の入力を作成と更新で分ける](0015-model-customer-write-api.md)
- [ADR 0016: 要求側入力 Entity の命名を統一し、旧名を非推奨の別名で残す](0016-unify-request-input-entity-names.md)
- [ADR 0024: 受注作成 API の入力を表現する](0024-model-sale-create-api.md)
- [ColorMe Shop API 商品応答構造の実測記録](../api-product-structure.md)（出典コミット: `002dbd2`）
- 入力の分割と `ProductInput` の削除の出典コミット: `af1fc31`
- README の出典コミット: `23bb2a4`
