"use client";
import {useEffect} from 'react';

/* One observer owns section-specific reveals and transform-only parallax. */
export default function HomepageReveal(){
 useEffect(()=>{
  const media=matchMedia('(prefers-reduced-motion: reduce)');let observer:IntersectionObserver|undefined;let frame=0;let visible=false;
  const section=document.querySelector<HTMLElement>('.home-editorial');const backdrop=section?.querySelector<HTMLElement>('.home-editorial-media');
  const parallax=()=>{frame=0;if(media.matches||!visible||!section||!backdrop)return;const rect=section.getBoundingClientRect();const offset=Math.max(-18,Math.min(18,(innerHeight/2-rect.top-rect.height/2)*.045));backdrop.style.setProperty('--parallax-y',`${offset}px`);};
  const scroll=()=>{if(!frame)frame=requestAnimationFrame(parallax);};
  const sync=()=>{observer?.disconnect();cancelAnimationFrame(frame);frame=0;document.querySelectorAll('[data-motion-revealed]').forEach(el=>el.removeAttribute('data-motion-revealed'));backdrop?.style.removeProperty('--parallax-y');if(media.matches)return;
   observer=new IntersectionObserver(entries=>entries.forEach(entry=>{if(entry.target===section){visible=entry.isIntersecting;scroll();return;}if(entry.isIntersecting){entry.target.setAttribute('data-motion-revealed','true');observer?.unobserve(entry.target);}}),{rootMargin:'0px 0px 40px 0px',threshold:.12});
   document.querySelectorAll('[data-home-collection=featured] .product-card').forEach((el,index)=>{el.setAttribute('data-motion-effect','fade-up');(el as HTMLElement).style.setProperty('--motion-delay',`${index*70}ms`);observer?.observe(el);});
   document.querySelectorAll('.home-editorial-content').forEach(el=>{el.setAttribute('data-motion-effect','fade-in');observer?.observe(el);});
   document.querySelectorAll('.blog-card-image img').forEach(el=>{el.setAttribute('data-motion-effect','image-reveal');observer?.observe(el);});if(section)observer.observe(section);
  };
  sync();addEventListener('scroll',scroll,{passive:true});addEventListener('resize',scroll);media.addEventListener('change',sync);
  return()=>{observer?.disconnect();cancelAnimationFrame(frame);removeEventListener('scroll',scroll);removeEventListener('resize',scroll);media.removeEventListener('change',sync);};
 },[]);
 return null;
}
