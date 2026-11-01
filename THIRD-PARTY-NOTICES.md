# 同梱素材と依存ライブラリ

OD Icon Collection本体はGPL-2.0-or-laterです。同梱アイコンのライセンスは以下のとおり維持します。本プラグインは各ライブラリの公式認定・提携製品ではありません。

## Feathericon

- 提供元: Megumi Hano / feathericon contributors
- 公式リポジトリ: https://github.com/feathericon/feathericon
- 取得バージョン: 1.0.2（2026-11-01取得）
- 取得コミット: c60fd50debdce9fc6bfda37eda2d7c812e769d03
- ライセンス: MIT。全文: `licenses/feathericon/LICENSE`
- 著作権表示: Copyright (c) 2018 Megumi Hano
- 同梱対象: `src/svg`の255件中254件。Feather Iconsではありません。
- 除外: `notice-push`は`mask`を含み、今回の変換工程では品質を保証できないため除外。
- 改変: グループの継承・transformをパスへ反映、図形をパス化、不要なメタデータとIDを除去、塗りをcurrentColorへ正規化、viewBoxを保持。元コメントを保持。

## Font Awesome Free

- 提供元: Fonticons, Inc.
- 公式リポジトリ: https://github.com/FortAwesome/Font-Awesome
- 取得バージョン: 7.3.1（2026-11-01取得）
- 取得コミット: 14c65a3747d0f3b751f15831fc719236aea8729d
- ライセンス: SVGはCC BY 4.0。全文: `licenses/CC-BY-4.0.txt`。上流のライセンス通知全体: `licenses/fontawesome-free/LICENSE.txt`
- 著作権表示: Copyright 2026 Fonticons, Inc. https://fontawesome.com
- 同梱対象: FreeのSolid・RegularのSVG計2,274件。Brands・Pro・フォント・JS/CSSは含みません。
- 改変: パス座標を正規化、塗りをcurrentColorへ統一。元SVGのライセンス・著作権コメントを各ファイル内にも保持。
- 帰属先: https://fontawesome.com / https://fontawesome.com/license/free

## Material Symbols

- 提供元: Google / material-design-icons contributors
- 公式リポジトリ: https://github.com/google/material-design-icons
- 取得バージョン: コミット10ecc1479061（2026-11-01取得。上流のリリースタグに代えて固定コミットで管理）
- 取得コミット: 10ecc14790612ca8fd164e3f4ffe9c16fc50d5b7
- ライセンス: Apache License 2.0。上流全文: `licenses/material-symbols/LICENSE`
- 帰属表示: Material Symbols by Google。取得対象のSVGには個別の著作権年表示がありません。上流LICENSE中の通知をそのまま保持しています。
- 同梱対象: `symbols/web/*/materialsymbolsoutlined/*_24px.svg`のうち、各名前の標準24pxバリアント4,153件。Rounded・Sharp・fill1・追加weight/gradeバリアント・フォントは含みません。
- 改変: 曲線等をパス座標へ正規化、塗りをcurrentColorに統一。viewBoxのある4,089件は元の値を保持。viewBoxのない64件は数値のwidth/heightから`0 0 width height`を補完。
- この固定コミットの対象ルートには別のNOTICEファイルはありません。将来存在する場合は取得工程がコピーします。

## 変換と来歴

元ファイルは開発キャッシュに保持し、配布には変換済みSVGを含めます。各manifest.jsonには取得バージョン・コミット・元SVGのSHA-256・変換後のSHA-256を記録します。変換にはpicosvg / Skiaを使用し、線幅・線端・角丸を保持してstrokeを塗りのパスにします。未知の要素、外部参照、イベント属性、style、DTD・XML実体は拒否します。

## 更新用依存ライブラリ

`inc2734/wp-github-plugin-updater`とその依存関係はComposerの固定バージョンで配布します。各パッケージのLICENSE等の通知は`vendor`内に保持します。開発専用のPython・npm・PHPUnit依存関係は配布ZIPには同梱しません。
