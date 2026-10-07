import {chromium} from '@playwright/test';import assert from 'node:assert/strict';import fs from 'node:fs';
const browser=await chromium.launch({executablePath:'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true});
try {
 const page=await browser.newPage({viewport:{width:1440,height:1000},reducedMotion:'reduce'});
 await page.goto('http://10.0.0.17:3000');
 const before=JSON.parse(fs.readFileSync('tmp/banner-before.json','utf8'));const expected=['everyday-home','lg-living','midea-kitchen'];
 for(const size of [{width:1440,height:1000},{width:390,height:844}]){
 await page.setViewportSize(size);
 for(let i=0;i<3;i++){
 await page.getByRole('button',{name:new RegExp('Go to banner '+(i+1)+':')}).click();
 await page.waitForFunction(()=>{const img=document.querySelector('.hero-background img');return img?.complete&&img.naturalWidth>0;});
 const current=await page.locator('.banner-carousel').evaluate(el=>({text:el.innerText,width:el.clientWidth,height:el.clientHeight,links:[...el.querySelectorAll('a')].map(a=>a.getAttribute('href'))}));
 assert.equal(current.text,size.width===1440?before[i].text:before[i].text.replace('Find your everyday upgrade\n',''));assert.deepEqual(current.links,before[i].links);
 if(size.width===1440){assert.equal(current.width,before[i].width);assert.equal(current.height,before[i].height);}
 assert.equal(await page.locator('.hero-background img').getAttribute('src'),'/assets/banners/'+expected[i]+'.jpg');
 assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false);
 await page.locator('.banner-carousel').screenshot({path:'tmp/banner-'+size.width+'-'+(i+1)+'.png'});
 }
 }
 await page.emulateMedia({reducedMotion:'no-preference'});await page.getByRole('button',{name:/Go to banner 1:/}).click();await page.waitForTimeout(6500);assert.equal(await page.locator('.hero-background img').getAttribute('src'),'/assets/banners/lg-living.jpg');
 console.log('Three images verified on desktop/mobile; original text, links and desktop dimensions preserved; automatic carousel still works.');
}finally{await browser.close();}
