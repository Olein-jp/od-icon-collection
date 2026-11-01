# OD Icon Collection

WordPress標準のアイコンライブラリに、3つのオープンソースコレクションを追加するプラグインです。標準の`core/icon`ブロックから検索・選択し、色・幅・配置・回転・反転を設定できます。

- 対応: WordPress 7.1以降 / PHP 7.4以降
- 作者: Koji Kuno / https://olein-design.com
- 本体: GPL-2.0-or-later（同梱素材は各ライセンスを維持）
- 公開先: GitHub。独自ブロック・独自ピッカー・独自REST APIは追加しません。

## 同梱コレクション

| コレクション | 固定バージョン | アイコン数 | ライセンス |
| --- | --- | ---: | --- |
| Feathericon | 1.0.2 | 254 | MIT |
| Font Awesome Free | 7.3.1 / Solid・Regular | 2,274 | SVG: CC BY 4.0 |
| Material Symbols | 10ecc1479061 / Outlined標準24px | 4,153 | Apache-2.0 |

合計6,681件。アイコンの表示名は元の英単語を使い、翻訳対象にしません。設定画面の案内・警告は日本語表示に対応します。Feathericonは`feathericon/feathericon`であり、Feather Iconsとは別のプロジェクトです。`notice-push`1件はmaskの変換品質を保証できないため除外しています。Font Awesome Brands/Pro、Material SymbolsのRounded/Sharp・追加weight/grade/fillバリアントは含みません。

取得コミット、著作権、改変内容は[THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md)を参照してください。各ライブラリと本プラグインの公式提携・認定を示すものではありません。

## インストールと使い方

1. GitHub Releasesに添付された`od-icon-collection.zip`をダウンロードします。
2. WordPressの「プラグイン → プラグインを追加 → プラグインのアップロード」でZIPを選択し、有効化します。
3. 標準の「アイコン」ブロックを追加し、「置換」からアイコンライブラリを開きます。
4. 左側でコレクションを選び、英語名で検索してアイコンを選択します。
5. ブロックの「スタイル」で色・幅、ツールバーで配置・回転・反転を設定します。

初回は全コレクションが有効です。「設定 → OD Icon Collection」で切り替えられます。設定変更には`manage_options`権限が必要です。

**無効化すると、既存の投稿・固定ページで使用しているアイコンが表示されなくなる場合があります。** 再度有効にすると同じ識別子で復旧します。投稿本文やブロック属性は変更しません。

アイコンは同梱済みで、通常の登録・設定・表示には外部通信を要求しません。従来のGitHub更新ライブラリはWordPressの更新チェック時にGitHubへ接続します。オフラインでは更新チェック以外のアイコン機能を利用できます。フロントエンドへ本プラグイン専用のJS/CSSや全件SVGを追加しません。

## 開発環境

Node.js 24.18以上・npm 11.16以上、PHP 7.4以上・Composer 2、Dockerを用意します。SVG再生成にはPython 3.10以降も必要です。本番ではNode.js/Pythonは不要です。

```sh
npm ci
composer install
npm run env:start
```

wp-envはWordPress 7.1.4 / PHP 7.4を指定しています。`http://localhost:8888`から空きポートを自動選択します。出力に表示されたURLを使用してください。開発用ログインは`admin` / `password`です。個別設定はGit管理外の`.wp-env.override.json`に記載します。

```sh
npm run env:status
npm run env:cli -- plugin list
npm run env:stop
```

ホストにComposerがない場合は環境起動後に`npm run env:composer -- install`を利用できます。

## SVGの取得・再生成

配布用SVGとmanifestはリポジトリに含みます。通常のインストールやパッケージ作成で再取得は不要です。

```sh
python3 -m venv .cache/venv
.cache/venv/bin/pip install -r scripts/requirements-icons.txt
npm run icons:fetch
npm run icons:build
npm run icons:validate
npm run icons:compare
```

取得元は`scripts/icon-sources.json`の公式リポジトリと固定コミットです。`.cache/sources`に元SVG・ライセンスを取得し、元ファイルを変更せずpicosvg/Skiaで線・図形・transformを塗りパスへ変換します。DTD/外部実体・未知の要素/属性・外部参照・イベント・styleを拒否し、WordPressの許可リストを満たす出力を検証します。

`viewBox`を保持し、pathに`fill="currentColor"`を設定します。元にviewBoxがない場合は数値のwidth/heightから補完します。変換できない素材は除外理由を`docs/icon-build-report.json`に記録します。全件を96pxで描画比較し、RGBAの平均差分率0.5%を超える結果、空白画像、寸法の不一致を失敗として扱います。これは目視確認を置き換える基準ではありません。差分上位の複雑なアイコンはブラウザーでも比較しています。比較後に`.cache/visual-comparison.html`を生成します。ローカルWordPressのプラグインディレクトリ配下の同パスから開けます。

Material SymbolsのGitツリーが大きいため、取得キャッシュには約1GBの空き容量が必要です。取得後のビルド・検証・比較はオフラインで実行できます。各manifestは元と変換後のSHA-256を含みます。

## ライブラリ更新と追加

1. `scripts/icon-sources.json`の取得バージョン・コミットと対象を明示的に更新します。
2. 取得・ビルド・安全性検証・全件描画比較を実行します。
3. 既存識別子が失われる更新はビルドを停止します。上流で削除・改名された場合は旧SVGの維持か移行方針を検討し、READMEと変更履歴に記載してください。
4. ライセンス・NOTICE・来歴・件数を確認し、WordPress上のテストとZIP生成を実施します。

保存識別子の例は`od-feathericon/home`、`od-fontawesome-free/solid-heart`、`od-fontawesome-free/regular-heart`、`od-material-symbols/outlined-search`です。将来のスタイルも名前にプレフィックスを付けて衝突を避けます。

外部PHPコードからは`od_icon_collection_collections`フィルターを利用できます。

```php
add_filter( 'od_icon_collection_collections', static function ( $collections ) {
    $collections[] = array(
        'slug'            => 'od-custom',
        'label'           => 'Custom',
        'description'     => '追加コレクション',
        'icons_directory' => __DIR__ . '/custom/svg',
        'manifest_path'   => __DIR__ . '/custom/manifest.json',
    );
    return $collections;
} );
```

manifestは`collection`、`source_version`、`icons`を持ち、各アイコンに`name`、`label`、`file`を指定します。同梱manifestが実例です。JSONは8MiB以下、1コレクションは20,000件以下を上限とします。ファイル名は小文字英数字・ハイフン・アンダースコアと`.svg`だけを許可し、サブディレクトリは使えません。ディレクトリとJSONは読み取り可能なローカル絶対パス、SVGはそのディレクトリ内の実在ファイルである必要があります。スキーム付きURLやパスの`..`、外側へ出るsymlinkを拒否します。外部SVGにもWordPressのサニタイズが適用されます。

## テストと計測

開発専用WordPressで実行してください。PHPUnitは一時的な投稿・設定を作成して復元します。

```sh
composer validate --strict
composer lint
npm run test:php
npm run test:svg
npm run icons:validate
npm run icons:compare
npm run package
npm run test:package
```

PHPUnitは実際のWordPress APIで登録、重複、外部定義、無効化、不正パス、メタデータ、設定権限、REST検索、保存描画、全件サニタイズを検証します。PythonテストはSVGの危険入力とパス変換を検証します。`test:package`は専用のWordPress 7.1.3 / PHP 8.3環境へZIPを通常インストールし、上書き更新、WordPress 7.1.4への更新、アンインストールと再インストールを検証します。既存の開発環境とは分離して起動し、終了時に専用環境を停止します。

ブラウザーで3コレクションの検索・選択、標準の色/幅変更、保存・公開・再編集を確認しています。実施内容と結果、画面証跡、未検証範囲は[開発・検証記録](docs/DEVELOPMENT-REPORT.md)を参照してください。

標準RESTの計測例:

```sh
npm run env:cli -- eval-file wp-content/plugins/od-icon-collection/tests/benchmark.php all
npm run env:cli -- eval-file wp-content/plugins/od-icon-collection/tests/benchmark.php od-material-symbols home
```

## 制限事項

- Feathericonの`notice-push`1件は除外しています。
- 7.1.4の標準REST APIは、コレクションの絞り込み前に全アイコンを読み込みます。ローカル計測では全件応答約2秒・6.84MB・PHPピーク74.5MiBでした。サーバーにより変動します。
- 標準ライブラリはコレクション一覧を取得してから検索します。日本語名・別名・独自キーワードは追加しません。
- 将来のWordPress更新や未検証テーマすべてでの互換性は保証していません。実際に検証したバージョンと範囲は検証記録を参照してください。
- 変換基準を超えた差分や未対応SVGは、外観を損なったまま登録せず除外・報告します。

## 翻訳

```sh
npm run i18n:pot
# languages/od-icon-collection-ja.poを編集
npm run i18n:mo
```

POT/PO/MOを同時に更新します。原文は英語、日本語訳を同梱しています。

## 配布ZIPと公開

```sh
npm run package
```

`dist/od-icon-collection.zip`を生成します。最上位は`od-icon-collection/`で、変換済みSVG・manifest・必要なライセンス・本番Composer依存を含みます。開発依存、元SVG、テスト、スクリプト、node_modulesは含みません。Composerとzip、および本番依存の取得に使うネットワークが必要です。

GitHubの自動生成Source code ZIPは本番Composer依存を含まないため、そのままではGitHub更新機能を利用できません。手元で`composer install --no-dev`するか、Releaseに添付されたインストール用ZIPを使います。

バージョンは本体ヘッダー・package.json/package-lock.json・readme.txtで揃えます。タグは`1.0.0`の形式です。GitHub Actionsは品質チェックとZIP生成を行い、明示的にタグをpushした場合のみReleaseワークフローが公開します。GitHubへのpushとRelease作成は、利用者の指示に従って行います。

## 無効化・アンインストール

通常のプラグイン無効化では設定を保持します。アンインストール時は本プラグインの有効コレクション設定だけを削除します。マルチサイトでは各サイトの同設定を削除します。投稿本文・ブロック属性には触れません。

プラグインを無効化・削除すると登録がなくなるため、同梱アイコンが表示されなくなる場合があります。再インストール・再有効化すると同じ識別子で復旧できます。
