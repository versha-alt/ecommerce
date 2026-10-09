import {headers} from 'next/headers';
import Link from 'next/link';
import {notFound} from 'next/navigation';
import {plainText} from '@/lib/rich-text';
import {catalog,price} from '@/lib/store';
import {Gallery,ProductActions} from '@/components/product-detail';
import PdpCompare from '@/components/pdp-compare';
import ProductCarousel from '@/components/product-carousel';

export async function generateMetadata({params}:{params:Promise<{slug:string}>}){
 const data=await catalog();const {slug}=await params;const product=data.products.find(p=>p.slug===slug);
 return {title:product?.seo_title||product?.name||'Product',description:plainText(product?.seo_description||product?.description||product?.name||'').slice(0,180)};
}
export default async function ProductPage({params}:{params:Promise<{slug:string}>}){
 const data=await catalog();const {slug}=await params;const product=data.products.find(p=>p.slug===slug);if(!product)notFound();
 const brand=data.brands.find(b=>b.id===product.brand_id);
 const category=data.categories.find(c=>c.parent_id&&product.category_ids.includes(c.id))||data.categories.find(c=>product.category_ids.includes(c.id));
 const features=plainText(product.description||'').split(/\n|\\n/).map(line=>line.replace(/^[•\-]\s*/,'').trim()).filter(Boolean);
 const similar=data.products.filter(p=>p.id!==product.id&&(p.category_ids.some(id=>product.category_ids.includes(id))||p.brand_id===product.brand_id)).slice(0,4);
 const used=new Set([product.id,...similar.map(p=>p.id)]);
 const together=data.products.filter(p=>!used.has(p.id)).slice(0,4);
 const h=await headers(),origin=process.env.NEXT_PUBLIC_SITE_URL||`${h.get('x-forwarded-proto')||'http'}://${h.get('host')||'localhost:3000'}`;
 const crumbs=[['Home','/'],['Shop','/products'],...(category?[[category.name,'/categories/'+category.slug]]:[]),[product.name,'/products/'+slug]];
 const schema=[{'@context':'https://schema.org','@type':'Product',name:product.name,description:plainText(product.description||''),sku:product.sku,image:[product.image,...(product.gallery_images||[])].filter(Boolean).map(image=>new URL(image!,origin).href),...(brand?{brand:{'@type':'Brand',name:brand.name}}:{}),offers:{'@type':'Offer',url:new URL('/products/'+slug,origin).href,priceCurrency:'KES',price:price(product),availability:'https://schema.org/'+(product.type==='Simple'&&product.stock>product.reserved?'InStock':'OutOfStock')}},{'@context':'https://schema.org','@type':'BreadcrumbList',itemListElement:crumbs.map(([name,path],index)=>({'@type':'ListItem',position:index+1,name,item:new URL(path,origin).href}))}];
 return <main id="main" className="container product-detail-page"><nav className="breadcrumbs" aria-label="Breadcrumb">{crumbs.map(([name,path],index)=><span key={path}>{index>0&&<span aria-hidden="true">/</span>}{index===crumbs.length-1?<span aria-current="page" title={name}>{name}</span>:<Link href={path}>{name}</Link>}</span>)}</nav><div className="product-detail-grid"><div className="pdp-gallery-column"><Gallery product={product}/></div><section className="product-detail-copy"><ProductActions product={product} features={features}/></section></div><PdpCompare product={product}/>{similar.length>0&&<section className="section pdp-recommendations"><div className="section-heading"><div><span className="eyebrow">A FEW MORE THOUGHTFUL FINDS</span><h2>Similar products</h2></div></div><ProductCarousel products={similar} label="similar products"/></section>}{together.length>0&&<section className="section pdp-recommendations"><div className="section-heading"><div><h2>Frequently bought together</h2><p>Explore complementary products for your home.</p></div></div><ProductCarousel products={together} label="frequently bought together"/></section>}<script type="application/ld+json" dangerouslySetInnerHTML={{__html:JSON.stringify(schema).replaceAll('<','\\u003c')}}/></main>;
}
