#!/usr/bin/env python3
"""Render a Markdown elicitation document to an institutional-style PDF.

Usage: python3 render_pdf.py <input.md> <output.pdf>
"""
import sys
from pathlib import Path

import markdown
from weasyprint import HTML

CSS = """
@page {
    size: A4;
    margin: 2.2cm 2.4cm 2.4cm 2.4cm;
    @bottom-center {
        content: "Alcaldía Distrital de Santa Marta — Documento de levantamiento de información";
        font-size: 7.5pt; color: #6b7280;
    }
    @bottom-right {
        content: "Pág. " counter(page) " / " counter(pages);
        font-size: 7.5pt; color: #6b7280;
    }
}
* { box-sizing: border-box; }
body {
    font-family: "Liberation Serif", "DejaVu Serif", Georgia, serif;
    font-size: 10.8pt; line-height: 1.45; color: #1f2937;
}
h1 {
    font-family: "Liberation Sans", "DejaVu Sans", Arial, sans-serif;
    font-size: 16pt; color: #0b3d66; margin: 0 0 2pt 0; line-height: 1.2;
    border-bottom: 2.5pt solid #0b3d66; padding-bottom: 6pt;
}
h2 {
    font-family: "Liberation Sans", "DejaVu Sans", Arial, sans-serif;
    font-size: 12pt; color: #0b3d66; margin: 16pt 0 4pt 0;
    border-bottom: 0.6pt solid #c7d2da; padding-bottom: 2pt;
}
h3 {
    font-family: "Liberation Sans", "DejaVu Sans", Arial, sans-serif;
    font-size: 10.6pt; color: #134e7a; margin: 11pt 0 3pt 0;
}
p { margin: 5pt 0; text-align: justify; }
strong { color: #0b3d66; }
ul, ol { margin: 4pt 0 6pt 0; padding-left: 18pt; }
li { margin: 2.5pt 0; }
table {
    width: 100%; border-collapse: collapse; margin: 8pt 0; font-size: 9.6pt;
}
th {
    background: #0b3d66; color: #fff; text-align: left; padding: 5pt 7pt;
    font-family: "Liberation Sans", "DejaVu Sans", Arial, sans-serif; font-size: 9pt;
}
td { border: 0.5pt solid #c7d2da; padding: 5pt 7pt; vertical-align: top; }
tr:nth-child(even) td { background: #f3f6f9; }
blockquote {
    margin: 8pt 0; padding: 7pt 11pt; background: #eef4f9;
    border-left: 3pt solid #0b3d66; color: #334155; font-size: 9.8pt;
}
code { background: #eef2f5; padding: 1pt 3pt; border-radius: 2pt; font-size: 9.2pt; }
hr { border: none; border-top: 0.6pt solid #c7d2da; margin: 12pt 0; }
.firma { margin-top: 26pt; }
"""


def main() -> int:
    if len(sys.argv) != 3:
        print("Usage: render_pdf.py <input.md> <output.pdf>", file=sys.stderr)
        return 2
    src, dst = Path(sys.argv[1]), Path(sys.argv[2])
    md_text = src.read_text(encoding="utf-8")
    html_body = markdown.markdown(
        md_text, extensions=["tables", "fenced_code", "sane_lists", "attr_list"]
    )
    html_doc = (
        f"<!DOCTYPE html><html lang='es'><head><meta charset='utf-8'>"
        f"<style>{CSS}</style></head><body>{html_body}</body></html>"
    )
    HTML(string=html_doc, base_url=str(src.parent)).write_pdf(str(dst))
    print(f"PDF generado: {dst}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
