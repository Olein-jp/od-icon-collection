"""公式SVGを安全な塗りのパスへ変換し、マニフェスト・来歴を生成する。"""
import hashlib
import json
import re
import shutil
import subprocess
from pathlib import Path
from svg_pipeline import convert
ROOT = Path(__file__).resolve().parent.parent
SOURCES = json.loads((ROOT/'scripts/icon-sources.json').read_text())

def write_json(path,data):
    path.parent.mkdir(parents=True,exist_ok=True)
    path.write_text(json.dumps(data,ensure_ascii=False,indent=2)+'\n')

pairs, excluded, definitions, counts = [], [], [], {}
staging=ROOT/'.cache/build-icons'
if staging.exists(): shutil.rmtree(staging)
for source in SOURCES:
    origin=ROOT/'.cache/sources'/source['id']
    head=subprocess.check_output(['git','-C',str(origin),'rev-parse','HEAD'],text=True).strip()
    if head!=source['commit']: raise RuntimeError('取得コミットが変更されています')
    entries=subprocess.check_output(['git','-C',str(origin),'ls-tree','-r','-z',source['commit']]).split(b'\0')
    blobs={}
    for entry in entries:
        if not entry: continue
        metadata,name=entry.split(b'\t',1)
        mode,kind,blob=metadata.split()
        if kind==b'blob' and mode in (b'100644',b'100755'): blobs[name.decode()]=blob.decode()
    def verified_text(path):
        data=path.read_bytes()
        actual=hashlib.sha1(b'blob '+str(len(data)).encode()+b'\0'+data).hexdigest()
        if blobs.get(str(path.relative_to(origin)))!=actual: raise RuntimeError('元ファイルが取得コミットと不一致: '+str(path))
        return data.decode('utf-8')
    if not (origin/source['license_file']).is_file(): raise RuntimeError('ライセンス欠損')
    verified_text(origin/source['license_file'])
    icons=[]
    if source['id']=='feathericon':
        files=[(p,p.stem) for p in sorted((origin/'src/svg').glob('*.svg'))]
    elif source['id']=='fontawesome-free':
        files=[(p,style+'-'+p.stem) for style in ['solid','regular'] for p in sorted((origin/'svgs'/style).glob('*.svg'))]
    else:
        files=[(p,'outlined-'+p.parent.parent.name) for p in sorted((origin/'symbols/web').glob('*/materialsymbolsoutlined/*_24px.svg')) if p.name==p.parent.parent.name+'_24px.svg']
    output=staging/source['id']/'svg'
    output.mkdir(parents=True,exist_ok=True)
    seen=set()
    for path,name in files:
        try:
            if not re.fullmatch(r'[a-z0-9](?:[a-z0-9_-]*[a-z0-9])?',name) or name in seen: raise ValueError('命名規則違反・重複')
            seen.add(name)
            text=verified_text(path)
            svg=convert(text)
            (output/(name+'.svg')).write_text(svg)
            icons.append({'name':name,'label':name.replace('-',' ').replace('_',' ').title(),'file':name+'.svg','source_sha256':hashlib.sha256(text.encode()).hexdigest(),'sha256':hashlib.sha256(svg.encode()).hexdigest()})
            pairs.append({'name':source['slug']+'/'+name,'source':str(path.relative_to(ROOT)),'output':'assets/icons/'+source['id']+'/svg/'+name+'.svg'})
        except Exception as error:
            excluded.append({'collection':source['slug'],'file':str(path.relative_to(origin)),'reason':str(error)})
    if not icons: raise RuntimeError('コレクションが空です')
    previous=ROOT/'assets/icons'/source['id']/'manifest.json'
    if previous.exists():
        missing={i['name'] for i in json.loads(previous.read_text())['icons']}-{i['name'] for i in icons}
        if missing: raise RuntimeError(f'既存識別子が失われます: {sorted(missing)}')
    write_json(staging/source['id']/'manifest.json',{'collection':source['slug'],'source_version':source['version'],'source_commit':source['commit'],'icons':icons})
    counts[source['id']]={'source':len(files),'included':len(icons),'excluded':len(files)-len(icons)}
    definitions.append({key:source[key] for key in ['id','slug','label','license','license_url']} | {'description':source['label']+' SVG icons.','repository_url':'https://github.com/'+source['repository']})
    licenses=ROOT/'licenses'/source['id']
    licenses.mkdir(parents=True,exist_ok=True)
    shutil.copy2(origin/source['license_file'],licenses/source['license_file'])
    if (origin/'NOTICE').exists():
        verified_text(origin/'NOTICE')
        shutil.copy2(origin/'NOTICE',licenses/'NOTICE')
    print(source['label'],counts[source['id']],flush=True)
assets=ROOT/'assets/icons'
assets.mkdir(parents=True,exist_ok=True)
for source in SOURCES:
    dest=assets/source['id']
    if dest.exists(): shutil.rmtree(dest)
    shutil.copytree(staging/source['id'],dest)
write_json(ROOT/'includes/collections.json',definitions)
write_json(ROOT/'docs/icon-build-report.json',{'counts':counts,'excluded':excluded})
write_json(ROOT/'.cache/comparison-pairs.json',pairs)
