"""公式Gitリポジトリから固定コミットの対象ファイルだけを取得する。"""
import json
import subprocess
from pathlib import Path
ROOT = Path(__file__).resolve().parent.parent

def git(directory, *args, **kwargs):
    return subprocess.run(['git', '-C', str(directory), *args], check=True, **kwargs)

for source in json.loads((ROOT/'scripts/icon-sources.json').read_text()):
    directory=ROOT/'.cache/sources'/source['id']
    directory.mkdir(parents=True, exist_ok=True)
    if not (directory/'.git').exists():
        git(directory,'init','--quiet')
        git(directory,'remote','add','origin',f'https://github.com/{source["repository"]}.git')
    git(directory,'config','remote.origin.promisor','true')
    git(directory,'config','remote.origin.partialclonefilter','blob:none')
    git(directory,'fetch','--depth=1','--filter=blob:none','origin',source['commit'])
    git(directory,'sparse-checkout','set','--no-cone','--stdin',input='\n'.join(source['patterns'])+'\n',text=True)
    git(directory,'checkout','--detach',source['commit'])
    head=subprocess.check_output(['git','-C',str(directory),'rev-parse','HEAD'],text=True).strip()
    if head!=source['commit']: raise RuntimeError('取得コミット不一致')
    print(f'{source["label"]}: {head}',flush=True)
