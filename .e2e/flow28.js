// Controlled diff: assign vs generate_combos click — capture exact requests/hidden fields.
const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  const log=[];
  page.on('request', r=>{ if(r.method()==='POST') log.push({u:r.url().replace(BASE,''), b:(r.postData()||'').slice(0,250)}); });
  page.on('requestfailed', r=>log.push({FAIL:r.url().replace(BASE,''), err:r.failure()}));
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
  const pid='E2E-RR-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E RR '+pk);
  await page.click('button[name="addupdate"]'); await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
  await activateVar(pid);

  // EXPERIMENT A: click ASSIGN button (works). Capture POST.
  log.length=0;
  await page.locator('select[name="assign_category_id"]').selectOption({value:'2'});
  await page.click('input[name="assign_category_submit"]');
  await page.waitForTimeout(3500);
  console.log('=== ASSIGN CLICK requests ===');
  console.log(JSON.stringify(log,null,1));

  // EXPERIMENT B: go back to variations tab, click GENERATE_COMBOS. Capture POST.
  await activateVar(pid);
  log.length=0;
  await page.click('input[name="generate_combos"]', {timeout:8000}).catch(e=>console.log('click err', e.message.slice(0,80)));
  await page.waitForTimeout(3500);
  console.log('=== GENERATE_COMBOS CLICK requests ===');
  console.log(JSON.stringify(log,null,1));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});