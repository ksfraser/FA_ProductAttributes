const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: false, args: ['--no-sandbox','--disable-dev-shm-usage'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
  const openTab=async()=>{await nav('/inventory/manage/items.php?stock_id=auto-gas');await page.waitForLoadState('networkidle');await pause(1400);await page.locator('button[name="tabs_product_variations"]').first().click();await pause(3000);await page.waitForLoadState('networkidle');};
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');
  await openTab();

  async function fire(name){
    let raw='<none>';
    const rp = page.waitForResponse(r=>r.method()==='POST' && r.url().includes('/inventory/manage/items.php') && r.request().postData() && r.request().postData().includes(name),{timeout:25000}).then(async r=>{try{raw=await r.text();}catch(e){raw='<readfail>';}}).catch(()=>{raw='<noresp>';});
    await page.locator('button[name="'+name+'"]').click();
    await rp; await pause(2200);
    const ora = await page.evaluate(()=>{const sels='.alert,.notification,#_flash_,.act_results,.err,[class*=flash],[id*=flash],.flash_message';return Array.from(document.querySelectorAll(sels)).map(e=>(e.innerText||'').trim()).filter(Boolean);});
    console.log('### '+name+' ###');
    console.log('  RESPONSE (raw, first 600):', raw.slice(0,600));
    console.log('  DOM flash/alert after:', JSON.stringify(ora));
  }
  await fire('generate_combos');
  await fire('create_child_product');
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});