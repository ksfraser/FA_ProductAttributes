const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: false, args: ['--no-sandbox','--disable-dev-shm-usage'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
  let flags={}, navCount=0, popups=0;
  page.on('request', req=>{
    if(req.method()==='POST'){
      const b=req.postData()||'';
      flags.btn = b.includes('name="generate_combos"');
      flags.tabs = b.includes('name="_tabs_sel"') && b.includes('product_variations');
      flags.stock = b.includes('auto-gas-L-11-36-Ind');
      flags.len = b.length;
    }
  });
  page.on('framenavigated', f=>{ if(f===page.mainFrame()) navCount++; });
  page.on('popup', p=>{ popups++; console.log('POPUP '+p.url()); });
  page.on('dialog', async d=>await d.dismiss());
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
  flags={}; navCount=0; popups=0;
  await page.locator('input[name="generate_combos"]').click();
  await pause(5000); await page.waitForLoadState('networkidle').catch(()=>{});
  console.log('GEN_DONE', JSON.stringify({...flags, nav:navCount, popups}));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});