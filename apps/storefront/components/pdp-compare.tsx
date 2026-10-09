"use client";
import {useState} from 'react';
import Link from 'next/link';
import {GitCompareArrows} from 'lucide-react';
import {Product,price,money} from '@/lib/store';
import {productSpecs} from '@/lib/product-specs';
import {useStore,ProductImage} from './store';
import CatalogDialog from './catalog-dialog';

export default function PdpCompare({product,compact=false}:{product:Product;compact?:boolean}){
 const {data}=useStore();const [open,setOpen]=useState(false),[ids,setIds]=useState([product.id]);
 const candidates=data.products.filter((p:Product)=>p.id===product.id||p.category_ids.some(id=>product.category_ids.includes(id)));
 const selected=candidates.filter((p:Product)=>ids.includes(p.id));
 const keys=[...new Set<string>(selected.flatMap((p:Product)=>Object.keys(productSpecs(p))))];
 const button=<button className={compact?'pdp-gallery-action':'button secondary'} onClick={()=>setOpen(true)}><GitCompareArrows size={16}/>{compact?'Compare':'Add to compare'}</button>;
 return <>{compact?button:<section className="pdp-compare-card"><div><h2>Compare before you buy</h2><p>Check the specifications side by side.</p></div>{button}</section>}{open&&<CatalogDialog title="Compare products" onClose={()=>setOpen(false)}><div className="pdp-compare-picker">{candidates.map((p:Product)=><label key={p.id}><input type="checkbox" checked={ids.includes(p.id)} disabled={!ids.includes(p.id)&&ids.length>=3} onChange={()=>setIds(ids.includes(p.id)?ids.filter(id=>id!==p.id):[...ids,p.id])}/>{p.name}</label>)}<small>Select up to 3 products.</small></div><div className="catalog-compare-table"><table><thead><tr><th>Specification</th>{selected.map((p:Product)=><th key={p.id}><Link className="pdp-compare-product" href={'/products/'+p.slug}><ProductImage product={p}/><span>{p.name}</span></Link></th>)}</tr></thead><tbody><tr><th>Price</th>{selected.map((p:Product)=><td key={p.id}>{money(price(p))}</td>)}</tr>{keys.map(key=><tr key={key}><th>{key}</th>{selected.map((p:Product)=><td key={p.id}>{productSpecs(p)[key]||'—'}</td>)}</tr>)}</tbody></table></div></CatalogDialog>}</>;
}
