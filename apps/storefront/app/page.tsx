import HomepageReveal from '@/components/homepage-reveal';
import ProductCarousel from '@/components/product-carousel';
import HomeDeals from '@/components/home-deals';
import BrandMarquee from '@/components/brand-marquee';
import BlogCarousel from '@/components/blog-carousel';
import Hero from '@/components/hero';
import HomeEditorial from '@/components/home-editorial';
import Link from 'next/link';
import {ArrowUpRight} from 'lucide-react';
import {catalog,slugify,type HomepageSection,type Product} from '@/lib/store';
import {Benefits} from '@/components/store';

const unavailable=(name:string)=><section className="container section content-unavailable" role="alert"><h2>{name} is unavailable.</h2><p>Activate this section in Content &amp; marketing in the admin panel.</p></section>;

export default async function Home(){
 const data=await catalog();
 const sections=new Map(data.homepage_sections.map(section=>[section.key,section]));
 const categories=data.categories.filter(category=>!category.parent_id).sort((a,b)=>Number(a.sort_order||0)-Number(b.sort_order||0));
 const featured=data.products.filter(product=>product.featured).slice(0,12);
 const deals=data.products.filter(product=>product.deal);
 const arrivals=data.products.filter(product=>product.new_arrival).slice(0,12);
 const section=(key:string)=>sections.get(key);
 const categoriesSection=section('categories');
 const featuredSection=section('featured-products');
 const dealsSection=section('deals');
 const editorialSection=section('editorial');
 const arrivalsSection=section('new-arrivals');
 const brandsSection=section('brands');
 const journalSection=section('journal');
 return <main id="main">
  <HomepageReveal/><Hero data={data}/><Benefits/>
  {categoriesSection?<CategorySection section={categoriesSection} categories={categories}/>:unavailable('Shop by category')}
  {featuredSection?<ProductSection section={featuredSection} products={featured} label="featured products"/>:unavailable('Featured products')}
  {dealsSection?<HomeDeals products={deals} section={dealsSection}/>:unavailable('Homepage deals')}
  {editorialSection?<HomeEditorial eyebrow={editorialSection.eyebrow} headline={editorialSection.heading} body={editorialSection.body} link={editorialSection.link} linkLabel={editorialSection.link_label} image={editorialSection.image} imageAlt={editorialSection.heading}/>:unavailable('Editorial promotion')}
  {arrivalsSection?<ProductSection section={arrivalsSection} products={arrivals} label="new arrivals"/>:unavailable('New arrivals')}
  {brandsSection?<BrandsMarquee brands={data.brands} section={brandsSection}/>:unavailable('Brands')}
  {journalSection?<BlogCarousel articles={[...data.articles].sort((a,b)=>Number(a.sort_order||0)-Number(b.sort_order||0))} section={journalSection}/>:unavailable('Journal')}
 </main>;
}

function CategorySection({section,categories}:{section:HomepageSection;categories:any[]}){
 return <section className="container section home-categories"><div className="section-heading"><div><span className="eyebrow">{section.eyebrow}</span><h2>{section.heading}</h2>{section.body&&<p>{section.body}</p>}</div><Link href={section.link||'/products'}>{section.link_label||'Explore'} <ArrowUpRight size={17}/></Link></div><div className="category-image-grid">{categories.map(category=><Link className="category-image-card" href={'/categories/'+category.slug} key={category.id}><span className="category-image">{category.image&&<img src={category.image} alt={`${category.name} products`} loading="lazy"/>}</span><strong>{category.nav_label||category.name}</strong><small>Shop collection <ArrowUpRight size={14}/></small></Link>)}</div></section>;
}

function ProductSection({section,products,label}:{section:HomepageSection;products:Product[];label:string}){
 return <section className="container section" data-home-collection={section.key}><div className="section-heading"><div><span className="eyebrow">{section.eyebrow}</span><h2>{section.heading}</h2>{section.body&&<p>{section.body}</p>}</div>{section.link&&<Link href={section.link}>{section.link_label||'Explore'} <ArrowUpRight size={17}/></Link>}</div>{products.length?<ProductCarousel products={products} label={label}/>:<div className="empty-state"><h3>No products selected.</h3><p>Select products for this section in the admin panel.</p></div>}</section>;
}

function BrandsMarquee({brands,section}:{brands:any[];section:HomepageSection}){const visible=brands.filter(brand=>brand.image);if(!visible.length)return unavailable('Brands');return <section className="container section brand-marquee-section" aria-labelledby="home-brands-title"><div className="brand-section-heading"><span className="eyebrow">{section.eyebrow}</span><h2 id="home-brands-title">{section.heading}</h2>{section.body&&<p>{section.body}</p>}</div><div className="brand-marquee" aria-label="Brand logos"><BrandMarquee brands={visible.map(brand=>({...brand,slug:slugify(brand.name)}))}/></div></section>}
