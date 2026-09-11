const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  const captures=[];
  page.on('request', async r=>{
    if(r.isNavigationRequest()) return;
    const pu=r.postData(); if(pu && pu.includes('generate')) captures.push({url:r.url().slice(-60), body:pu.slice(0,600)});
  });
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');
  const pk=Date.now().toString().slice(-5);
  const pid='E2E-W-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E W '+pk);
  await page.click('button[name="addupdate"]'); await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
  async function openVarTab(){ await page.evaluate(()=>{ const b=document.querySelector('button[name="tabs_product_variations"]'); if(b) b.click(); }); await page.waitForTimeout(2500); await page.waitForLoadState('networkidle'); }
  await nav('/inventory/manage/items.php?stock_id='+pid); await page.waitForLoadState('networkidle');
  await openVarTab();
  for(const cid of ['2','1']){
    await page.locator('select[name="assign_category_id"]').selectOption({value:cid});
    await page.click('button[name="assign_category_submit"],input[name="assign_category_submit"]');
    await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
  }
  await openVarTab();
  captures.length=0;
  const nb = page.locator('button[name="generate_combos"],input[name="generate_combos"]').first();
  console.log('combos button visible:', await nb.isVisible());
  await nb.click({force:true});
  await page.waitForTimeout(3000);
  console.log('captured posts with generate:', captures.length);
  for(const c of captures){ console.log('URL:', c.url); console.log('BODY:', c.body); }
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});