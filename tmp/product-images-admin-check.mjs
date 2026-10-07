import fs from 'node:fs/promises';
import assert from 'node:assert/strict';
import {chromium} from '@playwright/test';
const products=JSON.parse(await fs.readFile('tmp/product-images-after.json','utf8'));
const browser=await chromium.launch({executablePath:'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true});
const context=await browser.newContext({viewport:{width:1440,height:1000}});
const page=await context.newPage();
async function images(selector){await page.locator(selector).first().waitFor();await page.locator(selector).evaluateAll(async els=>{await Promise.all(els.map(img=>img.decode()));if(els.some(img=>!img.complete||img.naturalWidth===0))throw new Error('Broken image');});}
try{
 await page.goto('http://10.0.0.17:5173');await page.getByRole('button',{name:'Use demo credentials'}).click();await page.getByRole('button',{name:'Sign in',exact:true}).click();await page.locator('.workspace-name').waitFor();
 await page.goto('http://10.0.0.17:5173/#products');await page.locator('table tbody tr').first().waitFor();
 let rows=0;for(;;){await images('.product-thumb img');rows+=await page.locator('table tbody tr').count();const next=page.locator('.table-pagination').getByRole('button',{name:'Next',exact:true});if(await next.isDisabled())break;await next.click();}
 assert.equal(rows,products.length);await page.locator('.table-pagination').getByRole('button',{name:'Previous',exact:true}).click();
 await page.getByRole('button',{name:/^Open /}).first().click();await images('.drawer img');assert.equal(await page.locator('.drawer img').count(),3);
 await page.screenshot({path:'tmp/product-images-admin.png',fullPage:true});
 const urls=[...new Set(products.flatMap(p=>[p.image,...p.gallery_images]))];
 for(const url of urls){const response=await context.request.get('http://10.0.0.17:5173'+url);assert.equal(response.status(),200);assert.match(response.headers()['content-type'],/image/);}
 console.log('Admin: all '+rows+' product thumbnails and three-image editor verified. All '+urls.length+' local media files load through WireGuard address.');
}finally{await browser.close();}

