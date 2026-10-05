import { chromium, expect } from '@playwright/test';
import { mkdirSync } from 'node:fs';
const browser=await chromium.launch({executablePath:'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true});
const page=await browser.newPage({viewport:{width:1440,height:1000}});const errors=[];page.on('pageerror',e=>errors.push(e.message));
const nav=async(name)=>page.locator('nav').getByRole('button',{name:name==='Orders'?/^Orders/:name,exact:name!=='Orders'}).click();
try {
 mkdirSync('tmp/screenshots',{recursive:true});
 await page.goto('http://127.0.0.1:5173');await page.getByRole('button',{name:'Use demo credentials'}).click();await page.getByRole('button',{name:'Sign in',exact:true}).click();await page.getByRole('heading',{name:'Good things are growing.'}).waitFor();
 await expect(page.getByRole('button',{name:'Create order',exact:true})).toHaveCount(0);
 for(const [module,label] of [['Customers','Add customer'],['Orders','Add order'],['Returns & refunds','Add return request'],['Payment history / transactions','Add payment']]){
  await nav(module);await expect(page.getByRole('button',{name:label,exact:true})).toHaveCount(0);
 }
 await nav('Products');await page.getByRole('button',{name:'Import CSV',exact:true}).click();
 const templateDownload=page.waitForEvent('download');await page.getByRole('button',{name:'Download template',exact:true}).click();if((await templateDownload).suggestedFilename()!=='products-import-template.csv')throw new Error('CSV template download failed');
 await page.getByLabel('Product CSV file').setInputFiles({name:'invalid-products.csv',mimeType:'text/csv',buffer:Buffer.from('name,sku,slug,price\nInvalid,CSV-UI-INVALID,csv-ui-invalid,-1\n')});await page.getByRole('button',{name:'Preview import',exact:true}).click();await expect(page.locator('.import-preview')).toContainText('1 invalid');await expect(page.getByRole('button',{name:/^Import \d+ products$/})).toHaveCount(0);
 await page.getByLabel('Product CSV file').setInputFiles({name:'preview-products.csv',mimeType:'text/csv',buffer:Buffer.from('name,sku,slug,price\nPreview only,CSV-UI-PREVIEW,csv-ui-preview,100\n')});await page.getByRole('button',{name:'Preview import',exact:true}).click();await expect(page.getByRole('button',{name:'Import 1 products',exact:true})).toBeEnabled();await page.screenshot({path:'tmp/screenshots/product-import.png',fullPage:true});
 await page.setViewportSize({width:390,height:844});await page.screenshot({path:'tmp/screenshots/product-import-mobile.png',fullPage:true});if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth))throw new Error('Product import overflows on mobile');await page.setViewportSize({width:1440,height:1000});await page.getByRole('button',{name:'Close product import'}).click();
 await nav('Payment history / transactions');await page.locator('tbody tr').first().click();await expect(page.getByText('Financial details are managed automatically',{exact:true})).toBeVisible();await expect(page.getByRole('textbox',{name:'Transaction reference',exact:false})).toHaveCount(0);await page.screenshot({path:'tmp/screenshots/payment-history.png',fullPage:true});await page.getByRole('button',{name:'Close editor'}).click();
 await nav('Orders');await page.locator('tbody tr').first().click();await expect(page.locator('.status-history')).toBeVisible();await expect(page.getByRole('button',{name:'Confirm payment',exact:true})).toHaveCount(0);await page.getByRole('button',{name:'Close order details'}).click();
 await nav('Coupons & discounts');await page.getByRole('button',{name:'Create discount',exact:true}).click();
 await page.screenshot({path:'tmp/screenshots/discount-types.png',fullPage:true});
 for(const name of ['Amount off products','Amount off order','Buy X get Y','Free shipping']){
  await page.locator('.discount-type-picker').getByRole('button',{name:new RegExp(name)}).click();
  await expect(page.getByLabel('Discount name',{exact:false})).toBeVisible();
  if(name==='Buy X get Y'){await expect(page.getByLabel('Qualifying quantity',{exact:false})).toBeVisible();await expect(page.getByLabel('Reward quantity',{exact:false})).toBeVisible();await page.screenshot({path:'tmp/screenshots/discount-buy-x-get-y.png',fullPage:true});}
  if(name==='Free shipping')await expect(page.getByLabel('Maximum eligible delivery charge (KES)',{exact:false})).toBeVisible();
  await page.getByRole('button',{name:'Change',exact:true}).click();
 }
 await page.getByRole('button',{name:'Close discount editor'}).click();
 await nav('Payment methods');await page.getByRole('button',{name:'Add payment method',exact:true}).click();
 await page.getByLabel('Provider',{exact:false}).selectOption('Razorpay');await expect(page.getByLabel('key secret',{exact:false})).toBeVisible();
 await page.getByLabel('Payment category',{exact:false}).selectOption('Manual');await expect(page.getByLabel('Provider',{exact:false})).toHaveValue('COD');await expect(page.getByLabel('Payment instructions',{exact:false})).toBeVisible();await expect(page.getByLabel('key secret',{exact:false})).toHaveCount(0);
 await page.screenshot({path:'tmp/screenshots/payment-methods.png',fullPage:true});await page.getByRole('button',{name:'Close payment method editor'}).click();
 await nav('Store settings');await page.locator('main').getByRole('button',{name:'Payments',exact:true}).click();await page.getByRole('button',{name:'Manage payment methods'}).click();await expect(page.getByRole('heading',{name:'Payment methods.'})).toBeVisible();
 await nav('Reports & analytics');const downloadPromise=page.waitForEvent('download');await page.getByRole('button',{name:'Export report'}).click();const download=await downloadPromise;if(!download.suggestedFilename().endsWith('.csv'))throw new Error('Report export failed');
 await page.setViewportSize({width:390,height:844});await page.getByRole('button',{name:'Open navigation'}).click();await nav('Coupons & discounts');await page.getByRole('button',{name:'Create discount',exact:true}).click();await page.locator('.discount-type-picker').getByRole('button',{name:/Buy X get Y/}).click();await page.screenshot({path:'tmp/screenshots/discount-mobile.png',fullPage:true});
 if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth))throw new Error('Mobile page overflows');
 if(errors.length)throw new Error(errors.join('\n'));
 console.log('Browser workflows passed: CSV template/upload/preview, protected creation, persisted order history, all four discount flows, online/manual payment configuration, settings navigation, CSV export and mobile layout.');
}catch(e){await page.screenshot({path:'tmp/screenshots/workflow-error.png',fullPage:true});throw e;}finally{await browser.close();}
