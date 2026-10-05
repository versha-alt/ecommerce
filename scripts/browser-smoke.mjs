import { chromium } from '@playwright/test';
import { mkdirSync } from 'node:fs';
const browser=await chromium.launch({executablePath:'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true});
const page=await browser.newPage({viewport:{width:1440,height:1100}});const errors=[];page.on('pageerror',error=>errors.push(error.message));
try {
 await page.goto('http://127.0.0.1:5173',{waitUntil:'networkidle'});
 await page.getByRole('button',{name:'Use demo credentials'}).click();
 await page.getByRole('button',{name:'Sign in',exact:true}).click();
 await page.getByRole('heading',{name:'Good things are growing.'}).waitFor({timeout:20000});
 mkdirSync('tmp/screenshots',{recursive:true});
 await page.screenshot({path:'tmp/screenshots/admin-dashboard.png',fullPage:true});
 for(const name of ['Products','Brands','Categories','Attributes & specifications','Stock / inventory','Customers','Orders','Payment history / transactions','Returns & refunds','Enquiries','Coupons & discounts','Homepage content','CMS pages','Email notifications','Delivery zones & rates','Tax settings','Payment methods','Admin users & roles','Reports & analytics','Store settings','Activity log']) {
  await page.locator('nav').getByRole('button',{name:name==='Orders'?/^Orders/:name,exact:name!=='Orders'}).click();
  await page.waitForTimeout(80);
  if((await page.locator('main').innerText()).includes('Unable to load'))throw new Error(`Failed module ${name}`);
 }
 await page.locator('nav').getByRole('button',{name:'Products',exact:true}).click();
 await page.getByRole('textbox',{name:'Search Products',exact:true}).fill('washer');
 const rows=await page.locator('tbody tr').count();if(rows<1)throw new Error('Product search failed');
 await page.getByRole('button',{name:'Add product',exact:true}).click();
 const selected=await page.locator('.category-checks input:checked').count();if(selected!==0)throw new Error('Categories are preselected');
 await page.getByRole('button',{name:'Close editor'}).click();
 await page.locator('nav').getByRole('button',{name:'Overview',exact:true}).click();
 await page.setViewportSize({width:390,height:844});await page.waitForFunction(()=>document.querySelector('.sidebar').getBoundingClientRect().right<=0);
 await page.screenshot({path:'tmp/screenshots/admin-mobile.png',fullPage:true});
 const overflows=await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth);if(overflows)throw new Error('Mobile page overflows');
 if(errors.length)throw new Error(errors.join('\n'));
 console.log('Browser smoke passed: login, 21 modules, product search, unchecked categories, responsive layout.');
}finally{await browser.close();}
