import fs from 'node:fs/promises';
import assert from 'node:assert/strict';
const base='http://127.0.0.1:8000/api/v1';
const main=await fs.readFile('apps/admin/src/main.tsx','utf8');
const password=main.match(/setPassword\(import\.meta\.env\.DEV\?'([^']+)'/)[1];
let token='';
async function api(path,method='GET',body){const response=await fetch(base+'/'+path,{method,headers:{Accept:'application/json',...(token?{Authorization:'Bearer '+token}:{}),...(body&&! (body instanceof FormData)?{'Content-Type':'application/json'}:{})},body:body?(body instanceof FormData?body:JSON.stringify(body)):undefined});const data=await response.json();if(!response.ok)throw new Error(path+' '+response.status+' '+JSON.stringify(data));return data;}
token=(await api('auth/login','POST',{email:'admin@leekav.com',password})).token;
const workspace=await api('workspace');const products=workspace.records.products;
await fs.writeFile('tmp/product-images-apply-before.json',JSON.stringify(products,null,2));
const selections=JSON.parse(await fs.readFile('tmp/product-photo-selections.json','utf8'));
function category(p){const name=p.name.toLowerCase();if(name.includes('steam'))return 'steam';if(name.includes('pressure'))return 'pressure-washer';if(name.includes('vacuum'))return 'vacuum';if(name.includes('television')||name.includes('smart tv'))return 'tv';if(name.includes('air conditioner'))return 'ac';if(name.includes('freezer'))return 'freezer';if(name.includes('refrigerator'))return 'fridge';if(name.includes('cooker')||name.includes('cookers'))return 'cooker';if(name.includes('kettle'))return 'kettle';if(name.includes('microwave'))return 'microwave';if(name.includes('dryer'))return 'dryer';if(name.includes('top-load'))return 'top-washer';if(name.includes('washer')||name.includes('washing machine'))return 'front-washer';throw new Error('Unmapped product '+p.sku);}
for(const p of products)assert.ok(selections[category(p)]);
for(const [type,selection] of Object.entries(selections)){
 selection.urls=[];
 for(const file of selection.assets){const data=new FormData();data.append('file',new Blob([await fs.readFile('tmp/product-photo-prepared/'+file)],{type:'image/jpeg'}),file);selection.urls.push((await api('uploads','POST',data)).url);}
 console.log('Uploaded '+type+' main and gallery images.');
}
await fs.writeFile('tmp/product-photo-uploaded.json',JSON.stringify(selections,null,2));
const ignore=new Set(['image','gallery_images','version','updated_at']);
function remaining(row){return Object.fromEntries(Object.entries(row).filter(([key])=>!ignore.has(key)));}
const mappings=[];
for(const p of products){const type=category(p),set=selections[type];const input={...p,image:set.urls[0],gallery_images:set.urls.slice(1),category_ids:p.direct_category_ids??p.category_ids};const saved=await api('products/'+p.id,'PATCH',input);assert.deepEqual(remaining(saved),remaining(p),'Unrelated data changed for '+p.sku);mappings.push({id:p.id,sku:p.sku,category:type,image:saved.image,gallery_images:saved.gallery_images});}
const after=(await api('workspace')).records.products;
for(const row of after){const before=products.find(p=>p.id===row.id);assert.deepEqual(remaining(row),remaining(before));assert.equal(row.gallery_images.length,2);assert.equal(new Set([row.image,...row.gallery_images]).size,3);}
const sources={verified_on:'2026-10-07',usage:'Representative category photographs; main view with two detail crops. No exact model identity is asserted.',image_dimensions:[1000,1000],storage:'Laravel local uploads, served through /api/v1/media/',sources:selections,products:mappings};
await fs.writeFile('apps/storefront/public/assets/product-photo-sources.json',JSON.stringify(sources,null,2));
await fs.writeFile('tmp/product-images-after.json',JSON.stringify(after,null,2));
await api('auth/logout','POST',{});
console.log('Updated '+after.length+' products; exactly 1 main + 2 gallery images; all other product fields preserved.');
