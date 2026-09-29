const {chromium}=require(process.env.PLAYWRIGHT_PATH||'playwright');
const {spawn}=require('node:child_process');
const fs=require('node:fs');
const path=require('node:path');
const assert=require('node:assert/strict');
const root=path.resolve(__dirname,'..');
const folder=fs.mkdtempSync('/tmp/traplink-templates-test-');
let server,browser;
(async()=>{
 assert.ok(process.env.TEMPLATE_TEST_ALPINE,'Set TEMPLATE_TEST_ALPINE to the Alpine.js script path');
 server=spawn('php',['-S','127.0.0.1:8767','-t',root,path.join(__dirname,'templates-router.php')],{env:{...process.env,TEMPLATE_TEST_DB:path.join(folder,'test.sqlite')}});
 let serverErrors='';server.stderr.on('data',b=>serverErrors+=b.toString());
 await new Promise((resolve,reject)=>{const timeout=setTimeout(()=>reject(new Error('PHP startup timeout: '+serverErrors)),10000);server.stderr.on('data',b=>{if(b.toString().includes('Development Server')){clearTimeout(timeout);resolve();}});server.on('error',reject);});
 browser=await chromium.launch({channel:'chrome',headless:true});
 const page=await browser.newPage({viewport:{width:1440,height:1000}});const errors=[];page.on('pageerror',e=>errors.push(e.message));
 const base='http://127.0.0.1:8767';await page.goto(base+'/admin/?tab=templates');
 await page.waitForFunction(()=>document.querySelectorAll('.tpl-card').length===7);
 assert.equal(await page.getByRole('button',{name:'Создать',exact:true}).count(),7);
 await page.getByRole('button',{name:'Предпросмотр: Личная визитка',exact:true}).click();
 await page.locator('.tpl-preview-dialog iframe').waitFor();
 await page.frameLocator('.tpl-preview-dialog iframe').getByRole('heading',{name:'Анна Иванова',exact:true}).waitFor();assert.equal(await page.locator('.tpl-preview-dialog iframe').getAttribute('sandbox'),'');
 await page.getByRole('button',{name:'Закрыть предпросмотр'}).click();
 for(const name of ['Простая мультиссылка','Автор и соцсети','Кофейня']) {
  await page.getByRole('button',{name:'Предпросмотр: '+name,exact:true}).click();
  const photo=page.frameLocator('.tpl-preview-dialog iframe').locator('img').first();
  await photo.waitFor();await photo.evaluate(img=>img.decode());assert.ok(await photo.evaluate(img=>img.naturalWidth>0));
  await page.getByRole('button',{name:'Закрыть предпросмотр'}).click();
 }
 await page.getByRole('button',{name:'Мои шаблоны',exact:true}).click();await page.locator('.tpl-empty:visible').waitFor();
 await page.locator('#save-fixture').click();await page.getByLabel('Название',{exact:true}).fill('Клиентский шаблон');await page.getByLabel('Описание',{exact:true}).fill('Мой сохранённый макет');
 await page.getByRole('button',{name:'Сохранить шаблон',exact:true}).click();await page.getByRole('heading',{name:'Шаблон сохранён'}).waitFor();await page.getByRole('button',{name:'Готово',exact:true}).click();
 await page.getByRole('heading',{name:'Клиентский шаблон',exact:true}).waitFor();assert.equal(await page.locator('.tpl-card').count(),1);
 await page.getByRole('searchbox').fill('нет такого');await page.getByRole('heading',{name:'Ничего не найдено'}).waitFor();await page.getByRole('searchbox').fill('');
 const before=await (await page.request.get(base+'/admin/api.php?action=fixturePages')).json();
 // Simulate losing the response AFTER the server commits, then retry the same token.
 let intercepted=false;
 await page.route('**/admin/api.php?action=createFromTemplate',async route=>{if(!intercepted){intercepted=true;await route.fetch();await route.abort('failed');}else await route.continue();});
 await page.getByRole('button',{name:'Создать',exact:true}).click();await page.locator('.tpl-error:visible').first().waitFor();
 await page.getByRole('button',{name:'Создать',exact:true}).click();await page.waitForURL('**/admin/?page_id=*');
 const after=await (await page.request.get(base+'/admin/api.php?action=fixturePages')).json();assert.equal(after.length,before.length+1);
 assert.equal(after.at(-1).title,'Клиентский шаблон');
 await page.goto(base+'/admin/?tab=templates');await page.waitForFunction(()=>document.querySelectorAll('.tpl-card').length===8);
 await page.frameLocator('.tpl-preview iframe').first().getByRole('heading',{name:'Анна Иванова',exact:true}).waitFor();
 
 await page.screenshot({path:'/tmp/traplink-templates-desktop.png',fullPage:true});
 await page.getByRole('button',{name:'Мои шаблоны',exact:true}).click();page.once('dialog',d=>d.accept());await page.getByRole('button',{name:'Удалить шаблон Клиентский шаблон'}).click();await page.locator('.tpl-empty:visible').waitFor();
 assert.equal((await (await page.request.get(base+'/admin/api.php?action=fixturePages')).json()).length,after.length);
 await page.getByRole('button',{name:'Предустановленные',exact:true}).click();await page.setViewportSize({width:390,height:844});await page.screenshot({path:'/tmp/traplink-templates-mobile.png',fullPage:true});
 assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'no mobile horizontal overflow');
 assert.deepEqual(errors,[]);assert.ok(!serverErrors.includes('Fatal error'),serverErrors);
 console.log('PASS browser: catalog, preview isolation, filters, search, save snapshot, lost-response retry without duplicates, redirect, delete preserving pages, mobile layout');
})().catch(e=>{console.error(e);process.exitCode=1;}).finally(async()=>{if(browser)await browser.close();if(server)server.kill();fs.rmSync(folder,{recursive:true,force:true});});
