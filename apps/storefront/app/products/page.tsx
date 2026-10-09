import {headers} from 'next/headers';
import {redirect} from 'next/navigation';
import {catalog} from '@/lib/store';
import {normalizeCatalogQuery,catalogSeoTitle,catalogDescription,catalogPath} from '@/lib/catalog-listing';
import Listing from '@/components/listing';

type Props={searchParams:Promise<Record<string,string|string[]|undefined>>};
const values=(input:Record<string,string|string[]|undefined>)=>Object.fromEntries(Object.entries(input).flatMap(([key,value])=>value===undefined?[]:[[key,Array.isArray(value)?value.join(','):value]]));
async function origin(){const h=await headers();return process.env.NEXT_PUBLIC_SITE_URL||`${h.get('x-forwarded-proto')||'http'}://${h.get('host')||'localhost:3000'}`;}
export async function generateMetadata({searchParams}:Props){const data=await catalog(),query=normalizeCatalogQuery(data,values(await searchParams));return {title:catalogSeoTitle(data,query),description:catalogDescription(data,query),alternates:{canonical:new URL(catalogPath(query),await origin()).href}};}
export default async function Products({searchParams}:Props){const data=await catalog(),input=values(await searchParams),query=normalizeCatalogQuery(data,input);if(query.brand!==input.brand||query.category!==input.category)redirect(catalogPath(query));return <Listing data={data} siteOrigin={await origin()}/>;}
