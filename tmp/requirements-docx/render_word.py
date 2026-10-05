from pathlib import Path
import win32com.client
import pypdfium2 as pdfium
root=Path(r'F:\Versha Ekrocx\live-projects\ecomm')
source=root/'docs/Ecommerce Consolidated Project Requirements.docx'
out=root/'tmp/requirements-docx/render';out.mkdir(parents=True,exist_ok=True)
word=win32com.client.DispatchEx('Word.Application');word.Visible=False;word.DisplayAlerts=0;word.AutomationSecurity=3
try:
    document=word.Documents.Open(str(source),ReadOnly=True,AddToRecentFiles=False)
    document.Repaginate()
    print('Word page count:',document.ComputeStatistics(2))
    document.ExportAsFixedFormat(str(out/'requirements.pdf'),17)
    document.Close(False)
finally:
    try: word.Quit()
    except Exception: pass
pdf=pdfium.PdfDocument(str(out/'requirements.pdf'))
print('Rendered pages:',len(pdf))
for index in range(len(pdf)):
    page=pdf[index]
    page.render(scale=1.5).to_pil().save(out/f'page-{index+1}.png')
