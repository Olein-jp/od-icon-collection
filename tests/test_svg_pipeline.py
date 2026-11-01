"""危険なSVG・未対応SVGを拒否し、線をパス化する回帰テスト。"""
import sys
import unittest
from pathlib import Path
sys.path.insert(0,str(Path(__file__).resolve().parent.parent/'scripts'))
from svg_pipeline import convert, validate_output

class SVGPipelineTest(unittest.TestCase):
    def svg(self,body): return f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">{body}</svg>'
    def test_shapes_and_stroke(self):
        for shape in ['<circle cx="12" cy="12" r="8" fill="none" stroke="black" stroke-width="2"/>','<line x1="2" y1="2" x2="20" y2="20" stroke="black" stroke-linecap="round" stroke-width="2"/>','<rect x="2" y="2" width="16" height="16" rx="2"/>','<polyline points="2,2 10,20 20,2" fill="none" stroke="black" stroke-linejoin="round"/>']:
            with self.subTest(shape=shape):
                result=convert(self.svg(shape))
                self.assertNotIn('stroke=',result)
                self.assertIn('fill="currentColor"',result)
                self.assertIn('viewBox="0 0 24 24"',result)
                validate_output(result)
    def test_unsafe(self):
        for body in ['<script>alert(1)</script>','<foreignObject/>','<path d="M0 0h1v1z" onclick="bad()"/>','<path d="M0 0h1v1z" fill="url(https://example.com/a)"/>','<path d="M0 0h1v1z" style="fill:red"/>','<image href="https://example.com/a"/>','<use href="#shape"/>','<mask/>']:
            with self.subTest(body=body):
                with self.assertRaises(ValueError): convert(self.svg(body))
    def test_external_entity(self):
        with self.assertRaises(ValueError): convert('<!DOCTYPE svg [<!ENTITY x SYSTEM "file:///etc/passwd">]>'+self.svg('&x;'))
    def test_empty(self):
        with self.assertRaises(ValueError): convert(self.svg(''))
    def test_numeric_dimensions(self):
        result=convert('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"><path d="M1 1h4v4z"/></svg>')
        self.assertIn('viewBox="0 0 24 24"',result)
    def test_bad_viewbox(self):
        for value in ['x', '0 0 0 24', '0 0 -1 24', '0 0 nan 24']:
            with self.assertRaises(ValueError): convert(self.svg('<path d="M1 1h4v4z"/>').replace('0 0 24 24',value))
    def test_foreign_namespace(self):
        with self.assertRaises(ValueError): convert(self.svg('<path xmlns="https://example.com" d="M1 1h4v4z"/>'))
    def test_unsupported_output(self):
        with self.assertRaises(ValueError): validate_output(self.svg('<circle cx="12" cy="12" r="8"/>'))

if __name__=='__main__': unittest.main()
