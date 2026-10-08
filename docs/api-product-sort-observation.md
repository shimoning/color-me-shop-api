# 商品一覧の sort の実測記録

## この文書の位置づけと収集条件

この文書は、商品一覧（`GET /v1/products`）の検索条件 `sort` に、公式 OpenAPI の例と、それ以外の値を送ったときの
実 API の挙動を記録する。`sort` を値オブジェクトで扱う判断のための記録で、現在のライブラリ仕様ではない。実測の
出典はこの文書を追加・更新した各コミットであり、[ADR 0000](adr/0000-record-architecture-decisions.md) の収集条件・
検証可能性の規則に従う。

収集日は **2026-10-08（Asia/Tokyo）**。対象はテスト用ショップの商品 10 件。本ライブラリの `Communicator\Request` で
`limit=50` を付けた GET だけを送り、応答の HTTP ステータスと、商品の並び順を記録した。並び順は、価格の値と、商品 ID の
並びが別の応答と一致するかどうかで判断し、商品 ID と商品名は記録しない。

## 公式 OpenAPI の記述

`GET /v1/products` の `sort` は `type: string`、既定値は `-make_date`。カラム名の前に `-` を付けると降順、付けなければ
昇順で、カンマ区切りで複数指定できる。指定可能なカラムは `make_date`（作成日）、`update_date`（更新日）、`sales_price`
（販売価格）、`price`（定価）、`members_price`（会員価格）の 5 つとされている。

## 観測

すべて HTTP 200 で、エラーにならなかった。

### 仕様にあるカラム

| `sort` | 返った商品の値の並び |
| --- | --- |
| `sales_price` | `sales_price` が 144, 200, 555, 555, 1234, 9990, null, null, null, null |
| `-sales_price` | `sales_price` が 9990, 1234, 555, 555, 200, 144, null, null, null, null |
| `price` | `price` が 10000, null が 9 件 |
| `members_price` | `members_price` が 144, 200, 555, 555, 1234, 9990, null, null, null, null |
| `-update_date` | `update_date` の降順 |
| `-sales_price,make_date` | `sales_price` の降順（null を除く） |
| `make_date` | 指定なしの場合の商品の並びの逆順 |

**仕様にあるカラムは、`-` の有無で昇順・降順に並んだ。** 値が `null` の商品は、昇順でも降順でも最後に並んだ。

### 仕様にない値

次の値は、いずれも `sort` を指定しない場合（`-make_date`）と同じ商品の並びを返した。

- 仕様にないカラム名: `name`、`-name`、`id`
- 存在しないカラム名: `foo`
- 空文字、`-` だけ、`--sales_price`

**仕様にない値はエラーにならず、既定の並びとして扱われた。**

### カンマの後の空白

`-sales_price, make_date`（カンマの後に空白）は、`-sales_price,make_date` と異なる商品の並びを返した。

**カンマの後に空白を入れると、空白なしと同じ並びにならなかった。** 空白を含むキーがどう扱われたかは確かめていない。

### 追加観測: 商品が持つキーとでたらめな値

同日に、続けて次を確かめた。並び順は、同じ条件で `sort` を指定しない応答と商品 ID の並びが一致するか（「既定と
同じ」）と、指定したキーの値で並んでいるか（値が `null` の商品は最後に並ぶものとして判定）で判断した。

**商品が持つキー。** `sort` を指定しない応答の商品が持つ 49 個のキーを、それぞれ昇順（`key`）と降順（`-key`）で
送った。既定と違う並びになったのは、公式 OpenAPI が挙げる 5 つのカラムだけだった（`-make_date` は既定と同じ）。
残りの 44 個（`id`、`name`、`stocks`、`weight`、`model_number` など）は、昇順・降順とも既定と同じ並びだった。
`price` は値を持つ商品が 1 件だけで、そのキーで並んでいるかは判定できなかった。

**でたらめな値。** 次の値は、いずれも HTTP 200 で、既定と同じ並びだった。

- 大文字・小文字を変えたもの: `SALES_PRICE`、`Sales_Price`、`-SALES_PRICE`
- 区切りや記号を変えたもの: `sales-price`、`+sales_price`、`sales_price asc`、`sales_price:desc`
- 前後に空白を付けたもの: `' sales_price'`、`'sales_price '`
- カラム名でないもの: `asc`、`desc`、`123`、`-123`、`*`、`%`、`!@#$`、`a` を 300 個並べた文字列

**仕様にあるカラムとの組み合わせ。**

| `sort` | 結果 |
| --- | --- |
| `foo,-sales_price`、`-sales_price,foo`、`name,-sales_price`、`-sales_price,name`、`foo,bar,-sales_price` | `sales_price` の降順 |
| `,sales_price`、`sales_price,` | `sales_price` の昇順 |
| `sales_price,sales_price` | `sales_price` の昇順 |
| `-sales_price,-sales_price` | `sales_price` の降順 |

**どの値もエラーにならなかった。仕様にない名前、大文字・小文字の違い、前後の空白を含む名前、空のキーは、並び順に
使われなかった。仕様にあるカラムは、ほかに無効なキーがあっても、位置に関係なく使われた。**

前節の「カンマの後の空白」で並びが変わったのは、2 つ目のキー `' make_date'` が使われなかったためと考えられるが、
この組み合わせを直接は確かめていない。

## 確かめていないこと

- 商品の件数が少なく（10 件）、同じ値の商品の並びの規則は確かめていない
- 複数指定の 2 つ目以降のキーが、同じ値の商品の並びに効いているか
- 観測は 1 ショップ、各条件について 1 回である

## 関連

- [検索条件のクエリ形式の実測記録](api-search-query-format-observation.md)
