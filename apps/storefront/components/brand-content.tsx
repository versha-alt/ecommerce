import Link from 'next/link';
import {slugify} from '@/lib/store';
import RichContent from './rich-content';
import PaymentBadges from './payment-badges';

// Reuse the existing admin-managed brand description and image.
export default function BrandContent({brand,brands,image}:{brand:any;brands:any[];image?:string}){
 const faqs=[
  {question:`How do I choose a ${brand.name} product?`,answer:'Check dimensions, capacity and key specifications on the product page. Contact our team if you need help choosing.'},
  {question:'Where can I check warranty coverage?',answer:'Review the warranty information on the product page, or contact our team to confirm coverage before ordering.'},
  {question:'How do I confirm delivery and payment options?',answer:'Enter your delivery location at checkout to review available delivery options, charges and payment methods.'},
 ];
 const schema={'@context':'https://schema.org','@type':'FAQPage',mainEntity:faqs.map(({question,answer})=>({'@type':'Question',name:question,acceptedAnswer:{'@type':'Answer',text:answer}}))};
 return <section className="container brand-content">
  {brand.description&&<section className="brand-story"><div><h2>Discover {brand.name}</h2><RichContent value={brand.description}/></div>{image&&<img src={image} alt={`${brand.name} appliances in a home`} width={900} height={600} loading="lazy"/>}</section>}
  <section className="brand-faq"><h2>A little help choosing</h2>{faqs.map(({question,answer})=><details key={question}><summary>{question}</summary><p>{answer}</p></details>)}</section>
  <section className="brand-explore"><h2>Explore other brands</h2><div>{brands.filter(other=>other.id!==brand.id).map(other=><Link key={other.id} href={'/brands/'+slugify(other.name)} aria-label={'Shop '+other.name}>{other.logo||other.image?<img src={other.logo||other.image} alt={other.name} width={130} height={50} loading="lazy"/>:<span>{other.name}</span>}</Link>)}</div></section>
  <PaymentBadges/>
  <script type="application/ld+json" dangerouslySetInnerHTML={{__html:JSON.stringify(schema).replaceAll('<','\\u003c')}}/>
 </section>;
}
