// DEFINITIVE E2E using real UI interactions.
// assign Color(2)+Size(1) via dropdown+button; generate_combos; generate_child; verify children + read-only child.
const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  const strip=(h)=>h.replace(/<[^>]*>/g,' ').replace(/\s+/g,' ');
  async function activateVar(stock_id){
    await nav('/inventory/manage/items.php?stock_id='+stock_id); await page.waitForLoadState('networkidle');
    await page.evaluate(()=>{ const tb=document.querySelector('button[name="tabs_product_variations"]'); if(tb) tb.click(); });
    await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
  }
  async function assignCat(value){
    await page.locator('select[name="assign_category_id"]').selectOption({value:String(value)});
    await page.click('input[name="assign_category_submit"],button[name="assign_category_submit"]');
    await page.waitForTimeout(3000); await page.waitForLoadState('networkidle');
  }

  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');
  const pk=Date.now().toString().slice(-5);
  const pid='E2E-KK-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E KK '+pk);
  await page.click('button[name="addupdate"]'); await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');

  // assign Color(2)+Size(1)
  await activateVar(pid); await assignCat(2);
  await activateVar(pid); await assignCat(1);
  await activateVar(pid);
  console.log('assigned table:', JSON.stringify(await page.locator('.tablestyle2 tr').allInnerTexts()));

  // generate_combos via real button
  await page.click('input[name="generate_combos"],button[name="generate_combos"]');
  await page.waitForTimeout(3000); await page.waitForLoadState('networkidle');
  let t = strip(await page.content());
  console.log('GENERATE_COMBOS msg:', JSON.stringify((t.match(/(Combination set[^.]{0,80}|No categories[^.]{0,50}|No values[^.]{0,50})/g)||[])));

  // generate_child via real button
  await page.click('input[name="generate_child"],button[name="generate_child"]');
  await page.waitForTimeout(3000); await page.waitForLoadState('networkidle');
  t = strip(await page.content());
  console.log('GENERATE_CHILD msg:', JSON.stringify((t.match(/(Generate Child[^.]{0,80}|No combination[^.]{0,50}|instantiated[^.]{0,60})/gi)||[])));

  // verify children in dropdown
  await nav('/inventory/manage/items.php'); await page.waitForLoadState('networkidle');
  const opts = await page.evaluate(()=>{
    const sels=Array.from(document.querySelectorAll('select'));
    for(const s of sels){ const tt=s.innerText||''; if(tt.includes('E2E-KK-')) return tt; } return null;
  });
  const kids = opts? opts.split('\n').map(x=>x.trim()).filter(x=>x.includes('E2E-KK-')) : [];
  console.log('DROPDOWN children =', kids.length, JSON.stringify(kids.slice(0,8)));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});