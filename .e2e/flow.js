const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  const nav = (p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  const LOG=[]; const log=(m)=>LOG.push(m);
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');

  // create parent
  const pk=Date.now().toString().slice(-5);
  const pid='E2E-F-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E Flow '+pk);
  await page.click('button[name="addupdate"]'); await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
  console.log('created', pid);

  // helper to open variations tab
  async function openVarTab(){
    await page.evaluate(()=>{ const b=document.querySelector('button[name="tabs_product_variations"]'); if(b) b.click(); });
    await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
  }
  // ensure we're on edit page then open tab
  await nav('/inventory/manage/items.php?stock_id='+pid); await page.waitForLoadState('networkidle');
  await openVarTab();
  console.log('variations tab buttons:', {
    assign: await page.locator('select[name="assign_category_id"]').count(),
    combos: await page.locator('button[name="generate_combos"],input[name="generate_combos"]').count(),
    child: await page.locator('button[name="generate_child"],input[name="generate_child"]').count(),
  });
  // check existing categories dropdown for Color/Size seeded earlier
  // seed categories/values if not present
  const opts = await page.locator('select[name="assign_category_id"] option').allInnerTexts();
  console.log('unassigned categories:', JSON.stringify(opts));

  // Assign categories: need category ids. Read from admin values tab.
  async function catId(code){
    await nav('/modules/FA_ProductAttributes/public/index.php?tab=values'); await page.waitForLoadState('networkidle');
    const o2=await page.locator('select[name="category_id"] option').evaluateAll(os=>os.map(o=>({v:o.value,t:o.text})));
    for(const o of o2) if(o.t===code) return o.v;
    return null;
  }
  const cColor = await catId('colore2e');
  const cSize = await catId('sizee2e');
  console.log('catColor',cColor,'catSize',cSize);

  // assign each on variations tab
  async function assignCat(cid){
    await nav('/inventory/manage/items.php?stock_id='+pid); await page.waitForLoadState('networkidle');
    await openVarTab();
    const sel=page.locator('select[name="assign_category_id"]');
    // ensure option exists (unassigned only)
    const hasOpt = await sel.locator('option[value="'+cid+'"]').count();
    console.log('  assign option',cid,'count:',hasOpt);
    if(!hasOpt) throw new Error('category '+cid+' not in assign list');
    await sel.selectOption({value:cid});
    await page.click('button[name="assign_category_submit"],input[name="assign_category_submit"]');
    await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
  }
  if(cColor) await assignCat(cColor);
  if(cSize) await assignCat(cSize);
  console.log('assigned color+size');

  // open variations tab, verify generate buttons, click generate_combos
  await nav('/inventory/manage/items.php?stock_id='+pid); await page.waitForLoadState('networkidle');
  await openVarTab();
  console.log('generate_combos present:', await page.locator('button[name="generate_combos"],input[name="generate_combos"]').count());
  await page.click('button[name="generate_combos"],input[name="generate_combos"] >> nth=0');
  await page.waitForTimeout(3000); await page.waitForLoadState('networkidle');
  const body1 = await page.locator('body').innerText();
  console.log('AFTER GENERATE_COMBOS body contains:',
    /Combination set saved|already up to date/i.test(body1));
  // notification area
  const ma=await page.locator('.mail_msg,.sa,.alert,.notice,.ui-state-error,.ui-state-highlight').allInnerTexts();
  console.log('MSGS:', JSON.stringify(ma).slice(0,300));

  // click generate_child
  await page.click('button[name="generate_child"],input[name="generate_child"] >> nth=0');
  await page.waitForTimeout(3000); await page.waitForLoadState('networkidle');
  const body2 = await page.locator('body').innerText();
  console.log('AFTER GENERATE_CHILD:', /Generate Child complete/i.test(body2));
  console.log('has Existing Variations:', body2.includes('Existing Variations'));

  console.log('\nFINAL URL:', page.url());
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});