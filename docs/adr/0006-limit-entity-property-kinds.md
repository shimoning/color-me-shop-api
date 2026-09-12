# ADR 0006: Entity のプロパティ解決を通常のインスタンスプロパティに統一する

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

プロパティの列挙と解決が hydrate、欠損した optional の初期化、getter の初期化確認、配列化で分散し、
可視性と継承に対する規則も一致していなかった。利用者サブクラスの private は hydrate されても配列化と
optional 初期化から漏れた。親の private と子の同名プロパティが併存すると、親 getter の初期化確認が子の
宣言を参照し、未初期化の親 private を直接読んで生の `Error` を発生させた。さらに PHP 8.4 では、除外
判定より前の `get_object_vars()` が virtual property の get hook を実行していた。

## 判断

- hydrate、欠損した optional の初期化、getter の初期化確認、配列化の4経路は、単一の Reflection ベースの
  リゾルバが返す通常のインスタンスプロパティを使用する。
- リゾルバは実行時クラスから親方向へ宣言を探索し、同名では最も近い宣言を採用する。
- 最も近い宣言が static または virtual という契約外の種別なら、その名前を未知キーとして扱い、祖先の
  同名宣言へフォールスルーしない。
- private、protected、public の全可視性を4経路で一貫して対象にする。hydrate した private は配列化にも
  含め、欠損した nullable private も optional として初期化する。
- 配列化ではリゾルバによる列挙と対象外判定を先に完了し、採用した通常プロパティだけから値を読み取る。
  除外判定前に `get_object_vars()` を呼ばず、契約外 property の get hook に副作用を起こさない。
- getter の `assertFieldInitialized()` は getter の宣言クラスを探索起点にする。親 private と子の同名宣言が
  併存しても、親 getter は子のプロパティを初期化済みと誤認しない。
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

- static、virtual、readonly の問題へ種別ごとに個別対応する案は、hydrate、欠損初期化、初期化確認、
  配列化の各経路で判定が再び分散し、PHP に新しいプロパティ機能が追加されるたびに同種の不整合が
  再発するため却下した。
- static または virtual に対応する API キーで例外を投げる案は、宣言プロパティに対応しない未知キーを
  無視する既存挙動と整合せず、API の項目追加に対する前方互換性も損なうため却下した。
- readonly をすべて対象外にする案は、未初期化の readonly がリフレクションを通じて安全に一度だけ
  hydrate でき、実体を持つ通常のインスタンスプロパティとして有用なので却下した。
- 初期化済み readonly への API キーを無視する案は、既知の通常プロパティへ渡された値だけを黙って捨て、
  コンストラクタ値を配列化する非対称な挙動になるため却下した。

## 帰結

利用者定義 Entity に static または virtual property があっても、API レスポンスによる共有状態や property
hook の副作用を起こさず、安全に無視される。hydrate、optional 初期化、初期化確認、配列化の対象が一致し、
private も入出力から失われず、非対応種別が `null` キーとして露出しない。readonly の挙動は PHP 8.1〜8.4
で固定され、再代入不能または未初期化の入力から生の `Error` が利用者へ漏れない。この統一リゾルバと
「最も近い通常のインスタンスプロパティだけを対象にする」という基準は、将来 PHP に追加される
プロパティ機能を評価する際の判断基準になる。

## 関連

- [ADR 0004: Entity の `toArray()` は往復可能な直列化ではない](0004-entity-to-array-is-not-round-trippable.md)
- PR #19
- 出典コミット (static): `d2d052b750c71a6b381bace58aadc69c16a97607`
- 出典コミット (virtual / readonly): `9115f2ce0317285ce9146e89a1459ad1dc0117d4`
- 出典コミット (統一リゾルバ): `326f1ac8d3041888a7005df6b583326994fad2bc`
