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
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');
  const pk=Date.now().toString().slice(-5);
  const pid='E2E-QQ-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E QQ '+pk);
  await page.click('button[name="addupdate"]'); await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
  for(const value of [2,1]){
    await activateVar(pid);
    await page.locator('select[name="assign_category_id"]').selectOption({value:String(value)});
    await page.click('input[name="assign_category_submit"],button[name="assign_category_submit"]');
    await page.waitForTimeout(3000); await page.waitForLoadState('networkidle');
  }
  await activateVar(pid);
  const diag = await page.evaluate(()=>{
    const gc=document.querySelector('input[name="generate_combos"]');
    const f=document.querySelector('form');
    return {
      gcInForm: gc ? f.contains(gc) : false,
      gcDisabled: gc? gc.disabled : null,
      formAction: f? f.getAttribute('action') : null,
      formOnsubmit: f? f.getAttribute('onsubmit') : null,
      formMethod: f? f.getAttribute('method') : null,
      // any JS listeners? can't introspect, but check class of submit
      gcClosest: gc? gc.parentElement.tagName + ' > ' + gc.parentElement.parentElement.tagName : null,
    };
  });
  console.log(JSON.stringify(diag,null,1));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});
