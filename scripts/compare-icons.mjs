import {readFileSync,writeFileSync,mkdirSync} from 'node:fs';
import {Resvg} from '@resvg/resvg-js';
import {PNG} from 'pngjs';
import {createHash} from 'node:crypto';

// 同一エンジン・同一viewBox・黒色で、全対象の透明度と色を比較する。
export function compare(before,after) {
  function render(svg) {
    if(!/viewBox=/i.test(svg)) {
      const w=svg.match(/\bwidth="([0-9.]+)"/)?.[1],h=svg.match(/\bheight="([0-9.]+)"/)?.[1];
      if(!w||!h) throw Error('座標範囲が不明');
      svg=svg.replace('<svg',`<svg viewBox="0 0 ${w} ${h}"`);
    }
    const normalized=svg.replace(/<svg\b[^>]*>/,tag=>tag.replace('<svg','<svg color="#000000"').replace(/\s(?:width|height)="[^"]*"/g,''));
    const png=new Resvg(normalized,{fitTo:{mode:'width',value:96},font:{loadSystemFonts:false}}).render().asPng();
    return {image:PNG.sync.read(png),png};
  }
  const a=render(before), b=render(after);
  if(a.image.width!==b.image.width || a.image.height!==b.image.height) throw Error('描画寸法が不一致');
  let delta=0,ink=0;
  for(let i=0;i<a.image.data.length;i+=4) {
    ink+=a.image.data[i+3];
    for(let c=0;c<4;c++) delta+=Math.abs(a.image.data[i+c]-b.image.data[i+c]);
  }
  const ratio=delta/(a.image.width*a.image.height*4*255);
  if(ink===0 || b.image.data.every((v,i)=>i%4!==3 || v===0)) throw Error('空白SVG');
  return {ratio,before:a.png,after:b.png};
}
if(process.argv[2]) {
  const pairs=JSON.parse(readFileSync(process.argv[2],'utf8'));
  const results=[];
  for(const pair of pairs) {
    const source=readFileSync(pair.source,'utf8'),output=readFileSync(pair.output,'utf8');
    const result=compare(source,output);
    const hash=text=>createHash('sha256').update(text).digest('hex');
    results.push({name:pair.name,source_sha256:hash(source),sha256:hash(output),difference:result.ratio,passed:result.ratio<=0.005});
  }
  mkdirSync('docs',{recursive:true});
  writeFileSync('docs/svg-comparison.json',JSON.stringify(results,null,2)+'\n');
  const highest=[...results].sort((a,b)=>b.difference-a.difference).slice(0,6);
  const rows=highest.map(item=>{
    const pair=pairs.find(pair=>pair.name===item.name);
    const url=path=>'/wp-content/plugins/od-icon-collection/'+path;
    return `<tr><td>${item.name}<br>差分${(item.difference*100).toFixed(4)}%</td><td><img width="96" height="96" src="${url(pair.source)}"></td><td><img width="96" height="96" src="${url(pair.output)}"></td></tr>`;
  });
  writeFileSync('.cache/visual-comparison.html','<!doctype html><html lang="ja"><meta charset="utf-8"><title>SVG差分上位の目視確認</title><h1>SVG差分上位の目視確認</h1><table><tr><th>アイコン</th><th>変換前</th><th>変換後</th></tr>'+rows.join('')+'</table></html>');
  console.log(JSON.stringify({compared:results.length,failed:results.filter(r=>!r.passed).length,maxDifference:Math.max(...results.map(r=>r.difference))}));
  if(results.some(r=>!r.passed)) process.exitCode=1;
}
