"use client";
import {useEffect,useRef,useState,type ReactNode} from 'react';
import {ChevronLeft,ChevronRight} from 'lucide-react';

/* One native carousel for products and journal cards. */
export default function Carousel({children,count,label,trackClassName,controlsClassName='product-carousel-controls',products=false}:{children:ReactNode;count:number;label:string;trackClassName:string;controlsClassName?:string;products?:boolean}){
 const track=useRef<HTMLDivElement>(null);
 const [state,setState]=useState({start:true,end:true,index:0});
 const step=()=>{const el=track.current;const first=el?.firstElementChild as HTMLElement|null;return el&&first?first.offsetWidth+(parseFloat(getComputedStyle(el).columnGap)||0):0;};
 const update=()=>{const el=track.current;if(el)setState({start:el.scrollLeft<2,end:el.scrollLeft+el.clientWidth>=el.scrollWidth-2,index:Math.min(count-1,Math.round(el.scrollLeft/(step()||1)))});};
 useEffect(()=>{const el=track.current;if(!el)return;const observer=new ResizeObserver(update);observer.observe(el);update();return()=>observer.disconnect();},[count]);
 const go=(index:number)=>{const el=track.current;if(el)el.scrollTo({left:index*step(),behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'instant':'smooth'});};
 return <div className="product-carousel-shell"><div ref={track} className={trackClassName} data-carousel-track="true" data-product-carousel={products?'true':undefined} role="region" aria-label={label} tabIndex={0} onScroll={update} onKeyDown={event=>{if(event.target!==event.currentTarget)return;if(event.key==='ArrowLeft'||event.key==='ArrowRight'){event.preventDefault();go(state.index+(event.key==='ArrowRight'?1:-1));}if(event.key==='Home'){event.preventDefault();go(0);}if(event.key==='End'){event.preventDefault();go(count-1);}}}>{children}</div><div className={controlsClassName}><button type="button" aria-label={'Previous '+label} disabled={state.start} onClick={()=>go(state.index-1)}><ChevronLeft size={18}/></button><button type="button" aria-label={'Next '+label} disabled={state.end} onClick={()=>go(state.index+1)}><ChevronRight size={18}/></button></div><div className="carousel-mobile-dots" aria-label={'Choose '+label+' card'}>{Array.from({length:count},(_,index)=><button type="button" key={index} aria-label={'Go to '+label+' card '+(index+1)} aria-current={index===state.index?'true':undefined} onClick={()=>go(index)}><span/></button>)}</div></div>;
}
