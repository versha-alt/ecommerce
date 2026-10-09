import type {Catalog,Product} from './store';
import {slugify} from './store';
import {filter} from './filter';
import {productSpecs} from './product-specs';

// Shared filtering keeps server metadata and instant client results consistent.
export function normalizeCatalogQuery(data:Catalog,input:Record<string,string>){
 const query={...input};
 for(const key of ['brand','category'] as const){const rows=key==='brand'?data.brands:data.categories;if(query[key])query[key]=query[key].split(',').map(value=>{const row=rows.find(item=>item.id===value|| (key==='brand'?slugify(item.name):item.slug)===value);return row?(key==='brand'?slugify(row.name):row.slug):value;}).join(',');}
 return query;
}
export function categoryIds(data:Catalog,slug:string){const root=data.categories.find(c=>c.slug===slug);if(!root)return new Set<string>();const ids=new Set<string>([root.id]);let changed=true;while(changed){changed=false;for(const c of data.categories)if(ids.has(c.parent_id)&&!ids.has(c.id)){ids.add(c.id);changed=true;}}return ids;}
export function categoryCount(data:Catalog,slug:string){const ids=categoryIds(data,slug);return data.products.filter(p=>p.category_ids.some(id=>ids.has(id))).length;}
export function catalogResults(data:Catalog,query:Record<string,string>){
 const brands=(query.brand||'').split(',').filter(Boolean).map(slug=>data.brands.find(b=>slugify(b.name)===slug)?.id||slug);
 const selectedCategories=(query.category||'').split(',').filter(Boolean);const ids=new Set(selectedCategories.flatMap(slug=>[...categoryIds(data,slug)]));
 const products=data.products.filter(p=>(!brands.length||brands.includes(p.brand_id||''))&&(!selectedCategories.length||p.category_ids.some(id=>ids.has(id)))&&Object.entries(query).filter(([key])=>key.startsWith('spec:')).every(([key,value])=>productSpecs(p)[key.slice(5)]===value));
 return filter(products,{...query,brand:undefined,category:undefined});
}
export function catalogTitle(data:Catalog,query:Record<string,string>){const names=[...(query.brand||'').split(',').filter(Boolean).map(slug=>data.brands.find(b=>slugify(b.name)===slug)?.name||slug),...(query.category||'').split(',').filter(Boolean).map(slug=>data.categories.find(c=>c.slug===slug)?.name||slug)];return names.join(' ')||'All products';}
export function catalogDescription(data:Catalog,query:Record<string,string>){return `Shop ${catalogTitle(data,query).toLowerCase()} at LEEKAV in Kenya. ${catalogResults(data,query).length} products. ${query.q?`Search: ${query.q}. `:''}${query.sale==='1'||query.offer==='1'?'Special offers. ':''}${query.hot==='1'?'Hot deals. ':''}${query.available==='1'?'In-stock products. ':''}${query.min?`Prices from KSh ${query.min}. `:''}${query.max?`Prices up to KSh ${query.max}. `:''}${Object.entries(query).filter(([key])=>key.startsWith('spec:')).map(([key,value])=>`${key.slice(5)}: ${value}. `).join('')}Sort: ${query.sort||'featured'}. Compare specifications and choose the right fit for your home.`;}
export function catalogPath(query:Record<string,string>){const params=new URLSearchParams(Object.entries(query).filter(([key,value])=>value&&!(key==='page'&&value==='1')).sort(([a],[b])=>a.localeCompare(b)));return '/products'+(params.size?'?'+params:'');}
export function catalogBreadcrumbs(data:Catalog,query:Record<string,string>){return [['Home','/'],['Products','/products'],...(query.brand||'').split(',').filter(Boolean).map(slug=>[data.brands.find(b=>slugify(b.name)===slug)?.name||slug,catalogPath({brand:slug})]),...(query.category||'').split(',').filter(Boolean).map(slug=>[data.categories.find(c=>c.slug===slug)?.name||slug,catalogPath({...query,category:slug})])];}

export function catalogSeoTitle(data:Catalog,query:Record<string,string>){const details=Object.entries(query).filter(([key])=>!['brand','category'].includes(key)).sort(([a],[b])=>a.localeCompare(b)).map(([key,value])=>key==='q'?value:key==='sale'||key==='offer'?'Offers':key==='available'?'In stock':key==='hot'?'Hot deals':key==='max'?'Up to KSh '+value:key==='min'?'From KSh '+value:key==='sort'?value.replaceAll('-',' '):key==='page'?'Page '+value:key.replace('spec:','')+': '+value);return [catalogTitle(data,query),...details].join(' | ');}
