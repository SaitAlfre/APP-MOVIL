#!/usr/bin/env python3
"""Gráfico derivado del resultado final, no métrica Scrum. PNG requiere Pillow."""
from pathlib import Path
import html
import json

ROOT = Path(__file__).resolve().parents[2]
DATA = ROOT / 'docs/pruebas/ejecuciones/resultados-2026-10-22.json'
OUT = ROOT / 'docs/academico/graficos/pruebas-reales-2026-10-22'


def main():
    from PIL import Image, ImageDraw, ImageFont
    suites = json.loads(DATA.read_text())['final_suites']
    width, height = 900, 410
    img = Image.new('RGB', (width, height), '#f8fafc')
    draw = ImageDraw.Draw(img)
    font_path = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf'
    def font(n):
        try:
            return ImageFont.truetype(font_path, n)
        except OSError:
            return ImageFont.load_default()
    svg = [f'<svg xmlns="http://www.w3.org/2000/svg" width="{width}" height="{height}" viewBox="0 0 {width} {height}">',
           '<title>Ejecuciones reales Ecolactea, 22-10-2026</title>',
           '<desc>Última ejecución por suite: JVM 256, PHP 289 y Firestore 9, todas Passed. No es cobertura de requisitos ni velocidad Scrum.</desc>',
           '<rect width="900" height="410" fill="#f8fafc"/>']
    def text(x, y, value, size=18, color='#0f172a'):
        draw.text((x,y),value,font=font(size),fill=color)
        svg.append(f'<text x="{x}" y="{y+size}" font-family="DejaVu Sans, sans-serif" font-size="{size}" fill="{color}">{html.escape(value)}</text>')
    text(32,24,'Pruebas ejecutadas: resultado final por suite',25)
    text(32,62,'Ecolactea Digital · 22 de octubre de 2026 · entorno aislado',16,'#475569')
    for i, name in enumerate(['JVM','PHP','Firestore']):
        s=suites[name]; y=115+i*72; length=520*s['passed']/300
        text(32,y,name,20)
        draw.rounded_rectangle((165,y,165+length,y+35),radius=4,fill='#0f766e')
        svg.append(f'<rect x="165" y="{y}" width="{length:.2f}" height="35" rx="4" fill="#0f766e"/>')
        text(180+length,y+4,f"{s['passed']}/{s['total']} Passed",18)
    text(165,333,'0 fallos y 0 omitidos en estas tres ejecuciones finales.',17)
    text(32,370,'No suma reintentos. No acredita 100 % de CA ni aceptación del beneficiario.',15,'#475569')
    svg.append('</svg>')
    OUT.with_suffix('.svg').write_text('\n'.join(svg)+'\n')
    img.save(OUT.with_suffix('.png'))
    print('Gráfico PNG/SVG derivado de resultados JSON; no datos simulados.')


if __name__ == '__main__':
    main()
