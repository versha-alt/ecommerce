import Link from 'next/link';
import {ArrowUpRight} from 'lucide-react';
import {filter} from '@/lib/filter';
import {HomepageSection,Product} from '@/lib/store';
import ProductCarousel from '@/components/product-carousel';

export default function HomeDeals({products,section}:{products:Product[];section:HomepageSection}){
 const deals=filter(products,{sale:'1'}).sort((a,b)=>(1-(b.sale_price??b.price)/b.price)-(1-(a.sale_price??a.price)/a.price));
 return <section className="container section home-deals" aria-labelledby="home-deals-title">
  <div className="section-heading">
   <div><span className="eyebrow">{section.eyebrow}</span><h2 id="home-deals-title">{section.heading}</h2>{section.body&&<p className="muted">{section.body}</p>}</div>
  <Link href={section.link||'/deals'}>{section.link_label||'Shop all deals'} <ArrowUpRight size={17}/></Link>
  </div>
  {deals.length?<ProductCarousel products={deals} label="hot deals" hotDeal/>:<div className="empty-state"><h3>New deals are on the way.</h3><p>Check back soon for offers on your favorite appliances.</p><Link className="text-link" href="/products">Browse all products <ArrowUpRight size={17}/></Link></div>}
 </section>;
}
