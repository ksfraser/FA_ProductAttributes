// Determine whether category assignment genuinely persists via REAL UI (select + button click).
// Signal: after assigning a category, it disappears from the "unassigned" dropdown.
const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  async function activateVar(stock_id){
    await nav('/inventory/manage/items.php?stock_id='+stock_id); await page.waitForLoadState('networkidle');
    await page.evaluate(()=>{ const tb=document.querySelector('button[name="tabs_product_variations"]'); if(tb) tb.click(); });
    await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
  }
  async function dropdownOptions(){ return page.locator('select[name="assign_category_id"] option').allInnerTexts(); }
  async function assignedRows(){ return page.locator('.tablestyle2 tr').allInnerTexts(); }

  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');
  const pk=Date.now().toString().slice(-5);
  const pid='E2E-JJ-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E JJ '+pk);
  await page.click('button[name="addupdate"]'); await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');

  await activateVar(pid);
  console.log('BEFORE assign dropdown:', JSON.stringify(await dropdownOptions()));
  // real UI assign of Color (value 2)
  await page.locator('select[name="assign_category_id"]').selectOption({value:'2'});
  await page.click('input[name="assign_category_submit"],button[name="assign_category_submit"]');
  await page.waitForTimeout(3000); await page.waitForLoadState('networkidle');
  await activateVar(pid);
  console.log('AFTER assign dropdown:', JSON.stringify(await dropdownOptions()));
  console.log('AFTER assigned table rows:', JSON.stringify(await assignedRows()));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});