import Link from 'next/link';
import {ArrowRight,Heart,Package,ShoppingBag} from 'lucide-react';
const states = {
  orders: {Icon:Package,title:'No orders yet.',description:'Your next great find is waiting. Explore our products and place your first order.'},
  wishlist: {Icon:Heart,title:'Nothing in your wishlist yet.',description:'Save products you love by tapping the heart icon. Explore and find your favorites.'},
  cart: {Icon:ShoppingBag,title:'Nothing in your cart yet.',description:'Discover something useful for your everyday and add it to your cart.'},
};
export default function EmptyShopping({kind}:{kind:keyof typeof states}) {
 const {Icon,title,description}=states[kind];
 return <div className="empty-state empty-shopping"><Icon size={44} aria-hidden="true"/><h2>{title}</h2><p>{description}</p><Link className="button" href="/products">Explore products <ArrowRight size={16}/></Link></div>;
}
