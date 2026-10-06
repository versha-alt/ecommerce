from pathlib import Path
p=Path('apps/storefront/app/api/store/[...path]/route.ts');s=p.read_text();s=s.replace("if(!isRead&&request.headers.get('origin')!==request.nextUrl.origin)", "const expectedOrigin=process.env.NEXT_PUBLIC_SITE_URL||`${request.nextUrl.protocol}//${request.headers.get('host')}`;\n if(!isRead&&request.headers.get('origin')!==expectedOrigin)");p.write_text(s)
