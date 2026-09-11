const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  const log=[];
  page.on('request', r=>{ if(!r.url().includes('.js')&&!r.url().includes('.css')&&!r.url().includes('.png')&&!r.url().includes('.jpg')) log.push({m:r.method(), u:r.url().replace(BASE,''), b:(r.postData()||'').slice(0,200)}); });
  page.on('console', m=>{ if(m.type()==='error') console.log('CONSOLE-ERR:', m.text().slice(0,200)); });
  page.on('pageerror', e=>console.log('PAGE-ERR:', String(e).slice(0,200)));
  async function activateVar(stock_id){
    await nav('/inventory/manage/items.php?stock_id='+stock_id); await page.waitForLoadState('networkidle');
    await page.evaluate(()=>{ const tb=document.querySelector('button[name="tabs_product_variations"]'); if(tb) tb.click(); });
    await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
  }
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');
  const pk=Date.now().toString().slice(-5);
  const pid='E2E-SS-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E SS '+pk);
  await page.click('button[name="addupdate"]'); await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
  await activateVar(pid);
  await page.locator('select[name="assign_category_id"]').selectOption({value:'2'});

  log.length=0;
  console.log('assign btn visible?', await page.locator('input[name="assign_category_submit"]').isVisible());
  await page.locator('input[name="assign_category_submit"]').click({force:true});
  await page.waitForTimeout(3500);
  console.log('REQUESTS after assign:', JSON.stringify(log,null,1));
  // read dropdown to see if it changed
  await activateVar(pid);
  const ops=await page.locator('select[name="assign_category_id"] option').allInnerTexts();
  console.log('dropdown now:', JSON.stringify(ops));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});