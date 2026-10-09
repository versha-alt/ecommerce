"use client";
import {useRef,useState} from 'react';
import Image from 'next/image';
import {Heart,Share2,ChevronLeft,ChevronRight} from 'lucide-react';
import {Product} from '@/lib/store';
import {ProductImage,useStore} from './store';
import CatalogDialog from './catalog-dialog';
import PdpCompare from './pdp-compare';

export default function PdpGallery({product}:{product:Product}){
 const images=Array.from(new Set([product.image,...(product.gallery_images||[])].filter(Boolean))) as string[];
 const [selected,setSelected]=useState(0),[previous,setPrevious]=useState<number|null>(null),[zoom,setZoom]=useState(false),[scale,setScale]=useState(1),[notice,setNotice]=useState('');
 const {wishlist,favorite}=useStore();const start=useRef<number|null>(null),pinch=useRef(0);
 const select=(index:number)=>{if(index===selected)return;setPrevious(selected);setSelected(index);};
 const go=(step:number)=>select((selected+step+images.length)%images.length);
 const share=async()=>{try{if(navigator.share)await navigator.share({title:product.name,url:location.href});else{await navigator.clipboard.writeText(location.href);setNotice('Product link copied.');}}catch(error){if((error as Error).name!=='AbortError')setNotice('Copy the product link from your address bar.');}};
 return <div className="product-gallery-panel" onKeyDown={event=>{if(event.target instanceof HTMLButtonElement&&event.target.closest('.pdp-gallery-actions'))return;if(images.length&&(event.key==='ArrowLeft'||event.key==='ArrowRight')){event.preventDefault();go(event.key==='ArrowRight'?1:-1);}}}>
  <div className="pdp-main-image" onTouchStart={event=>{start.current=event.touches.length===1?event.touches[0].clientX:null;}} onTouchEnd={event=>{if(start.current!==null&&images.length&&Math.abs(event.changedTouches[0].clientX-start.current)>40)go(event.changedTouches[0].clientX<start.current?1:-1);start.current=null;}}>
   <button className="main-product-photo" aria-label="Zoom product image" disabled={!images.length} onClick={()=>{setScale(1);setZoom(true);}}>{images.length?<Image key={images[selected]} src={images[selected]} alt={`${product.name}, image ${selected+1}`} width={700} height={700} priority unoptimized={images[selected].endsWith('.svg')}/>:<ProductImage product={product}/>}{previous!==null&&<Image className="pdp-image-out" src={images[previous]} alt="" aria-hidden="true" width={700} height={700} unoptimized={images[previous].endsWith('.svg')} onAnimationEnd={()=>setPrevious(null)}/>}<span>Click to take a closer look</span></button>
   <span className="pdp-image-counter" aria-live="polite">{images.length?selected+1:0}/{images.length}</span>
   {images.length>1&&<div className="pdp-gallery-arrows"><button aria-label="Previous product image" onClick={()=>go(-1)}><ChevronLeft size={18}/></button><button aria-label="Next product image" onClick={()=>go(1)}><ChevronRight size={18}/></button></div>}
  </div>
  <div className="product-thumbnails">{images.map((image,index)=><button key={image} aria-label={'View product image '+(index+1)} aria-pressed={selected===index} className={selected===index?'selected':''} onClick={()=>select(index)}><Image src={image} alt="" width={90} height={90} unoptimized={image.endsWith('.svg')}/></button>)}</div>
  <div className="pdp-gallery-actions"><button className={'wishlist-detail '+(wishlist.includes(product.id)?'selected':'')} aria-pressed={wishlist.includes(product.id)} onClick={()=>favorite(product.id)}><Heart size={16} fill={wishlist.includes(product.id)?'currentColor':'none'}/>Wishlist</button><PdpCompare product={product} compact/><button onClick={share}><Share2 size={16}/>Share</button></div><span className="pdp-action-notice" role="status">{notice}</span>
  {zoom&&<CatalogDialog title={product.name} onClose={()=>setZoom(false)}><div className="photo-zoom pdp-photo-zoom" onTouchStart={event=>{if(event.touches.length===2)pinch.current=Math.hypot(event.touches[0].clientX-event.touches[1].clientX,event.touches[0].clientY-event.touches[1].clientY)/scale;}} onTouchMove={event=>{if(event.touches.length===2&&pinch.current)setScale(Math.max(1,Math.min(3,Math.hypot(event.touches[0].clientX-event.touches[1].clientX,event.touches[0].clientY-event.touches[1].clientY)/pinch.current)));}}><img src={images[selected]} alt={product.name} style={{transform:`scale(${scale})`}}/></div><p className="pdp-zoom-hint">Pinch to zoom on touch devices.</p></CatalogDialog>}
 </div>;
}
