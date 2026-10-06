from pathlib import Path
import re,colorsys
p=Path('apps/storefront/components/store.tsx');s=p.read_text(encoding='utf-8');s=s.replace('<Link href="/" className="store-logo" aria-label="Olive home"><span><Leaf size={24}/></span>olive<span className="logo-caption">HOME & LIVING</span></Link>','<Link href="/" className="store-logo leekav-logo" aria-label="LEEKAV home"><img src="/brand/leekav-logo.png" alt="LEEKAV" width={2020} height={778}/></Link>').replace('<Link href="/" className="footer-logo"><Leaf size={26}/>olive</Link>','<Link href="/" className="footer-logo leekav-logo" aria-label="LEEKAV home"><img src="/brand/leekav-logo.png" alt="LEEKAV" width={2020} height={778}/></Link>').replace('aria-label="Follow Olive"','aria-label="Follow LEEKAV"').replace("{settings.store_name||'Olive'}",'LEEKAV');p.write_text(s,encoding='utf-8')
for p in list(Path('apps/storefront/app').rglob('*.tsx'))+list(Path('apps/storefront/components').glob('*.tsx')):
 s=p.read_text(encoding='utf-8');s=s.replace('THE OLIVE WAY','THE LEEKAV WAY').replace('YOUR OLIVE ACCOUNT','YOUR LEEKAV ACCOUNT').replace('your Olive account','your LEEKAV account').replace('the Olive team','the LEEKAV team').replace('Olive brings','LEEKAV brings').replace('OLIVE COLLECTION','LEEKAV COLLECTION').replace('Olive |','LEEKAV |').replace('%s | Olive','%s | LEEKAV');
 if p.name=='global-error.tsx':s=s.replace('>olive<','>LEEKAV<').replace('#58633e','#0755d5').replace('#273124','#102943').replace('#f7f7f1','#f4f8fc')
 p.write_text(s,encoding='utf-8')
p=Path('apps/storefront/app/globals.css');s=p.read_text(encoding='utf-8')
def recolor(m):
 value=m.group(1)
 if len(value) not in (6,8):return m.group(0)
 rgb=[int(value[i:i+2],16)/255 for i in (0,2,4)];h,l,sat=colorsys.rgb_to_hls(*rgb)
 if 70<=h*360<=165:
  rgb=colorsys.hls_to_rgb(212/360,l,max(sat,.22) if l>.75 else max(sat,.35))
  return '#'+''.join(f'{round(x*255):02x}' for x in rgb)+value[6:]
 return m.group(0)
s=re.sub(r'#([a-fA-F0-9]{8}|[a-fA-F0-9]{6})(?![a-fA-F0-9])',recolor,s)
p.write_text(s,encoding='utf-8')
