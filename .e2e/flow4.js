const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  const find=(s)=>page.locator('body').innerText().then(t=>t.includes(s));
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');

  const pk=Date.now().toString().slice(-5);
  const pid='E2E-R-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E R '+pk);
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
  const b0 = await page.locator('body').innerText();
  console.log('before click: has Assigned Categories =', b0.includes('Assigned Categories'));
  console.log('before click: assigned color row =', /Color|Size/.test(b0));
  // cap notifications/confirmations across whole body for THIS item context (search lower section)
  console.log('before click: contains "No categories" =', b0.includes('No categories'));
  console.log('before click: contains "No values" =', b0.includes('No values'));

  await page.click('button[name="generate_combos"],input[name="generate_combos"] >> nth=0');
  await page.waitForTimeout(3500); await page.waitForLoadState('networkidle');
  const b=await page.locator('body').innerText();
  for(const k of ['No categories assigned','No values found','Combination set saved','already up to date','No valid combinations']){
    if(b.includes(k)) console.log('>>> GENERATE_COMBOS RESULT:', k);
  }
  console.log('child button present after:', await page.locator('button[name="generate_child"],input[name="generate_child"]').count());

  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});