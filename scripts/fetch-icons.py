"""公式Gitリポジトリから固定コミットの対象ファイルだけを取得する。"""
import json
import argparse
import subprocess
from pathlib import Path
ROOT = Path(__file__).resolve().parent.parent

def git(directory, *args, **kwargs):
    return subprocess.run(['git', '-C', str(directory), *args], check=True, **kwargs)

parser=argparse.ArgumentParser(description=__doc__)
parser.add_argument('--only',help='取得するコレクションID')
args=parser.parse_args()
sources=json.loads((ROOT/'scripts/icon-sources.json').read_text())
if args.only and args.only not in {s['id'] for s in sources}: parser.error('不明なコレクションID')
for source in sources:
    if args.only and source['id']!=args.only: continue
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
