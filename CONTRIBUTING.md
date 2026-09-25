# 開発の進め方

このリポジトリで作業するときの規約をまとめる。実装の設計判断は `docs/adr/` に記録しており、この文書は
手順と命名だけを扱う。

## ブランチ

`master` を唯一の長期ブランチとし、作業は必ずブランチを切って PR 経由で取り込む。

ブランチ名は `<type>/<topic>` とする。

```
feat/customer-write-api
fix/furigana-fallback
docs/adr-0017-value-fallback
refactor/request-input-naming
test/entity-contract-coverage
chore/bump-0.15.0
release/0.15.0
```

### type

コミットメッセージおよび PR タイトルと同じ語彙を使う。1 つの語彙で 3 つの規約を説明できるようにする
ためである。

| type | 用途 |
| --- | --- |
| `feat` | 機能の追加 |
| `fix` | 不具合の修正 |
| `docs` | ドキュメントのみの変更 (ADR、観測記録、README) |
| `refactor` | 振る舞いを変えない内部構造の変更 |
| `test` | テストのみの追加・変更 |
| `chore` | ビルド設定、依存、バージョン更新など |
| `release` | リリース準備 (`release/<バージョン>`) |

1 つのブランチに複数の type が混ざる場合は、主たる変更の type を選ぶ。混在が大きいなら、そもそも
ブランチを分けることを検討する。

### topic

英小文字のケバブケースで、変更の対象が分かる語にする。`release` の topic はバージョン番号とし、
タグと同じ表記 (`0.15.0`) を使う。

### issue 番号

ブランチ名には含めない。issue との紐付けは PR 本文の `Closes #53` で行う。GitHub が自動で issue を
閉じ、双方向のリンクも張られる。

## コミット

[Conventional Commits](https://www.conventionalcommits.org/ja/) 形式とし、本文は日本語で書く。

```
feat: 顧客データの追加に対応する

POST /v1/customers に対応する Entities\Customer\CustomerCreateInput と
Services\Customer::create()、Client::createCustomer() を追加する。
```

- 件名は変更内容を日本語で簡潔に述べる。コード識別子は英語のまま使う。
- 本文には、何をしたかに加えて「なぜそうしたか」を書く。特に公式 API 仕様と実測が食い違う場合は、
  どちらを採ったかとその根拠を残す。
- 破壊的変更は `feat!:` のように `!` を付ける。0.14.0 までは付けていなかったため、この規約は
  0.15.0 以降に適用する。
- 確認なしに自動でコミット・push しない。

## プルリクエスト

- base は `master`。
- タイトルはコミットと同じ Conventional Commits 形式にする。
- 本文には、変更の概要、設計判断とその根拠、破壊的変更、検証方法を書く。公式 API 仕様との差異が
  ある場合は必ず明記する。
- issue を解決する場合は `Closes #NN` を本文に含める。
- マージは merge commit で行う (squash や rebase は使わない)。過去のマージはすべて
  `Merge pull request #NN from ...` の形で残っている。

### push 前の確認

push する前に、ローカルで次を通す。

```bash
composer check
```

`composer validate --strict`、PHPStan、PHPUnit をまとめて実行する。個別に動かす場合は
`composer analyse` と `composer test` を使う。コマンドの一覧は README の「開発者向け」にある。

CI (`.github/workflows/test.yml`) は `master` への push と PR で動き、PHP 8.1 / 8.2 / 8.3 / 8.4 の
テスト、静的解析、カバレッジを実行する。

## 開発の進め方

- テスト駆動開発を行う。先に失敗するテストを書き、通してから整える。
- リファクタリングは振る舞いを変えない変更に限り、振る舞いの変更とは別のコミットに分ける。
- 既存のテストを確認なく削除・コメントアウトしない。
- 公式 API 仕様と実 API の挙動が食い違う場合は実測を採り、観測記録を `docs/` に残したうえで、
  該当箇所の PHPDoc に差異と観測日を明記する。
- 観測記録には個人情報を残さない。キー、型、`null` と欠損、HTTP status、エラーコードなど、
  構造の判断に必要な情報だけを記録する。

## 設計判断の記録

アーキテクチャ上の意思決定は ADR として `docs/adr/` に記録する。書式と運用規則は
[ADR 0000](docs/adr/0000-record-architecture-decisions.md) にある。各判断には、根拠を追跡できる
出典コミット SHA を付記する。

## リリース

1. `release/<バージョン>` ブランチで `composer.json` の `version` を更新し、PR を出す。
2. マージ後、`master` に同じ名前のタグを打ち、GitHub リリースを作成する。
3. リリースノートは日本語で、`契約変更 (要注意)` / `追加` / `実 API との差異 (観測に基づく実装)` /
   `既知の制約` / `設計判断と記録` の構成で書く。破壊的変更は先頭にまとめる。

バージョンは `0.x` のため、破壊的変更はマイナーバージョンの更新で行う。
