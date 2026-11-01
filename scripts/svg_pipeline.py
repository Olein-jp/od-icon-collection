"""安全性とWordPress 7.1許可リストを検査して、図形と線をパスへ変換する。"""
import re
import math
import xml.etree.ElementTree as ET
from defusedxml.ElementTree import fromstring
from picosvg.svg import SVG

NS = 'http://www.w3.org/2000/svg'
ET.register_namespace('', NS)
SOURCE_TAGS = {'svg', 'g', 'path', 'polygon', 'polyline', 'rect', 'circle', 'ellipse', 'line', 'title', 'desc', 'defs'}
SOURCE_ATTRS = {'width','height','viewBox','version','id','fill','fill-rule','clip-rule','stroke','stroke-width','stroke-linecap','stroke-linejoin','stroke-miterlimit','stroke-dasharray','stroke-dashoffset','d','points','transform','x','y','x1','x2','y1','y2','cx','cy','r','rx','ry'}
OUTPUT_ATTRS = {'svg': {'viewBox','xmlns'}, 'path': {'d','fill','fill-rule','transform'}, 'polygon': {'points','fill','fill-rule','transform'}}

def local(tag):
    return tag.rsplit('}', 1)[-1]

def parse_safe(text):
    if re.search(r'<!DOCTYPE|<!ENTITY', text, re.I):
        raise ValueError('DTD・外部実体は禁止')
    root = fromstring(text, forbid_dtd=True, forbid_entities=True, forbid_external=True)
    if root.tag != f'{{{NS}}}svg':
        raise ValueError('SVG名前空間が不正')
    return root

def validate_viewbox(root):
    try:
        values = [float(v) for v in re.split(r'[ ,]+', root.get('viewBox', '').strip())]
    except ValueError as error:
        raise ValueError('viewBoxが不正') from error
    if len(values)!=4 or not all(math.isfinite(v) for v in values) or values[2]<=0 or values[3]<=0:
        raise ValueError('viewBoxが不正')

def validate_source(text):
    root = parse_safe(text)
    for element in root.iter():
        if not element.tag.startswith(f'{{{NS}}}') or local(element.tag) not in SOURCE_TAGS:
            raise ValueError(f'未対応要素: {element.tag}')
        for key, value in element.attrib.items():
            if key not in SOURCE_ATTRS or re.search(r'url\s*\(|javascript:|https?://|data:', value, re.I):
                raise ValueError(f'危険または未対応属性: {key}')
    if not root.get('viewBox'):
        width, height = root.get('width', ''), root.get('height', '')
        if not re.fullmatch(r'[0-9]+(?:\.[0-9]+)?', width) or not re.fullmatch(r'[0-9]+(?:\.[0-9]+)?', height):
            raise ValueError('viewBoxも数値の寸法もない')
        root.set('viewBox', f'0 0 {width} {height}')
    validate_viewbox(root)
    return root

def validate_output(text):
    root = parse_safe(text)
    validate_viewbox(root)
    if len(root) == 0 or not root.get('viewBox'):
        raise ValueError('空のSVGまたはviewBox欠損')
    for element in root.iter():
        tag = local(element.tag)
        if not element.tag.startswith(f'{{{NS}}}') or tag not in OUTPUT_ATTRS or set(element.attrib) - OUTPUT_ATTRS[tag]:
            raise ValueError(f'WordPress非対応: {tag} {element.attrib.keys()}')
        if tag != 'svg' and (element.get('fill') != 'currentColor' or not element.get('d' if tag == 'path' else 'points')):
            raise ValueError('塗りまたは形状が不正')
        for value in element.attrib.values():
            if re.search(r'url\s*\(|javascript:|https?://|data:', value, re.I):
                raise ValueError('外部参照は禁止')
    return root

def convert(text):
    original = validate_source(text)
    # 外部参照などを拒否した後にのみ、picosvgへ入力する。
    pico = SVG.fromstring(ET.tostring(original, encoding='unicode')).topicosvg(ndigits=5)
    converted = ET.fromstring(pico.tostring())
    output = ET.Element(f'{{{NS}}}svg', {'viewBox': original.get('viewBox')})
    for element in converted:
        if local(element.tag) == 'defs' and len(element) == 0:
            continue
        if local(element.tag) != 'path' or set(element.attrib) - {'d','fill','fill-rule','clip-rule','id'}:
            raise ValueError('パス化できない属性・グループが残っている')
        if element.get('fill') == 'none':
            continue
        path = ET.SubElement(output, f'{{{NS}}}path', {'fill':'currentColor','d':element.get('d','')})
        if element.get('fill-rule'):
            path.set('fill-rule', element.get('fill-rule'))
    result = ET.tostring(output, encoding='unicode') + '\n'
    validate_output(result)
    # 元の著作権コメントを保持する。
    comments = re.findall(r'<!--[\s\S]*?-->', text)
    if comments:
        result = result.replace('>', '>' + ''.join(comments), 1)
    return result
