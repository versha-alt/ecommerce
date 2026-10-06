from pathlib import Path
p=Path('apps/storefront/app/layout.tsx');s=p.read_text();s="import Analytics from '@/components/analytics';\n"+s;s=s.replace('<Footer/>','<Footer/><Analytics settings={data.settings}/>');p.write_text(s)
