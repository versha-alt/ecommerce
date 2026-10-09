"use client";
import {Product} from '@/lib/store';
import {ProductCard} from '@/components/store';
import Carousel from '@/components/carousel';
export default function ProductCarousel({products,label,hotDeal=false,hideDelivery=false}:{products:Product[];label:string;hotDeal?:boolean;hideDelivery?:boolean}){
 return <Carousel count={products.length} label={label} trackClassName="product-grid home-product-grid" products>{products.map(product=><ProductCard key={product.id} product={product} hotDeal={hotDeal} hideDelivery={hideDelivery}/>)}</Carousel>;
}
