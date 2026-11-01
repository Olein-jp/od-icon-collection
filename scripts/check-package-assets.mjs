import {readFileSync,realpathSync,readdirSync,existsSync} from 'node:fs';
import {join,sep} from 'node:path';
import {createHash} from 'node:crypto';

export function checkAssets(root) {
  const json=path=>JSON.parse(readFileSync(join(root,path),'utf8'));
  const definitions=json('includes/collections.json');
  const comparison=json('docs/svg-comparison.json');
  const checked=new Map(comparison.map(item=>[item.name,item]));
  let count=0;
  for(const definition of definitions) {
    if(!/^[a-z0-9-]+$/.test(definition.id)) throw Error('不正なコレクションパス');
    const base=join(root,'assets/icons',definition.id);
    const directory=realpathSync(join(base,'svg'));
    const manifest=JSON.parse(readFileSync(join(base,'manifest.json'),'utf8'));
    if(manifest.collection!==definition.slug || !manifest.icons.length) throw Error('不正なマニフェスト');
    const seen=new Set();
    for(const icon of manifest.icons) {
      if(!/^[a-z0-9](?:[a-z0-9_-]*[a-z0-9])?$/.test(icon.name) || icon.file!==icon.name+'.svg' || seen.has(icon.name)) throw Error('不正なアイコン識別子');
      seen.add(icon.name);
      const path=realpathSync(join(directory,icon.file));
      if(!path.startsWith(directory+sep)) throw Error('ディレクトリ外のSVG');
      const hash=createHash('sha256').update(readFileSync(path)).digest('hex');
      const report=checked.get(definition.slug+'/'+icon.name);
      if(hash!==icon.sha256 || !report?.passed || report.sha256!==hash || report.source_sha256!==icon.source_sha256 || !Number.isFinite(report.difference) || report.difference>0.005) throw Error('SVGの全件比較結果が不足または古くなっています: '+icon.name);
      count++;
    }
    if(readdirSync(directory).length!==manifest.icons.length) throw Error('余分なSVGがあります');
  }
  if(count!==checked.size || count!==comparison.length) throw Error('比較レポートとアイコン数が不一致');
  for(const path of ['licenses/feathericon/LICENSE','licenses/fontawesome-free/LICENSE.txt','licenses/material-symbols/LICENSE','licenses/lucide/LICENSE','licenses/CC-BY-4.0.txt','THIRD-PARTY-NOTICES.md']) {
    if(!existsSync(join(root,path))) throw Error('ライセンス欠損: '+path);
  }
  console.log(`配布前検証: ${count}件のSVGと全件比較結果・ライセンスが一致`);
}
