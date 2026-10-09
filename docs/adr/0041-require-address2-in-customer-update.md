# ADR 0041: 顧客データの更新で address2 を必須にする

- 状態: 採用
- 決定日: 2026-10-09

## 文脈

[ADR 0015](0015-model-customer-write-api.md) で、顧客データの更新（`PUT /v1/customers/{customer_id}`）は、明示した
フィールドだけを送る部分更新とし、実 API で必須だった `name` と `address1` の明示を `Services\Customer::update()` で
送信前に確かめることにした。2026-09-25 の観測で、`name` と `address1` だけを送ると、省略したほかの項目は保持された。
ただし、このとき確かめた項目に `address2` は含まれていなかった。

2026-10-09 に実 API で改めて確かめたところ、顧客の更新で `address2` を送らないと、`address2` は空になった。`address1` を
同じ値で送っても、違う値で送っても同じだった。`address2` を送ると、送った値になった。`name` と `address1` は今も必須で、
どちらかを欠くと 422 になり、何も更新されなかった。受注のお届け先の更新では、`address1` だけを送っても `address2` は
変わらなかった。公式 OpenAPI の更新 request には、`address2` を含めて required 指定がない。詳細は
[更新 API で送らなかった項目の扱いの実測記録](../api-partial-update-observation.md)にある。出典: `d64e2c7`。

`name` と `address1` は毎回必須なので、利用者が `address2` を指定せずに顧客を更新すると、気づかないうちに `address2` が
消えていた。

## 判断

- **顧客データの更新で、`address2` を `name` / `address1` と同じく必須にする。** `Services\Customer::update()` は、
  `CustomerUpdateInput` で `address2` が明示されていなければ、送信前に `ParameterException` で拒否する。利用者の指定に
  よる。出典: `454326c`。
- `address2` は nullable のままとし、明示した `null` は必須を満たすものとして送る。`address2` を消したいときは `null` を
  明示する。出典: `454326c`。
- この必須は公式 OpenAPI の required 指定ではなく実測に基づくため、ADR 0015 の `name` / `address1` と同じく、
  `CustomerUpdateInput` の PHPDoc に「公式 OpenAPI との差分」として書く。出典: `454326c`。
- 顧客データの作成（`CustomerCreateInput`）は変えない。作成で `address2` が保存されるかは確かめていない。

## 代替案と却下理由

- **ライブラリでは何もせず、文書で注意を促す案**は、API の挙動にそのまま従い、互換性も変わらない。しかし利用者が
  文書を読まずに部分更新すると、`address2` が消えることに気づけない。
- **`address1` を送るときだけ `address2` を必須にする案**は、条件を API の挙動に近づけられる。しかし顧客の更新では
  `address1` が常に必須なので、実質的に常に必須と同じになる。
- **`address2` を省略したときに、ライブラリが今の値を取得して補う案**は、利用者のコードを変えずに済む。しかし 1 回の
  呼び出しで GET と PUT の 2 回のリクエストを送ることになり、[ADR 0035](0035-follow-api-without-client-side-features.md) の
  「公式 API にない結果を作る機能を作らない」方針に反する。

## 帰結

顧客データの更新で、`address2` が意図せず消えることがなくなる。

次の点が変わる。破壊的変更のため、次のマイナーバージョンで行う。

- `address2` を明示しない顧客データの更新は、送信前に `ParameterException` になる。今の値を保つには今の値を、消すには
  `null` を明示する必要がある
- `address2` を保つために、利用者が今の値を知っている必要がある。知らない場合は、先に顧客データを取得する

## 関連

- [ADR 0000: アーキテクチャ上の意思決定を記録する](0000-record-architecture-decisions.md)
- [ADR 0015: 顧客書き込み API の入力を作成と更新で分ける](0015-model-customer-write-api.md)
- [ADR 0035: 公式 API にない機能をライブラリで作らない](0035-follow-api-without-client-side-features.md)
- [更新 API で送らなかった項目の扱いの実測記録](../api-partial-update-observation.md)（出典コミット: `d64e2c7`）
- 実装とテストの出典コミット: `454326c`
- README の記載の出典コミット: `55fbafb`
