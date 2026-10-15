# 顧客とお届け先の `furigana` の実測記録

## この文書の位置づけと収集条件

この文書は、顧客とお届け先の応答で、`furigana` の空文字 `""` と `null` がどう扱われるかを記録する。現在のライブラリ仕様
ではない。実測の出典はこの文書を追加・更新した各コミットであり、[ADR 0000](adr/0000-record-architecture-decisions.md) の
収集条件・検証可能性の規則に従う。

収集日は **2026-10-15（Asia/Tokyo）**。対象はテスト用ショップ。Guzzle で JSON の本文を直接送り、応答の JSON を本ライブラリの
Entity を通さずに調べた。記録したのは、`furigana` の値が空でない文字列、`""`、`null`、キーなしのどれかと、その件数だけで、
顧客・受注の値と ID は記録しない。

更新を確かめた顧客は、名前が「【検証】」で始まる検証用の顧客で、`furigana` に空でない値を持っていた。お届け先は、既存の
最新の受注の 1 件目を使った。どちらも、確かめた後に元の値へ戻し、10 秒後の GET が最初の GET と（`update_date` を除いて）
同じことを確かめた。

## 既存のデータ

| API | 件数 | 空でない値 | `""` | `null` | キーなし |
| --- | ---: | ---: | ---: | ---: | ---: |
| `GET /v1/customers` | 12 | 7 | 1 | 4 | 0 |
| `GET /v1/sales`（`make_date_min=2000-01-01`）の `sale_deliveries` | 7（受注 6 件） | 6 | 1 | 0 | 0 |

一覧で `""` と `null` だった顧客と、`""` だったお届け先は、単体の GET（`GET /v1/customers/{customer_id}`、
`GET /v1/sales/{sale_id}`）でも同じだった。`""` だったお届け先は、下の更新で使った受注とは別の、今日より前に最後に
更新された受注のものだった。

## 更新

空でない値を持つ顧客とお届け先の `furigana` に、次の値を送った。顧客の更新では、必須の `name`、`address1` と、送らないと
空になる `address2` を、今と同じ値で一緒に送った（[更新 API で送らなかった項目の扱いの実測記録](api-partial-update-observation.md)）。
お届け先は、`sale_deliveries` に `id` と `furigana` だけを送った。

| API | 送った `furigana` | HTTP | PUT の応答 | 10 秒後の GET | ほかの項目 |
| --- | --- | ---: | --- | --- | --- |
| `PUT /v1/customers/{customer_id}` | `""` | 200 | `""` | `""` | 変わらなかった |
| `PUT /v1/customers/{customer_id}` | `null` | 200 | `null` | `null` | 変わらなかった |
| `PUT /v1/sales/{sale_id}` の `sale_deliveries` | `""` | 200 | `""` | `""`（一覧の GET も `""`） | 変わらなかった |
| `PUT /v1/sales/{sale_id}` の `sale_deliveries` | `null` | 200 | `null` | `null` | 変わらなかった |

「ほかの項目」は、顧客では顧客の全項目、お届け先ではそのお届け先の全項目を、`furigana` と `update_date` を除いて比べた。

## 結果

**顧客とお届け先のどちらも、`furigana` の `""` と `null` を別の値として保存し、応答でもそのまま返した。** 既存のデータにも
両方があった（お届け先の `null` は、既存のデータにはなく、更新で保存したものだけ）。

## 確かめていないこと

- 受注に含まれる顧客（`sale.customer`）の `furigana` の `""` と `null`。既存の受注 6 件では、空でない値だけだった
- お届け先の作成（受注の作成）で `furigana` を送った場合。受注の作成 API は、テスト用ショップのプランでは使えない
  （[受注作成 API の実測記録](api-sale-create-observation.md)）
