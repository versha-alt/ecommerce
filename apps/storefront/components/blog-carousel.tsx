"use client";
import Link from 'next/link';
import {ArrowRight,Clock3} from 'lucide-react';
import {blogPosts} from '@/lib/blog';
import Carousel from '@/components/carousel';
export default function BlogCarousel(){
 return <section className="container section home-blog" aria-labelledby="home-blog-title"><div className="section-heading"><div><span className="eyebrow">IDEAS FOR A BETTER EVERYDAY</span><h2 id="home-blog-title">The LEEKAV journal.</h2><p>Helpful guides and a little inspiration for your home.</p></div></div><Carousel count={blogPosts.length} label="articles" trackClassName="blog-carousel-track" controlsClassName="blog-carousel-controls">{blogPosts.map(post=><article className="blog-card" key={post.slug}><Link href={'/blog/'+post.slug} className="blog-card-image" tabIndex={-1} aria-hidden="true"><img src={post.image} alt={post.alt} loading="lazy" width={640} height={400}/></Link><div className="blog-card-content"><div className="blog-card-meta"><span>{post.category}</span><span><Clock3 size={13}/>{post.minutes} min read</span></div><h3><Link href={'/blog/'+post.slug}>{post.title}</Link></h3><p>{post.excerpt}</p><Link className="blog-read-more" href={'/blog/'+post.slug}>Read article <ArrowRight size={15}/></Link></div></article>)}</Carousel></section>;
}
