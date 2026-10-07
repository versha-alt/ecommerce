import {chromium} from '@playwright/test';
import assert from 'node:assert/strict';
const catalog=await(await fetch('http://127.0.0.1:8000/api/v1/store/catalog')).json();
const product=catalog.products.find(p=>p.type==='Simple'&&p.stock-(p.reserved||0)>0);
const browser=await chromium.launch({executablePath:'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true});
try{
 for(const mode of ['success','missing','failed']){
 const page=await browser.newPage();
 await page.addInitScript(id=>localStorage.setItem('olive-store-cart',JSON.stringify([{id,quantity:1}])),product.id);
 await page.route('**/api/store/quote',r=>r.fulfill({json:{subtotal:1000,total:1500,shipping_total:500,tax_total:0,discount:0}}));
 await page.route('**/api/store/checkout',r=>r.fulfill({status:mode==='failed'?422:200,json:mode==='success'?{order:{id:'test-created-order',reference:'TEST-SUCCESS'}}:mode==='missing'?{order:{}}:{message:'Order could not be created'}}));
 await page.route('**/api/store/orders/test-created-order',r=>r.fulfill({status:404,json:{message:'Mock order'}}));
 await page.goto('http://127.0.0.1:3000/checkout');
 await page.getByRole('textbox',{name:'Full name',exact:true}).fill('Test buyer');
 await page.getByRole('textbox',{name:'Email address',exact:true}).fill('test@example.com');
 await page.getByRole('textbox',{name:'Phone number',exact:true}).fill('0700000000');
 await page.locator('label').filter({hasText:/^County/}).locator('select').selectOption('047');
 await page.getByLabel('Street address, building and delivery directions').fill('Test address');
 await page.locator('label').filter({hasText:/^Delivery option/}).locator('select').selectOption({index:1});
 await page.locator('input[name="payment"]').first().check();
 await page.getByRole('button',{name:'Review order'}).click();
 await page.getByRole('button',{name:'Place order'}).click();
 if(mode==='success'){
 await page.getByRole('heading',{name:'Your order has been placed successfully!'}).waitFor();
 assert(page.url().endsWith('/checkout'));
 assert.equal(await page.locator('.empty-shopping').count(),0);
 await page.waitForTimeout(3000);
 assert(page.url().endsWith('/checkout'));
 await page.getByRole('link',{name:'View order details'}).click();
 await page.waitForURL('**/orders/test-created-order');
 }else{
 await page.locator('.form-feedback.error').waitFor();
 await page.waitForTimeout(2400);
 assert(page.url().endsWith('/checkout'));
 assert.equal(await page.locator('.checkout-success').count(),0);
 assert(JSON.parse(await page.evaluate(()=>localStorage.getItem('olive-store-cart'))).length>0);
 }
 await page.close();
 }
 console.log('Checkout success confirmation, click-only navigation, missing ID and failed checkout verified with mocked API; no real orders or emails.');
}finally{await browser.close();}
