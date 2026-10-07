import Link from 'next/link';
import {notFound} from 'next/navigation';
import type {Metadata} from 'next';
import {blogPosts} from '@/lib/blog';
export async function generateMetadata({params}:{params:Promise<{slug:string}>}):Promise<Metadata>{const {slug}=await params;const post=blogPosts.find(p=>p.slug===slug);return {title:post?.title??'Article',description:post?.excerpt};}
export default async function BlogArticle({params}:{params:Promise<{slug:string}>}){const {slug}=await params;const post=blogPosts.find(p=>p.slug===slug);if(!post)notFound();return <main id="main" className="container section blog-article"><div className="breadcrumbs"><Link href="/">Home</Link><span>/</span>LEEKAV journal</div><span className="eyebrow">{post.category} · {post.minutes} min read</span><h1>{post.title}</h1><p className="blog-article-intro">{post.excerpt}</p><img className="blog-article-cover" src={post.image} alt={post.alt} width={1200} height={600}/><div className="rich-content">{post.sections.map(section=><section key={section.heading}><h2>{section.heading}</h2><p>{section.text}</p></section>)}</div><Link className="text-link" href="/#home-blog-title">Explore more articles</Link></main>;}
