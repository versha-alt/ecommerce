import Link from 'next/link';
export default function NotFound(){return <main className="container centered"><span className="eyebrow">404</span><h1>This page has moved.</h1><p>Discover something useful in our collection instead.</p><Link href="/products" className="button">Explore products</Link></main>}
