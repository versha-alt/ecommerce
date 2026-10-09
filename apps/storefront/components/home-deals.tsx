import Link from 'next/link';
import {ArrowUpRight} from 'lucide-react';
import {filter} from '@/lib/filter';
import {Product} from '@/lib/store';
import ProductCarousel from '@/components/product-carousel';

export default function HomeDeals({products,tagline}:{products:Product[];tagline?:string}){
 const copy=tagline?.trim()||'Limited-time prices on the appliances your home needs.';
 const deals=filter(products,{sale:'1',hot:'1'}).sort((a,b)=>(1-(b.sale_price??b.price)/b.price)-(1-(a.sale_price??a.price)/a.price));
 return <section className="container section home-deals" aria-labelledby="home-deals-title">
  <div className="section-heading">
   <div><span className="eyebrow">MORE VALUE FOR YOUR HOME</span><h2 id="home-deals-title">Deals worth bringing home.</h2><p className="muted" title={copy}>{copy}</p></div>
   <Link href="/products?sale=1&hot=1">Shop all deals <ArrowUpRight size={17}/></Link>
  </div>
  {deals.length?<ProductCarousel products={deals} label="hot deals" hotDeal/>:<div className="empty-state"><h3>New deals are on the way.</h3><p>Check back soon for offers on your favorite appliances.</p><Link className="text-link" href="/products">Browse all products <ArrowUpRight size={17}/></Link></div>}
 </section>;
}