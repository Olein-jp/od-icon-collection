import {mkdirSync,writeFileSync,existsSync} from 'node:fs';
import {resolve,dirname,join} from 'node:path';
import {fileURLToPath} from 'node:url';
import {execFileSync} from 'node:child_process';

// ソースをマウントせず、ZIPから通常インストールする専用環境を使用する。
const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const directory=join(root,'.cache/package-check');
const binary=join(root,'node_modules/.bin/wp-env');
if(!existsSync(join(root,'dist/od-icon-collection.zip'))) throw Error('先にnpm run packageを実行してください。');
mkdirSync(directory,{recursive:true});
writeFileSync(join(directory,'package.json'),JSON.stringify({name:'od-icon-collection-package-check',private:true}));
writeFileSync(join(directory,'.wp-env.json'),JSON.stringify({
  core:'https://wordpress.org/wordpress-7.1.3.zip',phpVersion:'8.3',autoPort:true,plugins:[],
  mappings:{'wp-content/package-fixtures':join(root,'dist'),'wp-content/od-verification':join(root,'tests')},
  config:{WP_DEBUG:true}
},null,2)+'\n');
const run=args=>execFileSync(binary,args,{cwd:directory,stdio:'inherit'});
const wp=command=>run(['run','cli','sh','-c',command]);
let started=false;
try {
  started=true;
  run(['start','--update']);
  wp('wp plugin install /var/www/html/wp-content/package-fixtures/od-icon-collection.zip --force --activate');
  // 検証環境の設定を初期値へ戻して、新コレクションも検証する。
  wp("wp eval 'delete_option( \"od_icon_collection_enabled\" );'");
  wp('wp eval-file wp-content/od-verification/package-verification.php initial 7.1.3');
  wp('wp plugin install /var/www/html/wp-content/package-fixtures/od-icon-collection.zip --force --activate');
  wp('wp eval-file wp-content/od-verification/package-verification.php overwritten');
  wp('wp core update --version=7.1.4');
  wp('wp core update-db');
  wp('wp eval-file wp-content/od-verification/package-verification.php after-core-update 7.1.4');
  wp('wp plugin uninstall od-icon-collection --deactivate');
  wp('wp eval-file wp-content/od-verification/package-verification.php uninstalled');
  wp('wp plugin install /var/www/html/wp-content/package-fixtures/od-icon-collection.zip --activate');
  wp('wp eval-file wp-content/od-verification/package-verification.php reinstalled');
} finally {
  if(started) run(['stop']);
}
