# OD Icon Collection

WordPress 用アイコンコレクション。バージョン `0.0.0` は開発基盤のみです。

## 開発環境

- Node.js 24.18 以上、npm 11.16 以上（`.nvmrc` は Node.js 24）
- PHP 7.4 以上、Composer 2（ホストで lint／パッケージを作成する場合）
- Docker（起動しておく）
- WordPress 6.8 以上。wp-env は最新安定版 WordPress／PHP 7.4 を使用します。

```sh
npm ci
composer install
npm run env:start
```

ローカル環境は `http://localhost:8888` を起点に空きポートを自動選択します。ユーザー名 `admin`、パスワード `password` です。起動時の出力で URL を確認してください。

ホストに PHP／Composer がない場合、`composer install` は環境起動後に次のコマンドで実行できます。

```sh
npm run env:composer -- install
```

```sh
npm run env:stop
npm run env:status
npm run env:logs
npm run env:cli -- plugin list
composer lint
```

コンテナ内での lint は `npm run env:composer -- lint` を使います。個別の設定は Git 管理外の `.wp-env.override.json` に記載してください。

現行 wp-env の既定動作によりテスト用サイトも起動します。非推奨の `testsEnvironment` 設定は追加していません。npm の監査では wp-env の間接依存関係に中程度の指摘が 7 件あります。これらの開発用依存関係は配布 ZIP に含まれません。

## 多言語化

文字列は英語を原文とし、`od-icon-collection` をテキストドメインに指定します。プラグイン内の翻訳ファイルを `init` で登録します。

```sh
npm run i18n:pot
# POT を基に languages/od-icon-collection-ja.po などを編集
npm run i18n:mo
```

生成した POT／PO／MO はコミットしてください。翻訳の変更後は MO を再生成してからタグを作成します。更新ライブラリの翻訳は同ライブラリに同梱されています。

## パッケージとリリース

```sh
npm run package
```

`dist/od-icon-collection.zip` に、`od-icon-collection/` を最上位ディレクトリとするインストール用 ZIP を生成します。固定された Composer 依存関係を一時ディレクトリに本番用としてインストールするため、手元の開発用 `vendor` は変更しません。Composer と `zip`、依存パッケージ取得のためのネットワーク接続が必要です。

次のバージョンでは以下を同じ番号に変更します。

1. `od-icon-collection.php` の `Version`
2. `package.json`／`package-lock.json` のバージョン（`npm version <番号> --no-git-tag-version`）
3. `readme.txt` の `Stable tag` と変更履歴

```sh
git add .
git commit -m "バージョン 0.0.1 を準備"
git tag 0.0.1
git push origin main
git push origin 0.0.1
```

タグは `v` を付けず、`0.0.0` の形式にします。GitHub Actions がバージョンの一致と WPCS を検査して、GitHub Releases に `od-icon-collection.zip` を公開します。

インストールには Releases に添付された ZIP を使用してください。GitHub 自動生成の Source code ZIP は Composer 依存関係を含みません。

更新通知は [inc2734/wp-github-plugin-updater](https://github.com/inc2734/wp-github-plugin-updater) が公開 Releases を参照して行います。現在より大きいバージョンの通常リリースが公開されると WordPress 上で更新できます。非公開リポジトリの認証設定は含めていません。

## ライセンス

GPL-2.0-or-later。作者: Koji Kuno（https://olein-design.com）。
