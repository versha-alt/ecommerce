from pathlib import Path
p=Path('tmp/storefront-smoke.mjs');s=p.read_text(encoding='utf-8-sig');s=s.replace("const product=await page.locator('.product-card h3').first().innerText();await page.locator('.product-card h3').first().click();", "const product='Midea 8 kg front load washer';await page.getByRole('heading',{name:product,exact:true}).first().click();")
s=s.replace("if(r.status()!==200)throw Error(path+' returned '+r.status());", "if(r.status()!==200)throw Error(path+' returned '+r.status());await page.locator('#main').waitFor();console.log('page',path);")
p.write_text(s,encoding='utf-8')
