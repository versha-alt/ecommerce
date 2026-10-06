from pathlib import Path
p=Path('tmp/storefront-smoke.mjs');s=p.read_text();s=s.replace("await page.getByRole('link',{name:'Shop all products',exact:true}).click()", "await page.getByRole('navigation').getByRole('link',{name:'Shop all products',exact:true}).click()");p.write_text(s)
p=Path('scripts/build-admin.ps1');s=p.read_text();s=s.replace('exit $LASTEXITCODE',"if($LASTEXITCODE -ne 0){exit $LASTEXITCODE}\nnpm.cmd run build -w '@ecomm/storefront'\nexit $LASTEXITCODE");p.write_text(s)
