const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  const nav = (p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');

  const pid = 'MECHC-560606'; // created by previous run
  // edit + submit _tabs_sel=product_variations via form.submit()
  await nav('/inventory/manage/items.php?stock_id='+pid); await page.waitForLoadState('networkidle');
  await page.evaluate(()=>{
    const f=document.querySelector('form'); if(!f) return;
    const el=f.querySelector('input[name="_tabs_sel"]')||f.querySelector('select[name="_tabs_sel"]');
    if(el) el.value='product_variations';
    f.submit();
  });
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(2500);
  const html=await page.content();
  console.log('--- after variations-tab submit ---');
  console.log('assign_category_id select:', await page.locator('select[name="assign_category_id"]').count());
  console.log('generate_combos btn:', await page.locator('input[name="generate_combos"]').count());
  console.log('generate_child btn:', await page.locator('input[name="generate_child"]').count());
  console.log('create_child btn:', await page.locator('input[name="create_child"]').count());
  const subs = await page.locator('button[type="submit"], input[type="submit"]').evaluateAll(es=>es.map(e=>({n:e.name,t:e.tagName,v:e.value||e.innerText})));
  console.log('SUBMIT/tab elems:', JSON.stringify(subs).slice(0,600));
  // tab headers
  const tabSel = await page.locator('select[name="_tabs_sel"]').count();
  console.log('has _tabs_sel select:', tabSel);
  if(tabSel){
    const opts = await page.locator('select[name="_tabs_sel"] option').allInnerTexts();
    console.log('TAB OPTIONS:', JSON.stringify(opts));
  }
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});