from pathlib import Path
import re,colorsys
p=Path('apps/admin/src/main.tsx');s=p.read_text(encoding='utf-8');old='<button className="brand-lockup" onClick={()=>go(\'dashboard\')}><span className="brand-icon"><Leaf size={23}/></span><span>olive<span className="brand-sub">COMMERCE ADMIN</span></span></button>';new='<button className="brand-lockup leekav-admin-brand" aria-label="LEEKAV dashboard" onClick={()=>go(\'dashboard\')}><span className="leekav-admin-logo"><img src="/brand/leekav-logo.png" alt="LEEKAV"/></span><span className="brand-sub">COMMERCE ADMIN</span></button>';assert old in s;s=s.replace(old,new).replace('<div className="brand-lockup light"><span className="brand-icon"><Leaf/></span><span>olive<span className="brand-sub">COMMERCE ADMIN</span></span></div>','<div className="brand-lockup light leekav-admin-brand"><span className="leekav-admin-logo"><img src="/brand/leekav-logo.png" alt="LEEKAV"/></span><span className="brand-sub">COMMERCE ADMIN</span></div>').replace('<span className="store-monogram">O</span>','<span className="store-monogram">L</span>').replace("??'Olive Electronics'","??'LEEKAV'").replace('Olive Commerce</span>','LEEKAV Commerce</span>');p.write_text(s,encoding='utf-8')
p=Path('apps/admin/index.html');s=p.read_text().replace('<title>E-commerce Admin</title>','<title>LEEKAV | Commerce Admin</title>');p.write_text(s)
p=Path('apps/storefront/app/globals.css');s=p.read_text(encoding='utf-8').replace('--coral:#c24128;--coral-dark:#a93220','--coral:#7355d5;--coral-dark:#593cb5').replace('#ae351f','#6245bc').replace('#a9322026','#593cb526').replace('#a9322038','#593cb538').replace('#c2412810','#7355d510');p.write_text(s,encoding='utf-8')
p=Path('apps/admin/src/styles.css');s=p.read_text(encoding='utf-8')
def recolor(m):
 v=m.group(1)
 if len(v) not in (6,8):return m.group(0)
 h,l,sat=colorsys.rgb_to_hls(*[int(v[i:i+2],16)/255 for i in (0,2,4)])
 if 70<=h*360<=165:
  rgb=colorsys.hls_to_rgb(212/360,l,max(sat,.22) if l>.75 else max(sat,.35));return '#'+''.join(f'{round(x*255):02x}' for x in rgb)+v[6:]
 return m.group(0)
p.write_text(re.sub(r'#([a-fA-F0-9]{8}|[a-fA-F0-9]{6})(?![a-fA-F0-9])',recolor,s),encoding='utf-8')
