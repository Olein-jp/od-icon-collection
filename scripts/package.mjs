import { cpSync, existsSync, mkdirSync, mkdtempSync, readFileSync, readdirSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { execFileSync } from 'node:child_process';
import { checkAssets } from './check-package-assets.mjs';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const slug = 'od-icon-collection';
const version = JSON.parse(readFileSync(join(root, 'package.json'))).version;
const header = readFileSync(join(root, `${slug}.php`), 'utf8');
const readme = readFileSync(join(root, 'readme.txt'), 'utf8');
if (!/^\d+\.\d+\.\d+$/.test(version) ||
    header.match(/\* Version:\s*(\S+)/)?.[1] !== version ||
    readme.match(/Stable tag:\s*(\S+)/)?.[1] !== version ||
    (process.env.GITHUB_REF_TYPE === 'tag' && process.env.GITHUB_REF_NAME !== version)) {
  throw new Error('バージョン番号を PHP、package.json、readme.txt、タグで統一してください。');
}
for (const file of ['includes/collections.json', 'THIRD-PARTY-NOTICES.md', 'licenses/CC-BY-4.0.txt']) {
  if (!existsSync(join(root,file))) throw new Error(`必須ファイルがありません: ${file}`);
}
checkAssets(root);
const temporary = mkdtempSync(join(tmpdir(), `${slug}-`));
const stage = join(temporary, slug);
const output = join(root, 'dist', `${slug}.zip`);
try {
  mkdirSync(stage);
  for (const file of [`${slug}.php`, 'README.md', 'readme.txt', 'LICENSE', 'THIRD-PARTY-NOTICES.md', 'CHANGELOG.md', 'uninstall.php', 'includes', 'assets', 'licenses', 'languages', 'composer.json', 'composer.lock']) {
    cpSync(join(root, file), join(stage, file), { recursive: true });
  }
  execFileSync('composer', ['install', '--no-dev', '--prefer-dist', '--no-interaction', '--no-progress', '--no-plugins', '--no-scripts', '--classmap-authoritative'], { cwd: stage, stdio: 'inherit' });
  const installed = JSON.parse(readFileSync(join(stage, 'vendor/composer/installed.json')));
  if (installed.dev || installed.packages.some((item) => /phpcodesniffer|wpcs|composer-installer/.test(item.name))) {
    throw new Error('開発用依存関係が含まれています。');
  }
  if (!existsSync(join(stage, 'vendor/inc2734/wp-github-plugin-updater/src/Bootstrap.php'))) {
    throw new Error('更新ライブラリが不足しています。');
  }
  // 配布物から依存パッケージの開発用ディレクトリを取り除く。
  function clean(directory) {
    for (const entry of readdirSync(directory, { withFileTypes: true })) {
      const path = join(directory, entry.name);
      if (entry.isDirectory() && ['.git', '.github', 'tests', 'test', 'node_modules'].includes(entry.name)) {
        rmSync(path, { recursive: true });
      } else if (entry.isDirectory()) {
        clean(path);
      }
    }
  }
  clean(join(stage, 'vendor'));
  rmSync(join(stage, 'composer.json'));
  rmSync(join(stage, 'composer.lock'));
  mkdirSync(dirname(output), { recursive: true });
  rmSync(output, { force: true });
  execFileSync('zip', ['-q', '-r', '-X', output, slug], { cwd: temporary, stdio: 'inherit' });
  console.log(`配布 ZIP を作成しました: ${output}`);
} finally {
  rmSync(temporary, { recursive: true, force: true });
}
