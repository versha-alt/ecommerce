import Link from 'next/link';
import {notFound} from 'next/navigation';
import {headers} from 'next/headers';
import {catalog} from '@/lib/store';
import {menuCategories} from '@/lib/mega-menu';
import CategoryListing from '@/components/category-listing';
import CollectionBanner from '@/components/collection-banner';

export async function generateMetadata({params}:{params:Promise<{slug:string}>}){const data=await catalog();const {slug}=await params;const category=data.categories.find(c=>c.slug===slug);return {title:category?.seo_title||category?.name||'Category',description:category?.seo_description||category?.description||'Thoughtful finds for a better everyday.'}}
export default async function Category({params,searchParams}:{params:Promise<{slug:string}>;searchParams:Promise<Record<string,string>>}){
 const data=await catalog();const {slug}=await params;const category=data.categories.find(c=>c.slug===slug);if(!category)notFound();
 const parent=data.categories.find(c=>c.id===category.parent_id);
 const scene=menuCategories.find(c=>c.id===(parent?.slug||category.slug));
 const panorama=Boolean(scene);
 const image=scene?`/assets/banners/${scene.id}-panorama.webp`:category.image||parent?.image;
 const description=category.description||'Thoughtful finds for a better everyday.';
 const products=data.products.filter(p=>p.category_ids.includes(category.id));
 const requestHeaders=await headers();const origin=process.env.NEXT_PUBLIC_SITE_URL||`${requestHeaders.get('x-forwarded-proto')||'http'}://${requestHeaders.get('host')||'localhost:3000'}`;
 const breadcrumb={'@context':'https://schema.org','@type':'BreadcrumbList',itemListElement:[{name:'Home',item:'/'},{name:'Categories',item:'/products'},...(parent?[{name:parent.name,item:'/categories/'+parent.slug}]:[]),{name:category.name,item:'/categories/'+category.slug}].map((crumb,index)=>({'@type':'ListItem',position:index+1,name:crumb.name,item:new URL(crumb.item,origin).href}))};
 return <><CollectionBanner name={category.name} description={description} image={image} count={products.length} parent={parent}/><script type="application/ld+json" dangerouslySetInnerHTML={{__html:JSON.stringify(breadcrumb).replaceAll('<',String.fromCharCode(92)+'u003c')}}/><CategoryListing data={data} category={category} products={products}/></>;
}
