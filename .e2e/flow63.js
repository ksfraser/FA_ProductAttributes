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

  // Try to discover a stock id from the item selector options
  await nav('/inventory/manage/items.php');
  await pause(1200);
  const opts = await page.evaluate(()=>{
    const sel=document.querySelector('select[name="stock_id"]');
    return sel? Array.from(sel.options).slice(0,40).map(o=>({v:o.value,t:o.text})) : {error:'no stock_id select', shown:document.body?document.body.innerText.slice(0,300):''};
  });
  console.log('STOCK IDS:', JSON.stringify(opts,null,1));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});