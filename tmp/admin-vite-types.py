from pathlib import Path
import json
p=Path('apps/admin/tsconfig.json');data=json.loads(p.read_text());data['compilerOptions']['types']=['vite/client'];p.write_text(json.dumps(data,indent=2)+'\n')
