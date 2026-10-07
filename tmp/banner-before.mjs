import {chromium} from '@playwright/test';
import fs from 'node:fs';
const browser=await chromium.launch({executablePath:'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true});
const page=await browser.newPage({viewport:{width:1440,height:1000},reducedMotion:'reduce'});await page.goto('http://127.0.0.1:3000');
const slides=[];for(let i=0;i<3;i++){await page.getByRole('button',{name:new RegExp('Go to banner '+(i+1)+':')}).click();slides.push(await page.locator('.banner-carousel').evaluate(el=>({text:el.innerText,width:el.clientWidth,height:el.clientHeight,links:[...el.querySelectorAll('a')].map(a=>a.getAttribute('href'))})));}
fs.writeFileSync('tmp/banner-before.json',JSON.stringify(slides));await browser.close();
