from pathlib import Path
p=Path('tmp/frontend-visible-check.mjs');s=p.read_text();s=s.replace("locator('input[name=", "locator('.auth-form input[name=");p.write_text(s)
