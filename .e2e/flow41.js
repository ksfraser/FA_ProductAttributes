const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: false, args: ['--no-sandbox','--disable-dev-shm-usage'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
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
  const pid='E2E-AG-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E AG '+pk);
  await page.click('button[name="addupdate"]'); await pause(2500); await page.waitForLoadState('networkidle');
  await openVarTab(pid);
  for(const value of [2,1]){
    await page.locator('select[name="assign_category_id"]').selectOption({value:String(value)});
    await page.locator('input[name="assign_category_submit"]').click();
    await pause(3500); await page.waitForLoadState('networkidle');
    await openVarTab(pid);
  }
  const info = await page.evaluate(()=>{
    const forms=Array.from(document.querySelectorAll('form'));
    const gc=document.querySelector('input[name="generate_combos"]');
    const ts=document.querySelector('input[name="_tabs_sel"]');
    const as=document.querySelector('input[name="assign_category_submit"]');
    const fid=(el)=>{ const f=el?el.form:null; return f? ('#'+(f.id||'')+' name='+(f.name||'')+' idx='+forms.indexOf(f)) : 'NONE'; };
    return {
      formsTotal: forms.length,
      gcForm: fid(gc),
      asForm: fid(as),
      tsForm: fid(ts),
      gcInFormWithTs: gc && ts ? gc.form===ts.form : false,
      gcInFormWithAs: gc && as ? gc.form===as.form : false,
      tsInside: ts? (ts.closest('div')? ts.closest('div').id : null) : null,
      // does the form that contains the tab bar contain the buttons too
      tabsAreInGcForm: document.querySelector('button[name="tabs_product_variations"]') ? document.querySelector('button[name="tabs_product_variations"]').form===gc.form : null,
    };
  });
  console.log(JSON.stringify(info,null,1));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});