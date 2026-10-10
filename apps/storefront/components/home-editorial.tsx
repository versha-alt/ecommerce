"use client";
import {useState} from 'react';
import Image from 'next/image';
import Link from 'next/link';
import {ArrowRight} from 'lucide-react';

export default function HomeEditorial({eyebrow, headline, body, link, linkLabel, image, imageAlt}: {eyebrow?: string; headline: string; body?: string; link?: string; linkLabel?: string; image?: string; imageAlt?: string}) {
  const [customFailed, setCustomFailed] = useState(false);
  const showCustom = Boolean(image) && !customFailed;
  const href = link || '#';
  const sizes = '(max-width: 1320px) 100vw, 1240px';
  return (
    <section className="container home-editorial" aria-labelledby="home-editorial-title">
      {/* If the image can't load, the section falls back to a solid dark background (see CSS). */}
      <div className="home-editorial-media">
        {showCustom && <Image src={image!} alt={imageAlt || ''} fill sizes={sizes} onError={() => setCustomFailed(true)} />}
      </div>
      <div className="home-editorial-content">
        <span className="eyebrow">{eyebrow}</span>
        <h2 id="home-editorial-title">{headline}</h2>
        {body&&<p>{body}</p>}
        <div className="home-editorial-actions">
          <Link className="button" href={href}>{linkLabel||'Explore'} <ArrowRight size={17} aria-hidden="true" /></Link>
        </div>
      </div>
      <span className="home-editorial-tagline" aria-hidden="true">CLEANER SPACES.<br />CLEARER MINDS.</span>
</section>
  );
}
