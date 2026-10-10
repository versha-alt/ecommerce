import '@fontsource-variable/bricolage-grotesque';
import '@fontsource-variable/inter';
import Analytics from '@/components/analytics';
import type {Metadata} from 'next';
import {catalog} from '@/lib/store';
import {StoreProvider,Header,Footer} from '@/components/store';
import CatalogSync from '@/components/catalog-sync';
import './globals.css';
import './mega-menu.css';
import './homepage-premium.css';
import './homepage-theme.css';
export const dynamic='force-dynamic';
export async function generateMetadata():Promise<Metadata>{const data=await catalog();const favicon=data.settings.favicon||'/favicon.png';const storeName=data.settings.store_name||'LEEKAV';return {metadataBase:new URL(process.env.NEXT_PUBLIC_SITE_URL||'http://127.0.0.1:3000'),title:{default:data.settings.site_title||storeName,template:`%s | ${storeName}`},description:data.settings.site_description||undefined,icons:{icon:favicon,apple:favicon}};}
export default async function Layout({children}:{children:React.ReactNode}){const data=await catalog();return <html lang="en"><body><StoreProvider data={data}><CatalogSync/><a className="skip-link" href="#main">Skip to content</a><Header/>{children}<Footer/><Analytics settings={data.settings}/></StoreProvider></body></html>}

