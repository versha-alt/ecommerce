import Link from 'next/link';
import {notFound} from 'next/navigation';
import type {Metadata} from 'next';
import {catalog} from '@/lib/store';
import RichContent from '@/components/rich-content';
export async function generateMetadata({params}:{params:Promise<{slug:string}>}):Promise<Metadata>{const {slug}=await params;const post=(await catalog()).articles.find(article=>article.slug===slug);return {title:post?.name??'Article',description:post?.excerpt};}
export default async function BlogArticle({params}:{params:Promise<{slug:string}>}){const {slug}=await params;const post=(await catalog()).articles.find(article=>article.slug===slug);if(!post)notFound();return <main id="main" className="container section blog-article"><div className="breadcrumbs"><Link href="/">Home</Link><span>/</span>LEEKAV journal</div><span className="eyebrow">{post.category} · {post.minutes} min read</span><h1>{post.name}</h1><p className="blog-article-intro">{post.excerpt}</p><img className="blog-article-cover" src={post.image} alt={post.image_alt} width={1200} height={600}/><RichContent className="rich-content" value={post.body}/><Link className="text-link" href="/#home-blog-title">Explore more articles</Link></main>;} 
