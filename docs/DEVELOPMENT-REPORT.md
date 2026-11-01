# 開発・検証記録

検証日: 2026-11-01。実環境: WordPress 7.1.4 / PHP 7.4.33 / Twenty Twenty-Five / Docker版wp-env。

## Phase 1: 公式API

実装: `tests/fixtures/od-api-probe/`の検証用プラグインと`tests/phase1-api.php`。公開APIの`wp_register_icon_collection` → `wp_register_icon(file_path)`の順で登録。

実測: 標準ライブラリ左側のOD API Probeタブ、標準検索、アイコン選択をChromeで確認。RESTコレクション2件（coreと検証用）、検証用アイコン1件、検索1件、すべて200。未ログイン401。標準core/iconの保存・サーバー描画でcurrentColor・48px・90度回転・水平反転を確認。保存済みアイコンをChromeで再編集表示。証跡: `screenshots/phase1-library.png`。

資料:
- https://make.wordpress.org/core/2026/07/24/registering-and-rendering-svg-icons-in-wordpress-7-1/
- https://developer.wordpress.org/reference/functions/wp_register_icon_collection/
- https://developer.wordpress.org/reference/functions/wp_register_icon/
- ローカル7.1.4の`wp-includes/icons.php`、各Registry、RESTコントローラー、標準ブロックのPHP/JS。
- 公式wordpress-develop/trunk調査時HEAD: b3f903c1d13060dcfe9336a16fa452c41b8472d9（実行テストはtrunkではなく7.1.4）。

仕様との関係: file_pathは登録時に読まれない。存在・拡張子・読み取り権限をプラグインでも検証する。コアの許可要素はsvg/path/polygonで、strokeやgは不可。WordPressはviewBoxをサニタイズ時にviewboxへ正規化するため、検査ではこの表記変更だけを許容する。

問題: IABでは編集キャンバスが空白になったためChromeへ切り替え。Chromeの開発用パスワード通知が操作を妨げていたが、通知を閉じた後は操作可能。コアファイルの改変は実施していない。

次: SVG互換性検証。

## Phase 2: SVG変換

実装: `scripts/svg_pipeline.py`、`compare-icons.mjs`、`tests/test_svg_pipeline.py`。

picosvg/Skiaで図形・stroke・transform・グループ継承を塗りパスへ反映。安全なXML解析の前にDTD/ENTITYを拒否。未知の要素/属性・外部参照・イベント・styleも拒否する。元データは変更しない。currentColorをpathに設定し、WordPressの許可リストを満たすSVGだけを書き出す。

代表サンプル: Feathericon Home、FA Regular Heart、Material Symbols Search、円と丸い線端の線画試験。96px描画の全RGBA差分率は順に0、0、0、約0.00001872（0.001872%）。ブラウザーでも比較。証跡: `screenshots/phase2-svg-comparison.png`。

差異: FeathericonはFeather Iconsとは別のプロジェクト。サンプルは元から塗りパス。Material SymbolsにはviewBoxのないSVGがあるため、正の数値width/heightからのみviewBoxを補完する。

除外: Feathericon `notice-push`はmaskを使う。maskを削除して無理に登録せず、1件を除外。詳細: `icon-build-report.json`。

次: 固定コミットの全対象SVGを取り込み、全件検証。

## Phase 3: ライブラリ統合

実装: `scripts/icon-sources.json`、`fetch-icons.py`、`build-icons.py`、`validate-icons.py`、`assets/icons/`、`licenses/`、`THIRD-PARTY-NOTICES.md`。

固定コミットをGitのsparse-checkoutで取得。manifestにバージョン、コミット、変換前後のSHA-256を保存。既存manifestの識別子が失われる更新はビルドを失敗させる。

- Feathericon 1.0.2: 255件中254件採用（maskを持つ1件除外）。
- Font Awesome Free 7.3.1: Solid/Regular計2,274件採用。Brands/Proを除外。
- Material Symbols 10ecc1479061: Outlined標準24pxの4,153件採用。64件はviewBox補完。

静的検証は6,681件すべて成功。WordPressでの登録成功6,681件、失敗0件。SVG変換前後の全件描画比較結果は`svg-comparison.json`に記録する。

次: 設定画面・統合検証。

## Phase 4: 管理画面と登録処理

実装: `includes/class-*.php`、`includes/collections.json`、本体、`uninstall.php`。

コレクション定義、登録、マニフェスト読み込み、検証、設定を分離。設定→OD Icon Collectionに有効切替、バージョン、対象/成功/失敗、公式リポジトリ、ライセンスを表示。無効化の影響を常時警告。Settings APIのoptions.phpでnonceとmanage_optionsを検証し、保存値は定義済みスラッグに限定。保存前は全件有効、空配列は全件無効。無効化は投稿・ブロック属性に触れない。

拡張フィルターの全定義を検証し、不正な1件で他を停止しない。SVGはディレクトリ直下のファイルだけを許可し、realpath後も包含を確認。外部の有効な定義もWordPress標準サニタイズを通る。

次: 自動テスト・ブラウザー検証・性能計測。

## Phase 5: 検証

PHPUnit: 実際のWordPressを読み込んで登録成功・再実行・重複定義・不正スラッグ・トラバーサル・symlink脱出・欠損ファイル・不正JSON・有効無効・外部定義追加・コレクション失敗時の中断・設定ホワイトリストと権限・REST権限と検索・保存と描画・全件サニタイズを検証。結果は実行後に追記。

Python: 危険要素、外部参照、イベント、style、DTD/ENTITY、空SVG、線/円/角丸/折線のパス化、viewBox補完、非対応出力を検証。

REST性能: `benchmark-*.json`は要求ごとに独立したCLIプロセスで計測した値。ネットワーク転送時間は含めない。全件（core88件込み6,769件）は約2,020ms、6.84MB、PHPピーク74.5MiB。コレクション別は約897〜1,263ms。Material Symbolsのhome検索27件は約13ms。

コア側の制約: 7.1.4のRESTコントローラーは、コレクションのフィルターより先に全アイコンを取得・SVG読込する。小さなコレクションでも初回応答は全件読込のコストがある。標準エディターは選択中コレクションの一覧を読み込み、検索はその一覧に対する標準のクライアント処理。独自APIや内部フックによる回避は行わない。これは本番サーバーの性能保証ではない。

## Phase 6: 配布

実装: README、CHANGELOG、ライセンス通知、ZIPスクリプト、GitHub Actions。配布ZIPには本体・includes・変換済みassets・languages・ライセンス・本番Composer依存だけを含める。開発キャッシュ、元SVG、tests、scripts、node_modules、開発用vendorを除外。

GitHubへのpush・Release作成は実施しない。

## 最終確認結果

- PHPUnit: 12テスト、20,224アサーション、失敗0。WordPress 7.1.4 / PHP 7.4.33で実行。
- Python: 8テスト成功。危険な入力・線と図形の変換・viewBoxと名前空間の検証を含む。
- 静的SVG検証・登録: 6,681件成功、失敗0。
- 全件描画比較: 6,681件すべて基準内。最大差分率0.301351%（Feathericon codepen）。差分上位6件をブラウザーで追加目視確認。`screenshots/svg-complex.png`。
- 再生成: 固定コミットの元SVGから再ビルドして、3件のmanifestと全SVGのハッシュが完全一致。オフラインの再生成を確認。Gitの部分取得で未取得blobを再取得しないよう、元データの検証にはGitツリーのblob IDとローカルのバイト列を直接照合する。
- アイコン名: 6,681件すべてがmanifestの英語ラベルと一致し、翻訳されないことを検証。設定・警告だけを日本語化。
- ブラウザー: CUAのPlaywright API等で、Feathericon Home / Font Awesome Regular Heart / Material Symbols Outlined Searchを標準UIから検索・選択。各々の文字色を変更し、幅48px / 64px / 80pxで保存・公開・再編集。公開ページの実際のSVG数・幅・色をDOMで確認。
- 公開ページで確認した色: rgb(246,207,244)、rgb(80,58,168)、rgb(104,104,104)。viewBoxはそれぞれ0 0 24 24、0 0 512 512、0 -960 960 960。
- 画面証跡: `screenshots/collections-feathericon.png`、`collections-fontawesome.png`、`collections-material.png`、`published-icons.png`、`reopened-editor.png`、`settings.png`。
- 設定保存: Feathericonを無効化して公開ページのSVGが3→2、有効化して2→3へ戻ることを確認。設定画面の対象/成功/失敗数も追随。
- ライフサイクル: プラグイン無効化時はSVG 0、再有効化後3。本文・設定は保持。Twenty Twenty-Fourへのテーマ変更でも3件表示し、元のTwenty Twenty-Fiveへ復帰。
- ZIP: 開発ソースを共有しない別環境（PHP 8.3.36）へ通常インストール。全6,681件登録と保存済み3アイコンの色・幅を確認。同一ZIPの上書き更新でも本文と表示を保持。
- WordPress更新: 独立環境で7.1.3→7.1.4へ標準WP-CLIで更新し、保存済み本文のSHA-256と3アイコン表示が維持されることを確認。
- アンインストール: 独立環境で実際に削除し、設定が削除され、投稿本文は保持されることを確認。再インストール後に3アイコンが復旧。
- 配布用の一連の回帰確認は`npm run test:package`として自動化し、通し実行成功。専用環境は終了時に停止。
- WPCS、Composer検証、PHP構文検証を実行。GitHubへのpush・Release作成は実施していない。

## 仕様との差異と残る範囲

仕様からの明示的な差異は、Feathericonのmaskを含む1件を除外したこと、およびMaterial Symbolsの64件に元の数値寸法からviewBoxを補完したこと。標準APIの遅延読み込み・コレクション絞り込み順序による初回コストは実測値として報告し、独自APIでは置き換えていない。

GitHub上でのActions実行は、pushしないという指定に従い未実施。ローカルで同等の主要チェックとZIP検証を実施した。マルチサイトの実環境、Windows、将来のWordPress、すべてのテーマの組み合わせは未検証。マルチサイトのアンインストール処理は実装済みだが、単一サイトと同じ実機検証済みとは扱わない。

実装フェーズ1〜6は完了。次の公開作業は、利用者から指示があった場合のGitHubレビュー・push・Release作成。
