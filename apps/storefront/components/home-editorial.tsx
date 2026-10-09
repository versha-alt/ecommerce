"use client";
import {useState} from 'react';
import Image, {type ImageLoaderProps} from 'next/image';
import Link from 'next/link';
import {ArrowRight, ShieldCheck, Sparkles, Wind} from 'lucide-react';

/* Background photo: "Living room with a large window" by Minh Pham on Unsplash (free under the Unsplash License).
   Served straight from Unsplash's image CDN, which resizes and converts it to WebP/AVIF per screen size. */
const photo = {
  src: 'https://images.unsplash.com/photo-1583847268964-b28dc8f51f92?auto=format&fit=crop',
  alt: 'A bright, tidy living room with a sofa and large window',
  author: 'Minh Pham',
  authorUrl: 'https://unsplash.com/@minhphamdesign?utm_source=leekav&utm_medium=referral',
  sourceUrl: 'https://unsplash.com/photos/a-living-room-filled-with-furniture-and-a-large-window-OtXADkUh3-I?utm_source=leekav&utm_medium=referral',
};

const unsplashLoader = ({src, width, quality}: ImageLoaderProps) => `${src}&w=${width}&q=${quality ?? 75}`;

const shortcuts = [
  {label: 'Vacuum cleaners', href: '/categories/vacuum-cleaners', Icon: Wind},
  {label: 'Pressure washers', href: '/products?q=pressure%20washer', Icon: Sparkles},
  {label: 'Kärcher collection', href: '/brands/karcher', Icon: ShieldCheck},
];

export default function HomeEditorial({headline, link, image, imageAlt}: {headline?: string; link?: string; image?: string; imageAlt?: string}) {
  const [customFailed, setCustomFailed] = useState(false);
  const [photoFailed, setPhotoFailed] = useState(false);
  const showCustom = Boolean(image) && !customFailed;
  const showPhoto = !showCustom && !photoFailed;
  const href = link?.startsWith('/') ? link : '/brands/karcher';
  const sizes = '(max-width: 1320px) 100vw, 1240px';
  return (
    <section className="container home-editorial" aria-labelledby="home-editorial-title">
      {/* If the image can't load, the section falls back to a solid dark background (see CSS). */}
      <div className="home-editorial-media">
        {showCustom && <Image src={image!} alt={imageAlt || ''} fill sizes={sizes} onError={() => setCustomFailed(true)} />}
        {showPhoto && <Image loader={unsplashLoader} src={photo.src} alt={photo.alt} fill sizes={sizes} onError={() => setPhotoFailed(true)} />}
      </div>
      <div className="home-editorial-content">
        <span className="eyebrow">A FRESH PERSPECTIVE</span>
        <h2 id="home-editorial-title">{headline || <>Less time on chores.<br />More time for you.</>}</h2>
        <p>Meet the hardworking helpers that make a clean home feel effortless, from quick daily tidy-ups to deep weekend cleans.</p>
        <div className="home-editorial-actions">
          <Link className="button" href={href}>Discover cleaning essentials <ArrowRight size={17} aria-hidden="true" /></Link>
        </div>
        <ul className="home-editorial-shortcuts" aria-label="Shop cleaning by type">
          {shortcuts.map(({label, href: to, Icon}) => (
            <li key={label}><Link href={to}><Icon size={15} aria-hidden="true" />{label}</Link></li>
          ))}
        </ul>
      </div>
      <span className="home-editorial-tagline" aria-hidden="true">CLEANER SPACES.<br />CLEARER MINDS.</span>
</section>
  );
}
