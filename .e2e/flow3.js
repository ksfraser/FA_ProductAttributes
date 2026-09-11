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

  const pk=Date.now().toString().slice(-5);
  const pid='E2E-Q-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E Q '+pk);
  await page.click('button[name="addupdate"]'); await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');

  async function openVarTab(){
    await page.evaluate(()=>{ const b=document.querySelector('button[name="tabs_product_variations"]'); if(b) b.click(); });
    await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
  }
  await nav('/inventory/manage/items.php?stock_id='+pid); await page.waitForLoadState('networkidle');
  await openVarTab();
  // assign Color(2) and Size(1)
  for(const cid of ['2','1']){
    await page.locator('select[name="assign_category_id"]').selectOption({value:cid});
    await page.click('button[name="assign_category_submit"],input[name="assign_category_submit"]');
    await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
  }
  console.log('assigned cats');
  await openVarTab();
  // dump widgets now
  console.log('combos btn:', await page.locator('button[name="generate_combos"],input[name="generate_combos"]').count());
  console.log('child btn:', await page.locator('button[name="generate_child"],input[name="generate_child"]').count());
  // existing variations header
  const b0=await page.locator('body').innerText();
  console.log('has Assigned Categories:', b0.includes('Assigned Categories'));
  console.log('assigned rows sample:', b0.split('\n').slice(0,40).join(' | '));
  // click generate_combos via JS to bypass any overlay
  const clicked = await page.evaluate(()=>{ const b=document.querySelector('button[name="generate_combos"]')||document.querySelector('input[name="generate_combos"]'); if(b){ b.click(); return true;} return false; });
  console.log('clicked combos:', clicked);
  await page.waitForTimeout(3500); await page.waitForLoadState('networkidle');
  const body=await page.locator('body').innerText();
  console.log('=== FULL BODY AFTER generate_combos ===');
  console.log(body.split('\n').filter(l=>l.trim()).slice(0,60).join(' | '));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});