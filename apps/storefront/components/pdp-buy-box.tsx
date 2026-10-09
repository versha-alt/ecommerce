"use client";
import {useState} from 'react';
import Link from 'next/link';
import {useRouter} from 'next/navigation';
import {ShoppingBag,Minus,Plus,Copy,Check,ShieldCheck,Truck,MessageCircle,FileDown,PackageCheck,LockKeyhole,RotateCcw,Ruler,Gauge,Layers} from 'lucide-react';
import {Product,money,price,slugify} from '@/lib/store';
import {productSpecs} from '@/lib/product-specs';
import {useStore} from './store';

export default function PdpBuyBox({product,features=[]}:{product:Product;features?:string[]}){
 const {add,cart,data}=useStore(),router=useRouter();const [quantity,setQuantity]=useState(1),[expanded,setExpanded]=useState(false),[copied,setCopied]=useState(''),[added,setAdded]=useState(false),[zoneId,setZoneId]=useState('');
 const available=product.type==='Simple'?Math.max(0,product.stock-(product.reserved||0)):0;
 const brand=data.brands.find((b:any)=>b.id===product.brand_id),specs=Object.entries(productSpecs(product));
 const model=specs.find(([key])=>/^model(?: number)?$/i.test(key))?.[1];
 const current=price(product),saving=Math.max(0,product.price-current),discount=product.price>0?Math.round(saving/product.price*100):0;
 const format=(value:number)=>money(value).replace(/^KES/,'KSh');
 const instalment=(product as Product&{instalment_monthly?:number}).instalment_monthly;
 const whatsapp=(data.settings.whatsapp||data.settings.phone||'').replace(/[^0-9]/g,'');
 const zone=data.delivery_zones.find((z:any)=>z.id===zoneId);
 const specTiles=specs.filter(([key])=>/capacity|size|dimensions|energy|power|channels|resolution|technology|cooking functions|format|cooling/i.test(key)).slice(0,4);
 const highlights=[...features,...specs.filter(([key])=>!/^model(?: number)?$/i.test(key)).map(([key,value])=>`${key}: ${value}`)];
 const copy=async(value:string)=>{try{await navigator.clipboard.writeText(value);setCopied(value);}catch{setCopied('Copy from the displayed text.');}};
 const purchase=(checkout=false)=>{if(!available)return;const existing=cart.find((line:any)=>line.id===product.id)?.quantity||0;add(product,quantity);if(existing+quantity<=available){setAdded(true);if(checkout)router.push('/checkout');}};
 return <>
  <div className="pdp-buy-top">{brand&&<Link className="eyebrow pdp-brand-chip" href={'/brands/'+slugify(brand.name)}>{brand.image&&<img src={brand.image} alt={brand.name} width={80} height={32}/>}<span>{brand.name}</span></Link>}<span className={'availability '+(available?'in-stock':'out-stock')}>{available?(available<=5?`Only ${available} left`:'In stock'):'Out of stock'}</span></div>
  <h1>{product.name}</h1>
  <div className="pdp-identifiers"><span className="product-sku">SKU: {product.sku}<button aria-label="Copy SKU" onClick={()=>copy(product.sku)}>{copied===product.sku?<Check size={14}/>:<Copy size={14}/>}</button></span>{model&&<span>Model: {model}<button aria-label="Copy model" onClick={()=>copy(model)}><Copy size={14}/></button></span>}</div>
  <span className="pdp-copy-status" role="status">{copied&&(copied.startsWith('Copy')?copied:'Copied '+copied)}</span>
  <div className="detail-price"><div className="pdp-price-line"><strong>{format(current)}</strong>{discount>0&&<span className="pdp-discount">-{discount}%</span>}</div>{saving>0&&<><del>{format(product.price)}</del><span className="pdp-saving">You save {format(saving)}</span></>}{instalment&&instalment>0&&<small>Pay in instalments from {format(instalment)}/month</small>}</div>
  {product.warranty&&<span className="pdp-warranty"><ShieldCheck size={16}/>{product.warranty}</span>}
  {specTiles.length>0&&<div className="pdp-spec-strip">{specTiles.map(([key,value],index)=>{const Icon=[Layers,Ruler,Gauge,PackageCheck][index];return <div key={key}><Icon size={18}/><small>{key}</small><strong>{value}</strong></div>;})}</div>}
  {highlights.length>0&&<section className="pdp-highlights"><h2>Highlights</h2><ul>{(expanded?highlights:highlights.slice(0,6)).map((feature,index)=><li key={index}><Check size={16}/><span>{feature}</span></li>)}</ul>{highlights.length>6&&<button aria-expanded={expanded} onClick={()=>setExpanded(!expanded)}>{expanded?'Show fewer features':'Show all features'}</button>}</section>}
  <div className="purchase-actions"><div className="quantity"><button aria-label="Decrease quantity" disabled={quantity<=1} onClick={()=>setQuantity(Math.max(1,quantity-1))}><Minus size={15}/></button><span aria-live="polite">{quantity}</span><button aria-label="Increase quantity" disabled={quantity>=available} onClick={()=>setQuantity(quantity+1)}><Plus size={15}/></button></div><button className="button pdp-add" data-added={added} disabled={!available} onClick={()=>purchase()}><ShoppingBag size={18} onAnimationEnd={()=>setAdded(false)}/>Add to cart</button><button className="button secondary" disabled={!available} onClick={()=>purchase(true)}>Buy now</button></div>
  {whatsapp&&<a className="button secondary pdp-whatsapp" href={'https://wa.me/'+whatsapp+'?text='+encodeURIComponent('Hello, I would like to order '+product.name)} target="_blank" rel="noreferrer"><MessageCircle size={16}/>Order on WhatsApp</a>}
  <div className="detail-assurances"><span><Truck size={16}/>Delivery options and charges confirmed at checkout.</span>{data.delivery_zones.length>0&&<label className="pdp-county-checker">Check delivery<select value={zoneId} onChange={event=>setZoneId(event.target.value)}><option value="">Choose your delivery area</option>{data.delivery_zones.map((z:any)=><option value={z.id} key={z.id}>{z.name}</option>)}</select>{zone&&<small>{zone.free_threshold&&current*quantity>=zone.free_threshold?'Free delivery':`Delivery from ${format(Number(zone.charge)||0)}`} · Confirm your address at checkout.</small>}</label>}</div>
  <div className="pdp-trust-row"><span><PackageCheck size={16}/>Genuine product</span><span><LockKeyhole size={16}/>Secure checkout</span><Link href="/shipping-returns"><RotateCcw size={16}/>Returns policy</Link>{product.warranty&&<span><ShieldCheck size={16}/>Warranty included</span>}</div>
  {product.manual&&<a className="detail-link" href={product.manual} target="_blank" rel="noreferrer"><FileDown size={16}/>Download manual / brochure</a>}
  <div className="pdp-mobile-buy"><strong>{format(current)}</strong><button className="button pdp-add" disabled={!available} onClick={()=>purchase()}><ShoppingBag size={16}/>Add to cart</button></div>
 </>;
}
