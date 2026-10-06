from pathlib import Path
import re,colorsys
p=Path('apps/admin/src/main.tsx');s=p.read_text(encoding='utf-8');s=s.replace('stopColor="#738448"','stopColor="var(--olive)"').replace('stroke="#eef0e9"','stroke="var(--line)"').replace('stroke="#75864b"','stroke="var(--olive)"').replace('fill="#52662f"','fill="var(--olive-dark)"').replace('#536438','#0755d5');p.write_text(s,encoding='utf-8')
p=Path('apps/admin/src/styles.css');s=p.read_text(encoding='utf-8')
def recolor(m):
 v=m.group(1)
 if len(v) not in (6,8):return m.group(0)
 h,l,sat=colorsys.rgb_to_hls(*[int(v[i:i+2],16)/255 for i in (0,2,4)])
 if 55<=h*360<=170:
  rgb=colorsys.hls_to_rgb(212/360,l,max(sat,.22) if l>.75 else max(sat,.35));return '#'+''.join(f'{round(x*255):02x}' for x in rgb)+v[6:]
 return m.group(0)
p.write_text(re.sub(r'#([a-fA-F0-9]{8}|[a-fA-F0-9]{6})(?![a-fA-F0-9])',recolor,s),encoding='utf-8')
