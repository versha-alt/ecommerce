from pathlib import Path
import json
root=Path('apps/storefront')
for folder in ['app','components','lib','public','app/products/[slug]','app/brands/[slug]','app/categories/[slug]','app/cart','app/checkout','app/account','app/orders/[id]','app/[slug]','app/api/store/[...path]']:(root/folder).mkdir(parents=True,exist_ok=True)
(root/'tsconfig.json').write_text(json.dumps({'compilerOptions':{'target':'ES2020','lib':['dom','dom.iterable','esnext'],'allowJs':True,'skipLibCheck':True,'strict':True,'noEmit':True,'esModuleInterop':True,'module':'esnext','moduleResolution':'bundler','resolveJsonModule':True,'isolatedModules':True,'jsx':'react-jsx','incremental':True,'plugins':[{'name':'next'}],'paths':{'@/*':['./*']}},'include':['next-env.d.ts','**/*.ts','**/*.tsx','.next/types/**/*.ts'],'exclude':['node_modules']},indent=2))
(root/'next.config.mjs').write_text('''const api=process.env.LARAVEL_API_URL||'http://127.0.0.1:8000';
export default {images:{remotePatterns:[{protocol:'https',hostname:'**'}]},async rewrites(){return [{source:'/api/v1/media/:path*',destination:`${api}/api/v1/media/:path*`}]}};
''')
(root/'.env.example').write_text('LARAVEL_API_URL=http://127.0.0.1:8000\nNEXT_PUBLIC_SITE_URL=http://127.0.0.1:3000\n')
(root/'lib/store.ts').write_text('''export type Product={id:string;name:string;slug:string;sku:string;type:string;price:number;sale_price?:number|null;stock:number;reserved:number;image?:string;gallery_images?:string[];brand_id?:string;category_ids:string[];description?:string;specifications?:string;warranty?:string;manual?:string;seo_title?:string;seo_description?:string;created_at:string};
export type Catalog={products:Product[];brands:any[];categories:any[];banners:any[];pages:any[];delivery_zones:any[];locations:any;payment_methods:any[];settings:Record<string,string>};
export const apiBase=()=>process.env.LARAVEL_API_URL||'http://127.0.0.1:8000';
export async function catalog():Promise<Catalog>{const response=await fetch(`${apiBase()}/api/v1/store/catalog`,{cache:'no-store',signal:AbortSignal.timeout(15000)});if(!response.ok)throw new Error('The store is temporarily unavailable. Please try again shortly.');return response.json();}
export const money=(value:number)=>'KSh '+Number(value||0).toLocaleString('en-KE',{maximumFractionDigits:2});
export const price=(p:Product)=>p.sale_price??p.price;
export const slugify=(value:string)=>value.normalize('NFD').replace(/[\\u0300-\\u036f]/g,'').toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
export async function storeRequest(path:string,method='GET',body?:any,key?:string){let response:Response;try{response=await fetch(`/api/store/${path}`,{method,headers:{'Content-Type':'application/json',...(key?{'Idempotency-Key':key}:{})},body:body?JSON.stringify(body):undefined,signal:AbortSignal.timeout(20000)});}catch{throw new Error('Unable to connect. Please try again.');}const data=await response.json().catch(()=>({message:'Unexpected response from the store.'}));if(!response.ok)throw new Error(Object.values(data.errors??{}).flat().join(' ')||data.message||'The request could not be completed.');return data;}
''')
(root/'app/api/store/[...path]/route.ts').write_text('''import {cookies} from 'next/headers';
import {NextRequest,NextResponse} from 'next/server';
import {apiBase} from '@/lib/store';
async function proxy(request:NextRequest,{params}:{params:Promise<{path:string[]}>}){
 const path=(await params).path.join('/');const isRead=request.method==='GET';
 const allowed=isRead?/^(catalog|account|orders\\/[a-f0-9-]+|product-reviews\\/[a-f0-9-]+)$/.test(path):/^(register|login|logout|checkout|quote|profile|reviews|returns|enquiries|newsletter|orders\\/[a-f0-9-]+\\/cancel)$/.test(path);
 if(!allowed)return NextResponse.json({message:'Not found.'},{status:404});
 if(!isRead&&request.headers.get('origin')!==request.nextUrl.origin)return NextResponse.json({message:'Invalid request origin.'},{status:403});
 const jar=await cookies();const token=jar.get('olive-customer')?.value;const headers:Record<string,string>={Accept:'application/json','Content-Type':'application/json'};if(token)headers.Authorization=`Bearer ${token}`;
 const key=request.headers.get('idempotency-key');if(key)headers['Idempotency-Key']=key;
 const endpoint=path.startsWith('product-reviews/')?`products/${path.split('/')[1]}/reviews`:`store/${path}`;
 try{const response=await fetch(`${apiBase()}/api/v1/${endpoint}${request.nextUrl.search}`,{method:request.method,headers,body:isRead?undefined:await request.text(),cache:'no-store',signal:AbortSignal.timeout(18000)});const body=await response.json();
 const session=body.token;delete body.token;const result=NextResponse.json(body,{status:response.status});
 if(session)result.cookies.set('olive-customer',session,{httpOnly:true,secure:process.env.NODE_ENV==='production'&&request.nextUrl.protocol==='https:',sameSite:'lax',path:'/',maxAge:path==='checkout'?86400:604800});
 if(path==='logout'||response.status===401&&!['login','register','account'].includes(path))result.cookies.delete('olive-customer');return result;
 }catch{return NextResponse.json({message:'The store could not be reached. Please try again.'},{status:503});}
}
export const GET=proxy;export const POST=proxy;export const PATCH=proxy;
''')
(root/'app/error.tsx').write_text('''"use client";
export default function ErrorPage({reset}:{reset:()=>void}){return <main className="container centered"><h1>Let us try that again.</h1><p>We could not load this page. Please try again shortly.</p><button className="button" onClick={reset}>Try again</button></main>}
''')
(root/'app/not-found.tsx').write_text('''import Link from 'next/link';
export default function NotFound(){return <main className="container centered"><span className="eyebrow">404</span><h1>This page has moved.</h1><p>Discover something useful in our collection instead.</p><Link href="/products" className="button">Explore products</Link></main>}
''')
(root/'app/robots.ts').write_text('''export default function robots(){return {rules:{userAgent:'*',allow:'/',disallow:['/account','/checkout','/cart','/orders','/api/']},sitemap:(process.env.NEXT_PUBLIC_SITE_URL||'http://127.0.0.1:3000')+'/sitemap.xml'}}
''')
(root/'app/sitemap.ts').write_text('''import {catalog,slugify} from '@/lib/store';
export default async function sitemap(){const data=await catalog();const base=process.env.NEXT_PUBLIC_SITE_URL||'http://127.0.0.1:3000';return ['','/products',...data.products.map(p=>'/products/'+p.slug),...data.brands.map(b=>'/brands/'+slugify(b.name)),...data.categories.map(c=>'/categories/'+c.slug),...data.pages.map(p=>'/'+p.slug)].map(path=>({url:base+path,lastModified:new Date()}));}
''')
