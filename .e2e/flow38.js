const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
  async function activateVar(stock_id){
    await nav('/inventory/manage/items.php?stock_id='+stock_id); await page.waitForLoadState('networkidle');
    await page.evaluate(()=>{ const tb=document.querySelector('button[name="tabs_product_variations"]'); if(tb) tb.click(); });
    await pause(2500); await page.waitForLoadState('networkidle');
  }
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');
  const pk=Date.now().toString().slice(-5);
  const pid='E2E-AC-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E AC '+pk);
  await page.click('button[name="addupdate"]'); await pause(2500); await page.waitForLoadState('networkidle');
  await activateVar(pid);

  const before = await page.content().then(c=>c.length);
  await page.locator('select[name="assign_category_id"]').selectOption({value:'4'}); // Shoe Size (unassigned)
  await page.click('input[name="assign_category_submit"]');
  await pause(3500); await page.waitForLoadState('networkidle');
  const after = await page.content().then(c=>c.length);
  console.log('ASSIGN page.content before/after:', before, after, '(diff', after-before,')');
  const dd=await page.locator('select[name="assign_category_id"] option').allInnerTexts();
  console.log('assign dropdown after:', JSON.stringify(dd));
  const rows=await page.locator('.tablestyle2 tr').allInnerTexts();
  console.log('assigned table after:', JSON.stringify(rows));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});