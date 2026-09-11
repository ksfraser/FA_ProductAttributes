const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: false, args: ['--no-sandbox','--disable-dev-shm-usage'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
  async function openVarTab(stock_id){
    await nav('/inventory/manage/items.php?stock_id='+stock_id); await page.waitForLoadState('networkidle');
    await pause(1400);
    await page.locator('button[name="tabs_product_variations"]').first().click();
    await pause(3500); await page.waitForLoadState('networkidle');
  }
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');

  await openVarTab('auto-gas');

  const rendered = await page.evaluate(()=>{
    const names=Array.from(document.querySelectorAll('button')).map(b=>b.getAttribute('name')).filter(n=>n&&/generate|create_child/.test(n));
    return { buttons: names };
  });
  console.log('BUTTONS:', JSON.stringify(rendered));

  let respBody='<none>';
  page.on('response', async r=>{
    if(r.url().includes('/inventory/manage/items.php')){ try{ respBody = await r.text(); }catch(e){ respBody='<read-fail>'; } }
  });

  if(rendered.buttons.includes('create_child_product')){
    await Promise.all([
      page.waitForResponse(r=>r.url().includes('/inventory/manage/items.php'),{timeout:30000}).catch(()=>{}),
      page.locator('button[name="create_child_product"]').click(),
    ]);
    await pause(5000);

    const pageText = await page.evaluate(()=>document.body?document.body.innerText:'');
    console.log('==================================================================');
    console.log('PAGE TEXT (tail 1200):');
    console.log(pageText.slice(-1200));
    console.log('==================================================================');
    console.log('RESPONSE BODY snippet (500):');
    console.log((respBody||'').replace(/\s+/g,' ').slice(-500));
  } else {
    console.log('NO create_child_product button');
  }
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});