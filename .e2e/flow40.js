// Instrumented: inject a submit listener, click generate_combos, see if submit fires & whether prevented.
const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: false, args: ['--no-sandbox','--disable-dev-shm-usage'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
  const log=[];
  page.on('console', m=>{ if(m.type()!=='verbose') log.push('CONSOLE['+m.type()+'] '+m.text()); });
  page.on('pageerror', e=>log.push('PAGEERR '+String(e)));
  page.on('request', r=>{ if(!/\.(js|css|png|jpg|gif|ico)$/.test(r.url())) log.push('REQ '+r.method()+' '+r.url().replace(BASE,'')); });
  page.on('framenavigated', f=>log.push('FRAMENAV '+f.url().replace(BASE,'')));
  async function openVarTab(stock_id){
    await nav('/inventory/manage/items.php?stock_id='+stock_id); await page.waitForLoadState('networkidle');
    await pause(1000);
    await page.locator('button[name="tabs_product_variations"]').first().click();
    await pause(3500); await page.waitForLoadState('networkidle');
  }
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');
  const pk=Date.now().toString().slice(-5);
  const pid='E2E-AF-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E AF '+pk);
  await page.click('button[name="addupdate"]'); await pause(2500); await page.waitForLoadState('networkidle');
  await openVarTab(pid);
  for(const value of [2,1]){
    await page.locator('select[name="assign_category_id"]').selectOption({value:String(value)});
    await page.locator('input[name="assign_category_submit"]').click();
    await pause(3500); await page.waitForLoadState('networkidle');
    await openVarTab(pid);
  }
  console.log('assigned:', JSON.stringify(await page.locator('.tablestyle2 tr').allInnerTexts()));

  // inject submit listener
  await page.evaluate(()=>{
    window.__submitLog=[];
    const f=document.querySelector('form');
    f.addEventListener('submit', e=>{ window.__submitLog.push({defaultPrevented:e.defaultPrevented, g:!!document.querySelector('input[name="generate_combos"]'), t:Date.now()}); console.log('FORMSUBMIT defaultPrevented='+e.defaultPrevented); });
    const btn=document.querySelector('input[name="generate_combos"]');
    btn.addEventListener('click', ()=>console.log('BUTTONCLICKED generate_combos'));
    // also spy on the button's form
    window.__btn = btn;
  });
  log.length=0;
  // capture the submit log storage
  await page.evaluate(()=>{ window.__submitLog=[]; });
  await page.locator('input[name="generate_combos"]').click();
  await pause(4000);
  const submitLog = await page.evaluate(()=>window.__submitLog || []);
  console.log('SUBMIT LOG (length', submitLog.length, '):', JSON.stringify(submitLog));
  console.log('EVENTS:', JSON.stringify(log.slice(0,12),null,0));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});