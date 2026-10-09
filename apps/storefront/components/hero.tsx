"use client";
import RichContent from '@/components/rich-content';
import {useEffect,useRef,useState} from 'react';
import Link from 'next/link';
import {ArrowRight,ChevronLeft,ChevronRight} from 'lucide-react';
import {Catalog} from '@/lib/store';

// Keep the existing images and destinations; match the copy to each slide.
const banners=[
 {id:'everyday-home',name:'Everyday home',tag:'LEEKAV HOME ESSENTIALS · KENYA',heading:['Upgrade your home.','Elevate your everyday.'],image:'/assets/banners/everyday-appliances.webp',description:'Shop reliable appliances from trusted brands, priced clearly and delivered with support that keeps everyday life moving.',link:'/products'},
 {id:'midea-kitchen',name:'Kitchen essentials',tag:'LEEKAV KITCHEN ESSENTIALS · KENYA',heading:['Better cooking.','Brighter everyday.'],image:'/assets/banners/kitchen-appliances.webp',description:'Refresh your kitchen with practical appliances chosen for everyday cooking, storage and simple routines.',link:'/categories/cookers-microwaves'},
 {id:'lg-living',name:'Living room upgrades',tag:'LEEKAV HOME ENTERTAINMENT · KENYA',heading:['Big moments.','Better together.'],image:'/assets/banners/living-entertainment.webp',description:'Bring comfort, entertainment and easy living together with dependable home technology.',link:'/categories/tvs-audio'},
];

export default function Hero({data}:{data:Catalog}){
 void data;
 const heroRef=useRef<HTMLElement>(null);
 const [visible,setVisible]=useState(true);
 const [index,setIndex]=useState(0);
 const [reducedMotion,setReducedMotion]=useState(true);
 const [hovered,setHovered]=useState(false);
 const [touching,setTouching]=useState(false);
 useEffect(()=>{
  const media=window.matchMedia('(prefers-reduced-motion: reduce)');
  const sync=()=>setReducedMotion(media.matches);
  sync();media.addEventListener('change',sync);
  return()=>media.removeEventListener('change',sync);
 },[]);
 // Pause off-screen so browsing lower sections never changes their position.
 useEffect(()=>{
  const observer=new IntersectionObserver(([entry])=>setVisible(entry.isIntersecting),{threshold:.1});
  if(heroRef.current)observer.observe(heroRef.current);
  return()=>observer.disconnect();
 },[]);
 // Restart the five-second countdown after interaction; clean up on unmount.
 useEffect(()=>{
  if(banners.length<2||reducedMotion||hovered||touching||!visible)return;
  const timer=window.setTimeout(()=>setIndex(current=>(current+1)%banners.length),5000);
  return()=>window.clearTimeout(timer);
 },[index,reducedMotion,hovered,touching,visible]);
 const current=index%banners.length;
 const banner=banners[current];
 const move=(step:number)=>setIndex(active=>(active+step+banners.length)%banners.length);
 return <section ref={heroRef} className="hero banner-carousel home-hero-reference" aria-label="Featured collections" aria-roledescription="carousel"
  onPointerEnter={event=>{if(event.pointerType==='mouse')setHovered(true);}}
  onPointerLeave={()=>setHovered(false)}
  onTouchStart={()=>setTouching(true)} onTouchEnd={()=>setTouching(false)} onTouchCancel={()=>setTouching(false)}
  onKeyDown={event=>{if(event.key==='ArrowLeft'){event.preventDefault();move(-1);}if(event.key==='ArrowRight'){event.preventDefault();move(1);}}}>
  <div className="hero-copy">
   <span key={banner.id+'-tag'} className="eyebrow">{banner.tag}</span>
   <h1 key={banner.id+'-heading'}><span>{banner.heading[0]}</span><br/><span>{banner.heading[1]}</span></h1>
   <RichContent key={banner.id+'-description'} className="hero-description" value={banner.description}/>
   <div className="hero-actions"><Link className="button" href={banner.link}>Shop the range <ArrowRight size={17}/></Link><Link className="button secondary" href="/products">Browse categories</Link></div>
   {banners.length>1&&<div className="hero-controls carousel-controls">
    <button type="button" aria-label="Previous banner" onClick={()=>move(-1)}><ChevronLeft size={20}/></button>
    <div className="carousel-dots" aria-label="Choose banner">{banners.map((slide,i)=><button type="button" key={slide.id} aria-label={`Go to banner ${i+1}: ${slide.name}`} aria-current={current===i?'true':undefined} className={current===i?'selected':''} onClick={()=>setIndex(i)}><span/></button>)}</div>
    <button type="button" aria-label="Next banner" onClick={()=>move(1)}><ChevronRight size={20}/></button>
   </div>}
  </div>
  <div className="hero-showcase" aria-hidden="true">
   {/* Layer the existing images for a smooth crossfade without new classes. */}
   {banners.map((slide,i)=><img key={slide.id} className="hero-showcase-image" src={slide.image} alt="" loading="lazy" data-active={current===i}/>)}
   <span className="hero-showcase-pill">CURATED FOR DAILY LIVING</span>
   <div className="hero-showcase-note"><strong>{banner.name}</strong><span>Kitchen, laundry, cleaning and entertainment.</span></div>
  </div>
 </section>;
}
