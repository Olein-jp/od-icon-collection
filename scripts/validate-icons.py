"""配布対象全件のSVG・メタデータ・ハッシュ・ライセンスを検証する。"""
import hashlib
import json
import re
from pathlib import Path
from svg_pipeline import validate_output
ROOT=Path(__file__).resolve().parent.parent
count=0
for source in json.loads((ROOT/'scripts/icon-sources.json').read_text()):
    base=ROOT/'assets/icons'/source['id']
    manifest=json.loads((base/'manifest.json').read_text())
    assert manifest['collection']==source['slug'] and manifest['source_commit']==source['commit']
    assert (ROOT/'licenses'/source['id']/source['license_file']).is_file()
    seen=set()
    for icon in manifest['icons']:
        assert re.fullmatch(r'[a-z0-9](?:[a-z0-9_-]*[a-z0-9])?',icon['name'])
        assert icon['name'] not in seen and icon['label'].strip()
        seen.add(icon['name'])
        assert icon['file']==icon['name']+'.svg'
        path=base/'svg'/icon['file']
        assert path.is_file() and path.resolve().parent==(base/'svg').resolve()
        text=path.read_text()
        assert hashlib.sha256(text.encode()).hexdigest()==icon['sha256']
        validate_output(text)
        count+=1
    assert len(list((base/'svg').glob('*.svg')))==len(manifest['icons'])
for name in ['THIRD-PARTY-NOTICES.md','licenses/CC-BY-4.0.txt']:
    assert (ROOT/name).is_file(), name
print(f'{count}件のSVG、マニフェスト、ライセンス検証に成功')
