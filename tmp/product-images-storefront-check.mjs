import fs from 'node:fs/promises';import assert from 'node:assert/strict';import {chromium} from '@playwright/test';
const data=await (await fetch('http://127.0.0.1:8000/api/v1/store/catalog')).json();
const browser=await chromium.launch({executablePath:'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true});
const page=await browser.newPage({viewport:{width:1440,height:1000},reducedMotion:'reduce'});
async function decoded(selector){await page.locator(selector).first().waitFor();await page.locator(selector).evaluateAll(async els=>{await Promise.all(els.map(img=>img.decode()));if(els.some(img=>!img.complete||!img.naturalWidth))throw new Error('Broken image');});}
try{
 let checked=0;
 for(const p of data.products){await page.goto('http://10.0.0.17:3000/products/'+p.slug);await decoded('.main-product-photo img');assert.equal(await page.locator('.product-thumbnails button').count(),3,p.sku);await decoded('.product-thumbnails img');for(let i=1;i<3;i++){await page.getByRole('button',{name:'View product image '+(i+1),exact:true}).click();await decoded('.main-product-photo img');}checked++;}
 await page.goto('http://10.0.0.17:3000/products');await decoded('.product-card img');await page.screenshot({path:'tmp/product-images-storefront.png',fullPage:true});
 const sample=data.products.find(p=>p.stock>(p.reserved||0));await page.goto('http://10.0.0.17:3000/products/'+sample.slug);await decoded('.main-product-photo img');await page.screenshot({path:'tmp/product-images-detail.png',fullPage:true});
 await page.evaluate(id=>localStorage.setItem('olive-store-cart',JSON.stringify([{id,quantity:1}])),sample.id);await page.goto('http://10.0.0.17:3000/cart');await decoded('.cart-line img');await page.screenshot({path:'tmp/product-images-cart.png',fullPage:true});
 await page.setViewportSize({width:390,height:844});await page.goto('http://10.0.0.17:3000/products/'+sample.slug);await decoded('.main-product-photo img');await decoded('.product-thumbnails img');assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false);await page.screenshot({path:'tmp/product-images-mobile.png',fullPage:true});
 const urls=[...new Set(data.products.flatMap(p=>[p.image,...p.gallery_images]))];for(const url of urls){assert.equal((await page.request.get('http://10.0.0.17:3000'+url)).status(),200);}
 console.log('Storefront: '+checked+' published product detail pages and all galleries verified. Listing, cart, mobile and local media verified on 10.0.0.17:3000.');
}finally{await browser.close();}
