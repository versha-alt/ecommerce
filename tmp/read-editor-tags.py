from pathlib import Path
s=Path('apps/admin/src/main.tsx').read_text();import re
for m in re.finditer(r'<textarea.*?/>',s):print(m.group())
i=s.find('function cell');print(s[i+850:i+1450])
