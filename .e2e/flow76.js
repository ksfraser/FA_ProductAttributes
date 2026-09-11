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
  await nav('/inventory/manage/items.php?stock_id=auto-gas'); await page.waitForLoadState('networkidle');
  await pause(1400);
  await page.locator('button[name="tabs_product_variations"]').first().click();
  await pause(3200); await page.waitForLoadState('networkidle');

  // capture the FULL JsHttpRequest response body for create_child_product
  let respJson=null;
  const respP = page.waitForResponse(r=>r.url().includes('/inventory/manage/items.php'),{timeout:25000}).then(async r=>{
    const t=await r.text();
    try{ respJson=JSON.parse(t); }catch(e){ respJson={RAW:t.slice(0,500)}; }
  }).catch(()=>{});
  await page.locator('button[name="create_child_product"]').click();
  await respP; await pause(2500);

  console.log('=== create_child_product RESPONSE ===');
  console.log(JSON.stringify(respJson && {

      error: respJson.error,
      post: respJson.post,
      js: respJson.js,
      text: respJson.text
    }, null, 1));

  // dump any flash/notification/error containers in the live DOM
  const dom = await page.evaluate(()=>{
    const grab=(sel)=>{const els=Array.from(document.querySelectorAll(sel));return els.map(e=>(e.innerText||'').trim()).filter(Boolean);};
    return {
      alerts: grab('.alert, .notification, #_flash_, .act_results, .err, .error, div[id*="flash"], div[class*="flash"]'),
      bodyTail: (document.body.innerText||'').trim().slice(-600)
    };
  });
  console.log('=== DOM alerts/errors ===');
  console.log(JSON.stringify(dom,null,1));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});