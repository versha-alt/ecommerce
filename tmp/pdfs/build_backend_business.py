from pathlib import Path
import re, html
from reportlab.pdfgen import canvas
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, PageBreak, KeepTogether
from reportlab.lib import colors
from reportlab.lib.styles import ParagraphStyle
from reportlab.lib.enums import TA_LEFT
from reportlab.lib.pagesizes import A4
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from pypdf import PdfReader

root = Path(r"F:\Versha Ekrocx\live-projects\ecomm")
source = root / "docs/Backend-Functional-Specification.md"
destination = root / "docs/Backend-Functional-Specification.pdf"
text = source.read_text(encoding="utf-8-sig").replace("\r\n", "\n")
source.write_text(text, encoding="utf-8")
parts = re.split(r"(?m)^# Page (\d+) - (.+)\n", text)
assert len(parts) == 25, len(parts)
pages = [(parts[i], parts[i+1], parts[i+2]) for i in range(1,len(parts),3)]
assert len(pages) == 8
pdfmetrics.registerFont(TTFont("Business", r"C:\Windows\Fonts\arial.ttf"))
pdfmetrics.registerFont(TTFont("BusinessBold", r"C:\Windows\Fonts\arialbd.ttf"))
pdfmetrics.registerFontFamily("Business", normal="Business", bold="BusinessBold", italic="Business", boldItalic="BusinessBold")

def inline(value):
    value = html.escape(value)
    return re.sub(r"\*\*(.*?)\*\*", r"<b>\1</b>", value)

def decoration(c, doc):
    c.saveState()
    w,h=A4
    c.setFillColor(colors.HexColor("#203546"))
    c.setFont("BusinessBold", 8)
    c.drawString(44,h-28,"E-COMMERCE PLATFORM")
    c.setFont("Business",8)
    c.drawRightString(w-44,h-28,"BACKEND FUNCTIONAL SPECIFICATION")
    c.setStrokeColor(colors.HexColor("#CCD5DD"))
    c.line(44,35,w-44,35)
    c.setFont("Business",7.5)
    c.setFillColor(colors.HexColor("#536573"))
    c.drawString(44,23,"Business Edition - Requirements for an AI Builder")
    c.drawRightString(w-44,23,f"{doc.page} / 8")
    c.restoreState()

for size in [10.8,10.6,10.4,10.2,10.0,9.8]:
    body=ParagraphStyle("Body",fontName="Business",fontSize=size,leading=size*1.20,spaceAfter=4,textColor=colors.HexColor("#263744"))
    heading=ParagraphStyle("Heading",fontName="BusinessBold",fontSize=11,leading=14,spaceBefore=5,spaceAfter=4,textColor=colors.HexColor("#203546"),keepWithNext=True)
    title=ParagraphStyle("Title",fontName="BusinessBold",fontSize=18,leading=22,spaceAfter=9,textColor=colors.HexColor("#203546"))
    label=ParagraphStyle("Label",fontName="BusinessBold",fontSize=8,leading=10,spaceAfter=5,textColor=colors.HexColor("#657E8C"))
    tablebody=ParagraphStyle("TableBody",parent=body,fontSize=9,leading=11.5,spaceAfter=0)
    flows=[]
    for number,page_title,content in pages:
        if flows: flows.append(PageBreak())
        flows.append(Paragraph(f"REQUIREMENTS / {number}",label))
        flows.append(Paragraph(inline(page_title),title))
        lines=content.strip().splitlines()
        i=0
        while i<len(lines):
            line=lines[i].strip()
            if not line:
                i+=1;continue
            if line.startswith("|"):
                rows=[]
                while i<len(lines) and lines[i].strip().startswith("|"):
                    cells=[x.strip() for x in lines[i].strip().strip("|").split("|")]
                    if not all(re.fullmatch(r":?-+:?",x.replace(" ","")) for x in cells):
                        rows.append([Paragraph(inline(x),tablebody) for x in cells])
                    i+=1
                count=len(rows[0])
                width=A4[0]-88
                widths=([width*.24,width*.76] if count==2 else [width*.32,width*.34,width*.34])
                if count==3 and rows[0][0].getPlainText()=='Phase': widths=[width*.13,width*.67,width*.20]
                tab=Table(rows,colWidths=widths,repeatRows=1,hAlign="LEFT")
                tab.setStyle(TableStyle([
                    ("BACKGROUND",(0,0),(-1,0),colors.HexColor("#E8EEF2")),
                    ("VALIGN",(0,0),(-1,-1),"TOP"),
                    ("LEFTPADDING",(0,0),(-1,-1),7),
                    ("RIGHTPADDING",(0,0),(-1,-1),7),
                    ("TOPPADDING",(0,0),(-1,-1),4),
                    ("BOTTOMPADDING",(0,0),(-1,-1),4),
                    ("LINEBELOW",(0,0),(-1,0),.6,colors.HexColor("#BCCBD5")),
                    ("LINEBELOW",(0,1),(-1,-1),.3,colors.HexColor("#DFE5E9")),
                ]))
                flows.append(tab);flows.append(Spacer(1,5));continue
            if line.startswith("## "):
                flows.append(Paragraph(inline(line[3:]),heading))
            elif re.match(r"\d+\. ",line):
                flows.append(Paragraph(inline(line),body))
            else:
                flows.append(Paragraph(inline(line),body))
            i+=1
    doc=SimpleDocTemplate(str(destination),pagesize=A4,rightMargin=44,leftMargin=44,topMargin=48,bottomMargin=46,
                          title="E-commerce Platform: Backend Functional Specification (Business Edition)",
                          author="Business Requirements",subject="Eight-page requirements for an AI builder")
    doc.build(flows,onFirstPage=decoration,onLaterPages=decoration)
    reader=PdfReader(destination)
    if len(reader.pages)==8:
        print(f"Created eight pages at {size} pt")
        break
else: raise RuntimeError("Content exceeds eight pages")
for i,page in enumerate(reader.pages,1):
    extracted=page.extract_text() or ""
    print(f"Page {i}: {len(extracted.split())} extracted words")
    assert f"REQUIREMENTS / {i}" in extracted, (i,extracted[:100])
combined="\n".join(p.extract_text() or "" for p in reader.pages)
for number in range(1,29):
    assert f"R{number:02}" in combined, number
print("All 28 requirement references verified")

