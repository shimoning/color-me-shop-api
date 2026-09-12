# ADR 0006: Entity が扱うプロパティ種別を通常のインスタンスプロパティに限定する

- 状態: 採用
- 決定日: 2026-09-12

## 文脈

PR #19 では、プロパティ種別に起因する問題が Copilot と独立レビューの双方から繰り返し検出された。
static プロパティへ API 値を代入するとインスタンスをまたいで共有状態を汚染する。PHP 8.4 の virtual
property は非 static として検出されるため、hydrate 時や欠損した nullable プロパティの初期化時に set
hook が実行され、実体を格納していないのに `ReflectionProperty::isInitialized()` が `true` を返す。

また、hydrate から除外した static プロパティが `toArray()` と `toArrayRecursive()` には `null` として
現れ、取り込みと配列化の対象が一致していなかった。readonly プロパティについても、欠損した nullable
readonly の初期化が PHP 8.1 では生の `Error` になり PHP 8.4 では成功する一方、初期化済みの promoted
readonly へ API 値を代入すると両版で生の `Error` になるという版差があった。

## 判断

- `Entity` が hydrate と配列化の対象にするのは、通常のインスタンスプロパティだけとする。
- static プロパティは可視性や継承元を問わず対象外とする。対応する API キーは未知キーと同様に無視し、
  `getRaw()` が返す生データには残す。
- PHP 8.4 の virtual property も可視性や継承元を問わず対象外とする。対応する API キーの扱いは static
  と同じとし、set hook を実行せず、配列化にも含めない。PHP 8.1〜8.3 との互換性を保つため、
  `ReflectionProperty::isVirtual()` の存在を確認してから判定する。
- readonly は実体を持つ通常のインスタンスプロパティなので対象に含める。未初期化なら
  `ReflectionProperty::setValue()` で hydrate し、欠損した nullable readonly も同じ方法で `null` に
  初期化して PHP 8.1〜8.4 の挙動を揃える。
- promoted readonly などコンストラクタですでに初期化された readonly に対応する API キーが来た場合は、
  再代入時の `Error` を `InvalidFieldException` に正規化する。API キーがなければコンストラクタ値を維持し、
  通常のインスタンスプロパティとして配列化する。

## 代替案と却下理由

- static、virtual、readonly の問題へ種別ごとに個別対応する案は、hydrate、欠損初期化、配列化の各経路で
  判定が再び分散し、PHP に新しいプロパティ機能が追加されるたびに同種の不整合が再発するため却下した。
- static または virtual に対応する API キーで例外を投げる案は、宣言プロパティに対応しない未知キーを
  無視する既存挙動と整合せず、API の項目追加に対する前方互換性も損なうため却下した。
- readonly をすべて対象外にする案は、未初期化の readonly がリフレクションを通じて安全に一度だけ
  hydrate でき、実体を持つ通常のインスタンスプロパティとして有用なので却下した。
- 初期化済み readonly への API キーを無視する案は、既知の通常プロパティへ渡された値だけを黙って捨て、
  コンストラクタ値を配列化する非対称な挙動になるため却下した。

## 帰結

利用者定義 Entity に static または virtual property があっても、API レスポンスによる共有状態や set hook
の副作用を起こさず、安全に無視される。hydrate と配列化の対象が一致し、非対応種別が `null` キーとして
露出しない。readonly の挙動は PHP 8.1〜8.4 で固定され、再代入不能な入力から生の `Error` が利用者へ
漏れない。この「通常のインスタンスプロパティだけを対象にする」という基準は、将来 PHP に追加される
プロパティ機能を評価する際の判断基準になる。

## 関連

- [ADR 0004: Entity の `toArray()` は往復可能な直列化ではない](0004-entity-to-array-is-not-round-trippable.md)
- PR #19
- 出典コミット: `d2d052b750c71a6b381bace58aadc69c16a97607`
