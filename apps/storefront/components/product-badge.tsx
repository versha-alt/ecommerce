import {Flame} from 'lucide-react';
// Shared card badge: hot deals take precedence over discount badges.
export default function ProductBadge({variant,discount=0}:{variant:'hot'|'discount';discount?:number}){
 if(variant==='discount'&&discount<=0)return null;
 return <span className={'offer-badge'+(variant==='hot'?' hot-deal':'')} data-variant={variant}>{variant==='hot'&&<Flame size={12} aria-hidden="true"/>}<span>{variant==='hot'?'HOT DEAL':`-${discount}%`}</span></span>;
}
