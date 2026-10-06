from pathlib import Path
p=Path('tmp/storefront-smoke.mjs');s=p.read_text();s=s.replace("const product='Midea 8 kg front load washer'", "const product='LG NeoChef microwave'");p.write_text(s)
p=Path('.gitignore');s=p.read_text();s+='\napps/storefront/.next/\n*.tsbuildinfo\n';p.write_text(s)
