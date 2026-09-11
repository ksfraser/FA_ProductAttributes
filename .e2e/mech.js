// Mechanics probe: create item, then render the product_variations tab and dump its widgets.
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

  const pk = Date.now().toString().slice(-6);
  const pid = 'MECH-'+pk;
  // create item
  await nav('/inventory/manage/items.php?NewItem=1');
  await page.waitForLoadState('networkidle');
  console.log('form has addupdate btn:', await page.locator('input[name="addupdate"]').count());
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]', 'Mech '+pk);
  await page.click('input[name="addupdate"]');
  await page.waitForLoadState('networkidle');
  console.log('after insert URL:', page.url());

  // now edit + submit _tabs_sel=product_variations
  await nav('/inventory/manage/items.php?stock_id='+pid);
  await page.waitForLoadState('networkidle');
  const stockInForm = await page.evaluate(()=>{
    const f=document.querySelector('form');
    const el=f.querySelector('input[name="stock_id"]')||f.querySelector('select[name="stock_id"]');
    return el? el.value : 'NO-stock_id-field';
  });
  console.log('edit-page stock_id field value:', JSON.stringify(stockInForm));
  // submit the form with _tabs_sel=product_variations
  await page.evaluate(()=>{
    const f=document.querySelector('form');
    const el=f.querySelector('input[name="_tabs_sel"]')||f.querySelector('select[name="_tabs_sel"]');
    if(el) el.value='product_variations';
    f.submit();
  });
  await page.waitForLoadState('networkidle');
  console.log('after tab-submit URL:', page.url());
  const html = await page.content();
  const has = (s)=> html.includes(s);
  console.log('--- VARIATIONS TAB CONTENT ---');
  console.log('Assign Category select:', await page.locator('select[name="assign_category_id"]').count());
  console.log('Generate Combinations button:', await page.locator('input[name="generate_combos"]').count());
  console.log('Generate Child button:', await page.locator('input[name="generate_child"]').count());
  console.log('Create Child button:', await page.locator('input[name="create_child"]').count());
  console.log('Existing Variations header:', has('Existing Variations'));
  console.log('page body snippet:', html.slice(0,120).replace(/\n/g,' '));
  // dump which submits are present
  const subs = await page.locator('input[type="submit"]').evaluateAll(es=>es.map(e=>({n:e.name,v:e.value})));
  console.log('SUBMITS:', JSON.stringify(subs));
  // dump selects
  const sels = await page.locator('select[name]').evaluateAll(es=>es.map(e=>e.name));
  console.log('SELECTS:', JSON.stringify(sels));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});