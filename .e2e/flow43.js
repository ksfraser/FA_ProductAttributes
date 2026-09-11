const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: false, args: ['--no-sandbox','--disable-dev-shm-usage'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
  page.on('response', async (resp)=>{
    if(resp.request().method()==='POST' && resp.url().includes('items.php')){
      console.log('POST items.php status='+resp.status());
    }
  });
  async function openVarTab(stock_id){
    await nav('/inventory/manage/items.php?stock_id='+stock_id); await page.waitForLoadState('networkidle');
    await pause(1200);
    await page.locator('button[name="tabs_product_variations"]').first().click();
    await pause(3500); await page.waitForLoadState('networkidle');
  }
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');
  const stock='auto-gas-L-11-36-Ind';
  await openVarTab(stock);
  const cats = await page.evaluate(()=>{
    const o=Array.from(document.querySelector('select[name="assign_category_id"]').options).map(x=>({v:x.value,t:x.textContent.trim()}));
    return o;
  });
  console.log('CATS_AVAILABLE', JSON.stringify(cats));
  // pick a category that is NOT already assigned (we want a real assign)
  // assign a category via UI
  const chosen = cats.find(c=>c.v!=='0') || cats[0];
  await page.locator('select[name="assign_category_id"]').selectOption({value:String(chosen.v)});
  await page.pause? 0:0;
  await page.locator('input[name="assign_category_submit"]').click();
  await pause(4000); await page.waitForLoadState('networkidle');
  console.log('ASSIGN_CLICKED v='+chosen.v);
  // now also click generate to test
  await page.locator('input[name="generate_combos"]').click();
  await pause(4000); await page.waitForLoadState('networkidle');
  console.log('GEN_CLICKED');
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});
