import type {Metadata} from 'next';
import Link from 'next/link';
import {ArrowRight,BadgePercent,PackageCheck,ShieldCheck,Truck} from 'lucide-react';
import {catalog,money,price} from '@/lib/store';
import {discountPercentage} from '@/lib/filter';
import {ProductCard} from '@/components/store';
import {categoryIds} from '@/lib/catalog-listing';

async function dealsData(){
 const data=await catalog();
 const section=data.homepage_sections.find(item=>item.key==='deals-page');
 const products=data.products.filter(product=>product.deal&&product.sale_price!=null&&product.sale_price<product.price).sort((a,b)=>discountPercentage(b)-discountPercentage(a));
 return {data,section,products};
}

export async function generateMetadata():Promise<Metadata>{
 const {section}=await dealsData();
 const title=section?.heading||'Deals';
 const description=section?.body||'Explore current appliance deals and special prices at LEEKAV Kenya.';
 return {title,description,alternates:{canonical:'/deals'},openGraph:{title,description,images:section?.image?[section.image]:undefined}};
}

export default async function DealsPage(){
 const {data,section,products}=await dealsData();
 if(!section)return <main id="main" className="container section content-unavailable" role="alert"><h1>Deals are currently unavailable.</h1><p>Configure and activate the Deals page section in Content &amp; marketing in the admin panel.</p></main>;
 const available=products.filter(product=>product.type==='Simple'&&product.stock-(product.reserved||0)>0).length;
 const highestSaving=Math.max(0,...products.map(discountPercentage));
 const totalSaving=products.reduce((sum,product)=>sum+Math.max(0,product.price-price(product)),0);
 const categories=data.categories.filter(category=>!category.parent_id).map(category=>({
  ...category,
  count:products.filter(product=>product.category_ids.some(id=>categoryIds(data,category.slug).has(id))).length,
 })).filter(category=>category.count>0);
 return <main id="main" className="deals-page">
  <section className="deals-hero">
   {section.image&&<img src={section.image} alt="" width={1800} height={760}/>}<div className="deals-hero-shade"/>
   <div className="container deals-hero-content"><nav className="breadcrumbs" aria-label="Breadcrumb"><Link href="/">Home</Link><span aria-hidden="true">/</span><span aria-current="page">Deals</span></nav><span className="eyebrow">{section.eyebrow}</span><h1>{section.heading}</h1>{section.body&&<p>{section.body}</p>}<div className="deals-hero-actions"><a className="button" href="#current-deals">Explore current deals <ArrowRight size={17}/></a>{section.link&&<Link className="button secondary" href={section.link}>{section.link_label||'Browse all products'}</Link>}</div></div>
  </section>
  <section className="container deals-summary" aria-label="Current deal summary"><div><BadgePercent/><strong>{products.length}</strong><span>active {products.length===1?'deal':'deals'}</span></div><div><PackageCheck/><strong>{available}</strong><span>available now</span></div><div><ShieldCheck/><strong>{highestSaving}%</strong><span>highest saving</span></div><div><Truck/><strong>{money(totalSaving)}</strong><span>combined unit savings</span></div></section>
  {categories.length>0&&<section className="container deals-categories" aria-labelledby="deal-categories-title"><div><span className="eyebrow">SHOP YOUR WAY</span><h2 id="deal-categories-title">Find a deal for every room.</h2></div><div>{categories.map(category=><Link href={`/products?category=${category.slug}&deal=1`} key={category.id}><span>{category.nav_label||category.name}</span><small>{category.count} {category.count===1?'deal':'deals'} <ArrowRight size={13}/></small></Link>)}</div></section>}
  <section className="container section deals-products" id="current-deals" aria-labelledby="current-deals-title"><div className="section-heading"><div><span className="eyebrow">CURRENT OFFERS</span><h2 id="current-deals-title">Save on something useful.</h2><p>Prices, availability and offer selection update from the admin panel.</p></div><Link href="/products?deal=1">View with filters <ArrowRight size={17}/></Link></div>{products.length?<div className="product-grid home-product-grid">{products.map(product=><ProductCard product={product} hotDeal key={product.id}/>)}</div>:<div className="empty-state"><BadgePercent size={30}/><h2>New deals are on the way.</h2><p>Select products for Deals and give them a lower sale price in the admin panel.</p><Link className="button" href={section.link||'/products'}>{section.link_label||'Browse all products'}</Link></div>}</section>
  <section className="container deals-assurance"><div><ShieldCheck/><h2>Clear savings</h2><p>Regular and sale prices are shown together so you can see the value before checkout.</p></div><div><PackageCheck/><h2>Live availability</h2><p>Stock shown on every deal reflects the latest quantity saved in the admin panel.</p></div><div><Truck/><h2>Delivery at checkout</h2><p>Select your county at checkout to confirm delivery availability and charges.</p></div></section>
 </main>;
}
