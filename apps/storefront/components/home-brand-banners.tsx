import Link from 'next/link';
import {ArrowUpRight} from 'lucide-react';
import {slugify} from '@/lib/store';

/* Local placeholder paths are backed by the existing appliance scenes. */
export default function HomeBrandBanners({brands}:{brands:any[]}){
 return <div className="home-brand-banners">{brands.filter(brand=>['lg','midea','karcher'].includes(slugify(brand.name))).map(brand=>{const slug=slugify(brand.name);return <Link className="home-brand-banner" href={'/brands/'+slug} key={brand.id}><img className="home-brand-scene" src={'/images/brands/'+slug+'.jpg'} alt={brand.name+' appliances'} loading="lazy" width={640} height={420}/><span className="home-brand-logo"><img src={brand.image||'/assets/brands/'+slug+'.png'} alt={brand.name} loading="lazy" width={120} height={48}/></span><span className="button">Shop {brand.name}<ArrowUpRight size={16} aria-hidden="true"/></span></Link>})}</div>;
}
