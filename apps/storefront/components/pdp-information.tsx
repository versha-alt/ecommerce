"use client";
import {useState} from 'react';
import Link from 'next/link';
import {Product} from '@/lib/store';
import {productSpecs} from '@/lib/product-specs';
import RichContent from './rich-content';
import {Reviews} from './product-detail';

export default function PdpInformation({product}:{product:Product}){
 const [active,setActive]=useState('Description');const tabs=['Description','Specifications','Delivery & returns','Reviews'];
 const specs=Object.entries(productSpecs(product));
 return <div className="product-information"><div className="pdp-tabs" role="tablist" aria-label="Product information">{tabs.map((tab,index)=><button role="tab" id={'pdp-tab-'+index} aria-controls={'pdp-panel-'+index} aria-selected={active===tab} tabIndex={active===tab?0:-1} key={tab} onClick={()=>setActive(tab)} onKeyDown={event=>{if(['ArrowLeft','ArrowRight','Home','End'].includes(event.key)){event.preventDefault();const next=event.key==='Home'?0:event.key==='End'?tabs.length-1:(index+(event.key==='ArrowRight'?1:tabs.length-1))%tabs.length;setActive(tabs[next]);document.getElementById('pdp-tab-'+next)?.focus();}}}>{tab}</button>)}</div>{tabs.map((tab,index)=><section key={tab} id={'pdp-panel-'+index} role="tabpanel" aria-labelledby={'pdp-tab-'+index} hidden={active!==tab} tabIndex={0}>{tab==='Description'?<><span className="eyebrow">A CLOSER LOOK</span><h2>Made to make life easier.</h2><RichContent value={product.description||''}/>{product.warranty&&<p><strong>Warranty:</strong> {product.warranty}</p>}</>:tab==='Specifications'?<><h2>Specifications</h2>{specs.length?<table><tbody>{specs.map(([key,value])=><tr key={key}><th scope="row">{key}</th><td>{value}</td></tr>)}</tbody></table>:<p>Contact our team for detailed specifications.</p>}</>:tab==='Delivery & returns'?<><h2>Delivery & returns</h2><p>Delivery options and charges are confirmed for your address at checkout.</p><p>Review our <Link href="/shipping-returns">shipping and returns policy</Link> before ordering. <Link href="/contact">Contact our team</Link> for help with delivery, installation or warranty coverage.</p></>:active==='Reviews'?<Reviews productId={product.id}/>:null}</section>)}</div>;
}
