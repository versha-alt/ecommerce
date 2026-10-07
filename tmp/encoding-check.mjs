import {chromium} from '@playwright/test';
import assert from 'node:assert/strict';
const browser=await chromium.launch({executablePath:'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true});
try {
 const page=await browser.newPage();
 await page.goto('http://127.0.0.1:5173');
 await page.getByRole('button',{name:'Use demo credentials'}).click();
 await page.getByRole('button',{name:'Sign in',exact:true}).click();
 await page.waitForFunction(()=>sessionStorage.getItem('olive-token'));
 for(const route of ['products','orders','categories']) {
  await page.goto('http://127.0.0.1:5173/#'+route);
  await page.waitForTimeout(1200);
  const text=await page.locator('body').innerText();
  assert.ok(!/[\u00c3\u00c2\ufffd]|\u00e2[\u20ac\u0080]/u.test(text),route+' has garbled text');
 }
 console.log('Products, orders and categories UI: no garbled encoding characters.');
} finally {await browser.close();}
