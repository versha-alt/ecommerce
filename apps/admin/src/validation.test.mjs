import assert from 'node:assert/strict';
import { test } from 'node:test';
import { readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { pathToFileURL } from 'node:url';
import { transformSync } from 'esbuild';
const require=createRequire(import.meta.url);
const source=readFileSync(new URL('./validation.tsx',import.meta.url),'utf8');
const output=transformSync(source,{loader:'tsx',format:'esm',jsx:'transform',target:'es2022'}).code.replace(/from ['"]react['"]/g,`from '${pathToFileURL(require.resolve('react')).href}'`);
const {readResponse,request,applyInputRules}=await import(`data:text/javascript;base64,${Buffer.from(output).toString('base64')}`);
globalThis.window=new EventTarget();globalThis.sessionStorage={removeItem:()=>{}};

test('API validation errors retain field details and readable messages',async()=>{
 let detail;window.addEventListener('submission-errors',event=>{detail=event.detail},{once:true});
 await assert.rejects(readResponse(new Response(JSON.stringify({message:'The given data was invalid.',errors:{slug:['This slug already exists.']}}),{status:422})),/This slug already exists/);
 assert.deepEqual(detail,{slug:['This slug already exists.']});
});
test('server errors and invalid response bodies produce safe messages',async()=>{
 await assert.rejects(readResponse(new Response(JSON.stringify({message:'SQL password and internal stack'}),{status:500})),/The server could not complete/);
 await assert.rejects(readResponse(new Response('<html>proxy error</html>',{status:200})),/unexpected response/);
});
test('expired sessions trigger login without exposing a generic fetch failure',async()=>{
 let expired=false;window.addEventListener('session-expired',()=>{expired=true},{once:true});
 await assert.rejects(readResponse(new Response('{}',{status:401})));assert.equal(expired,true);
});
test('network failures explain recovery and preserve rejection',async()=>{
 const previous=globalThis.fetch;globalThis.fetch=async()=>{throw new TypeError('fetch failed');};
 try{await assert.rejects(request('/api/v1/products',{}),/Check your connection/);}finally{globalThis.fetch=previous;}
});
test('input rules allow signed inventory adjustments and protect integer, VAT and slug fields',()=>{
 assert.equal(applyInputRules({key:'quantity',label:'Adjustment',type:'number'}).min,undefined);
 assert.equal(applyInputRules({key:'stock',label:'Stock',type:'number'}).step,1);
 assert.equal(applyInputRules({key:'rate',label:'VAT',type:'number'}).max,100);
 assert.ok(applyInputRules({key:'slug',label:'Slug'}).pattern);
 assert.equal(applyInputRules({key:'smtp_password',label:'SMTP password',type:'password'}).minLength,undefined);
});
