import type {Catalog} from './store';

export type MenuLink={label:string;query:string};
export type MenuFeature={image:string;imageAlt?:string;label:string;headline:string;description:string;query?:string;href?:string};
export type MenuCategory={id:string;name:string;navLabel?:string;image:string;query:string;subcategories:MenuLink[];featured:MenuFeature[];brands:string[]};

export function menuCategories(data:Catalog):MenuCategory[]{
 return data.categories.filter(category=>!category.parent_id).sort((a,b)=>Number(a.sort_order||0)-Number(b.sort_order||0)).map(category=>{
  const children=data.categories.filter(child=>child.parent_id===category.id);
  return {id:category.slug,name:category.name,navLabel:category.nav_label||category.name,image:category.image||'',query:category.name,subcategories:children.map(child=>({label:child.name,query:child.name})),featured:category.promo_headline?[{image:category.promo_image||category.image||'',imageAlt:category.promo_headline,label:category.promo_label||'Featured',headline:category.promo_headline,description:category.promo_description||category.description||'',href:'/categories/'+category.slug}]:[],brands:data.brands.filter(brand=>data.products.some(product=>product.brand_id===brand.id&&product.category_ids.includes(category.id))).map(brand=>brand.id)};
 });
}

export function productsHref(filters:{q?:string;brand?:string;category?:string}){const params=new URLSearchParams();if(filters.q)params.set('q',filters.q);if(filters.brand)params.set('brand',filters.brand);if(filters.category)params.set('category',filters.category);const query=params.toString();return query?`/products?${query}`:'/products';}

export function menuCategoryHref(category:MenuCategory,data:Catalog,label?:string){const parent=data.categories.find(item=>item.slug===category.id);const selected=label?data.categories.find(item=>item.parent_id===parent?.id&&item.name===label):parent;return selected?`/categories/${selected.slug}`:productsHref({q:label?category.subcategories.find(item=>item.label===label)?.query:category.query});}

export function menuBrandHref(category:MenuCategory,data:Catalog,brand:string){const parent=data.categories.find(item=>item.slug===category.id);return productsHref({brand,...(parent?{category:parent.id}:{q:category.query})});}

export const featureHref=(feature:MenuFeature,category:MenuCategory,data:Catalog)=>{const child=category.subcategories.find(item=>item.query===feature.query);return feature.href??menuCategoryHref(category,data,child?.label);};
