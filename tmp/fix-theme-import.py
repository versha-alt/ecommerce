from pathlib import Path
p=Path('apps/storefront/app/globals.css');s=p.read_text();s=s[s.index(':root{'):];p.write_text(s)
