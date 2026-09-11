const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: false, args: ['--no-sandbox','--disable-dev-shm-usage'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');
  // fresh load, NO tab clicks
  await nav('/inventory/manage/items.php?stock_id=auto-gas-L-11-36-Ind'); await page.waitForLoadState('networkidle');
  await pause(2500);
  const onLoad = await page.evaluate(()=>{
    const f=document.querySelector('input[name="generate_combos"]')?document.querySelector('input[name="generate_combos"]').form:null;
    return f? {onsubmit:f.getAttribute('onsubmit'), target:f.getAttribute('target'), activeTab:document.querySelector('ul.ajaxtabs button.current')?document.querySelector('ul.ajaxtabs button.current').name:null}: {none:true};
  });
  console.log('ON_LOAD_NO_CLICK', JSON.stringify(onLoad));
  // now click the variations tab
  await page.locator('button[name="tabs_product_variations"]').first().click();
  await pause(3500); await page.waitForLoadState('networkidle');
  const afterTab = await page.evaluate(()=>{
    const f=document.querySelector('input[name="generate_combos"]').form;
    return {onsubmit:f.getAttribute('onsubmit'), target:f.getAttribute('target')};
  });
  console.log('AFTER_TAB_CLICK', JSON.stringify(afterTab));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});
