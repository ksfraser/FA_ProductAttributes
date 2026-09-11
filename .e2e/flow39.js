// Patient, real-click-driven flow with long waits; verifies combos via generate_child children + panel.
const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
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
  const pid='E2E-AD-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E AD '+pk);
  await page.click('button[name="addupdate"]'); await pause(2500); await page.waitForLoadState('networkidle');

  await openVarTab(pid);
  for(const value of [2,1]){
    await page.locator('select[name="assign_category_id"]').selectOption({value:String(value)});
    await page.locator('input[name="assign_category_submit"]').click();
    await pause(3500); await page.waitForLoadState('networkidle');
    await openVarTab(pid);
  }
  console.log('assigned table:', JSON.stringify(await page.locator('.tablestyle2 tr').allInnerTexts()));

  // generate_combos
  await page.locator('input[name="generate_combos"]').click();
  await pause(3500); await page.waitForLoadState('networkidle');
  await openVarTab(pid);
  const panelGC = await page.locator('body').innerText();
  const gcMsg=panelGC.split('\n').map(s=>s.trim()).filter(s=>/Combination|No categor|No values|up to date|saved|combos/i.test(s));
  console.log('panel msgs after GC:', JSON.stringify(gcMsg), 'has Existing Variations:', panelGC.includes('Existing Variations'));

  // generate_child
  await page.locator('input[name="generate_child"]').click();
  await pause(3500); await page.waitForLoadState('networkidle');
  await openVarTab(pid);
  const panelGCh = await page.locator('body').innerText();
  const gchMsg=panelGCh.split('\n').map(s=>s.trim()).filter(s=>/Generate Child|No combination|instantiat|Existing Variations|E2E-AD-/i.test(s));
  console.log('panel msgs after GCh:', JSON.stringify(gchMsg));

  // children in global dropdown
  await nav('/inventory/manage/items.php'); await page.waitForLoadState('networkidle');
  const opts = await page.evaluate(()=>{ const sels=Array.from(document.querySelectorAll('select')); for(const s of sels){const t=s.innerText||''; if(t.includes('E2E-AD-')) return t;} return null;});
  const kids=opts?opts.split('\n').map(x=>x.trim()).filter(x=>x.startsWith(pid+'-')):[];
  console.log('DROPDOWN children:', kids.length, JSON.stringify(kids.slice(0,10)));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});
