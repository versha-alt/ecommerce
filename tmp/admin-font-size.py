from pathlib import Path
import re
p=Path('apps/admin/src/styles.css')
s=p.read_text(encoding='utf-8')
def increase(match):
 size=float(match.group(1))
 new=size+1 if size<20 else size+2 if size<40 else size
 return 'font-size:'+format(new,'g')+'px'
s,count=re.subn(r'font-size:\s*(\d+(?:\.\d+)?)px',increase,s)
p.write_text(s,encoding='utf-8')
print(f'Updated {count} font-size declarations across desktop and responsive admin styles.')
