// Definitive: click generate actions, pause, then check the item LIST changed (children appeared).
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
  async function itemDropdown(){ // returns all item option texts on the items index
    await nav('/inventory/manage/items.php'); await page.waitForLoadState('networkidle');
    return page.evaluate(()=>{
      const sels=Array.from(document.querySelectorAll('select'));
      for(const s of sels){ const t=s.innerText||''; if(t.includes('Select an item')||t.includes('E2E-')) return t; }
      return null;
    });
  }
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');
  const pk=Date.now().toString().slice(-5);
  const pid='E2E-ZZ-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E ZZ '+pk);
  await page.click('button[name="addupdate"]'); await pause(2500); await page.waitForLoadState('networkidle');

  for(const value of [2,1]){
    await activateVar(pid);
    await page.locator('select[name="assign_category_id"]').selectOption({value:String(value)});
    await page.click('input[name="assign_category_submit"]');
    await pause(3000); await page.waitForLoadState('networkidle');
  }
  await activateVar(pid);
  console.log('assigned:', JSON.stringify(await page.locator('.tablestyle2 tr').allInnerTexts()));

  // click generate_combos, pause, then check the LIST for children
  await page.click('input[name="generate_combos"]');
  await pause(3000);
  let dd = await itemDropdown();
  let gcKids = dd? dd.split('\n').map(x=>x.trim()).filter(x=>x.startsWith(pid+'-')) : [];
  console.log('after GENERATE_COMBOS: children in list =', gcKids.length, JSON.stringify(gcKids.slice(0,5)));

  // now click generate_child (on the parent again), pause, check list again
  await activateVar(pid);
  await page.click('input[name="generate_child"]');
  await pause(3000);
  dd = await itemDropdown();
  let kids = dd? dd.split('\n').map(x=>x.trim()).filter(x=>x.startsWith(pid+'-')) : [];
  console.log('after GENERATE_CHILD: children in list =', kids.length, JSON.stringify(kids.slice(0,10)));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});