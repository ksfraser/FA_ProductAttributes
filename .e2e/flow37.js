// Determine if generate_combos click changes the DOM at all (byte-diff innerHTML before/after).
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
  const pid='E2E-AB-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E AB '+pk);
  await page.click('button[name="addupdate"]'); await pause(2500); await page.waitForLoadState('networkidle');
  for(const value of [2,1]){
    await activateVar(pid);
    await page.locator('select[name="assign_category_id"]').selectOption({value:String(value)});
    await page.click('input[name="assign_category_submit"]');
    await pause(3000); await page.waitForLoadState('networkidle');
  }
  await activateVar(pid);

  const before = await page.evaluate(()=>document.querySelector('form').innerHTML.length);
  const beforeHtml = await page.content();
  await page.click('input[name="generate_combos"]');
  await pause(3500);
  const after = await page.evaluate(()=>{ try{ return document.querySelector('form').innerHTML.length; }catch(e){return -1;} });
  const afterHtml = await page.content();
  console.log('form innerHTML len before/after:', before, after, '(diff', after-before,')');
  console.log('page.content len before/after:', beforeHtml.length, afterHtml.length, '(diff', afterHtml.length-beforeHtml.length,')');
  // look in the after page for any combo-related message
  const t=afterHtml.replace(/<[^>]*>/g,' ').replace(/\s+/g,' ');
  const m=(t.match(/(Combination set[^.]{0,80}|No categories[^.]{0,50}|No values[^.]{0,50}|up to date[^.]{0,40}|Invalid stock[^.]{0,40})/g)||[]);
  console.log('combo msgs in after page:', JSON.stringify(m));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});