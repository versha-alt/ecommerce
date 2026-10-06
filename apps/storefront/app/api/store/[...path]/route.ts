import {cookies} from 'next/headers';
import {NextRequest,NextResponse} from 'next/server';
import {apiBase} from '@/lib/store';
async function proxy(request:NextRequest,{params}:{params:Promise<{path:string[]}>}){
 const path=(await params).path.join('/');const isRead=request.method==='GET';
 const allowed=isRead?/^(catalog|account|orders\/[a-f0-9-]+|product-reviews\/[a-f0-9-]+)$/.test(path):/^(register|login|logout|checkout|quote|profile|reviews|returns|enquiries|newsletter|orders\/[a-f0-9-]+\/cancel)$/.test(path);
 if(!allowed)return NextResponse.json({message:'Not found.'},{status:404});
 const expectedOrigin=process.env.NEXT_PUBLIC_SITE_URL||`${request.nextUrl.protocol}//${request.headers.get('host')}`;
 if(!isRead&&request.headers.get('origin')!==expectedOrigin)return NextResponse.json({message:'Invalid request origin.'},{status:403});
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
