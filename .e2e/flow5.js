const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  page.on('console', m=>console.log('CONSOLE:', m.type(), m.text().slice(0,150)));
  page.on('request', r=>{ if(r.isNavigationRequest()) console.log('NAV->', r.method(), r.url()); });
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');

  const pk=Date.now().toString().slice(-5);
  const pid='E2E-S-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E S '+pk);
  await page.click('button[name="addupdate"]'); await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');

  async function openVarTab(){
    await page.evaluate(()=>{ const b=document.querySelector('button[name="tabs_product_variations"]'); if(b) b.click(); });
    await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
  }
  await nav('/inventory/manage/items.php?stock_id='+pid); await page.waitForLoadState('networkidle');
  await openVarTab();
  for(const cid of ['2','1']){
    await page.locator('select[name="assign_category_id"]').selectOption({value:cid});
    await page.click('button[name="assign_category_submit"],input[name="assign_category_submit"]');
    await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
  }
  await openVarTab();
  console.log('URL before click:', page.url());
  console.log('generate_combos count before:', await page.locator('button[name="generate_combos"],input[name="generate_combos"]').count());
  await page.click('button[name="generate_combos"],input[name="generate_combos"] >> nth=0');
  await page.waitForTimeout(4000);
  console.log('URL after click:', page.url());
  const b=await page.locator('body').innerText();
  console.log('has New item header:', b.includes('New item'));
  console.log('has Generate Combinations text anywhere:', b.includes('Generate Combinations'));
  console.log('has Generate Child text:', b.includes('Generate Child'));
  // find the item list / whether we navigated to index
  console.log('URL after wait:', page.url());
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});