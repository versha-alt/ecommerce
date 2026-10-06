"use client";
export default function ErrorPage({reset}:{reset:()=>void}){return <main className="container centered"><h1>Let us try that again.</h1><p>We could not load this page. Please try again shortly.</p><button className="button" onClick={reset}>Try again</button></main>}
