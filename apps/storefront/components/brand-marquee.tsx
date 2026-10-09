import Link from 'next/link';
import {slugify} from '@/lib/store';

/* Static brand logos: each brand appears once. */
export default function BrandMarquee({brands}:{brands:any[]}){
 return <div className="brand-marquee-track"><div className="brand-marquee-group">{brands.map(brand=><Link className="brand-marquee-item" href={'/brands/'+slugify(brand.name)} key={brand.id} aria-label={`Shop ${brand.name}`}><img src={brand.image} alt={brand.name} loading="lazy"/></Link>)}</div></div>;
}
