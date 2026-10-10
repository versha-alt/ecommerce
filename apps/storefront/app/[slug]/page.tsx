import type {Metadata} from 'next';
import {notFound} from 'next/navigation';
import {Mail,MapPin,MessageCircle,Phone} from 'lucide-react';
import {catalog} from '@/lib/store';
import RichContent from '@/components/rich-content';
import Contact from '@/components/contact';

export async function generateMetadata({params}:{params:Promise<{slug:string}>}):Promise<Metadata>{
 const {slug}=await params;
 const page=(await catalog()).pages.find(item=>item.slug===slug);
 return {title:page?.seo_title||page?.name||'Information',description:page?.seo_description||undefined};
}

export default async function Information({params}:{params:Promise<{slug:string}>}){
 const {slug}=await params;
 const data=await catalog();
 const page=data.pages.find(item=>item.slug===slug);
 if(!page)notFound();
 const settings=data.settings;
 const whatsapp=(settings.whatsapp||settings.phone||'').replace(/[^0-9]/g,'');
 return <main id="main" className="container information-page"><div className="information-heading"><span className="eyebrow">{slug==='contact'?'HERE TO HELP':'THE LEEKAV WAY'}</span><h1>{page.name}</h1></div>{slug==='contact'?<><div className="contact-layout"><aside className="contact-details"><h2>Let&apos;s talk.</h2><p>Include your order reference when contacting us about an existing order.</p>{settings.phone&&<a href={'tel:'+settings.phone}><Phone size={19}/>{settings.phone}</a>}{settings.email&&<a href={'mailto:'+settings.email}><Mail size={19}/>{settings.email}</a>}{settings.address&&<a href={'https://www.google.com/maps/search/?api=1&query='+encodeURIComponent(settings.address)} target="_blank" rel="noreferrer"><MapPin size={19}/>{settings.address}</a>}{whatsapp&&<a href={'https://wa.me/'+whatsapp} target="_blank" rel="noreferrer"><MessageCircle size={19}/>Chat on WhatsApp</a>}</aside><Contact/></div><RichContent value={page.body} className="information-body contact-cms-body"/></>:<RichContent value={page.body} className="information-body"/>}</main>;
}
