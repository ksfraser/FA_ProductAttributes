const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');

  // create scratch item
  const pk=Date.now().toString().slice(-5);
  const pid='E2E-P-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E Parent '+pk);
  await page.click('button[name="addupdate"]'); await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');

  async function openVarTab(){
    await page.evaluate(()=>{ const b=document.querySelector('button[name="tabs_product_variations"]'); if(b) b.click(); });
    await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
  }
  // read assign_category_id options (ids+labels) from variations tab
  await nav('/inventory/manage/items.php?stock_id='+pid); await page.waitForLoadState('networkidle');
  await openVarTab();
  const opts = await page.locator('select[name="assign_category_id"] option').evaluateAll(os=>os.map(o=>({v:o.value,t:o.text})));
  console.log('ASSIGN CATEGORY OPTIONS (id/name):', JSON.stringify(opts));
  const color = opts.find(o=>o.t==='Color'); const size = opts.find(o=>o.t==='Size');
  console.log('Color id:', color&&color.v, ' Size id:', size&&size.v);

  // assign Color + Size
  async function assign(cid){
    await nav('/inventory/manage/items.php?stock_id='+pid); await page.waitForLoadState('networkidle');
    await openVarTab();
    await page.locator('select[name="assign_category_id"]').selectOption({value:cid});
    await page.click('button[name="assign_category_submit"],input[name="assign_category_submit"]');
    await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
  }
  if(color) await assign(color.v);
  if(size) await assign(size.v);
  console.log('assigned Color('+(color&&color.v)+') Size('+(size&&size.v)+')');

  // click generate_combos
  await nav('/inventory/manage/items.php?stock_id='+pid); await page.waitForLoadState('networkidle');
  await openVarTab();
  await page.click('button[name="generate_combos"],input[name="generate_combos"] >> nth=0');
  await page.waitForTimeout(3000); await page.waitForLoadState('networkidle');
  const body=await page.locator('body').innerText();
  console.log('AFTER GENERATE_COMBOS:');
  ['No categories','No values','Combination set saved','already up to date'].forEach(k=>{ if(body.includes(k)) console.log('   says:', k); });
  console.log('   exact snippet:', body.match(/(No categories[^\n]*|No values[^\n]*|Combination set[^\n]*|already up to date[^\n]*)/)?.[0]);

  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});