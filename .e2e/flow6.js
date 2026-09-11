const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  const ajax = [];
  page.on('response', async r => {
    const u = r.url();
    if (u.includes('JsHttpRequest') && r.request().method()==='POST') {
      try { const body = await r.text(); ajax.push({ url:u.slice(-40), body }); } catch(e){}
    }
  });
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');

  const pk=Date.now().toString().slice(-5);
  const pid='E2E-T-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E T '+pk);
  await page.click('button[name="addupdate"]'); await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');

  async function openVarTab(){
    await page.evaluate(()=>{ const b=document.querySelector('button[name="tabs_product_variations"]'); if(b) b.click(); });
    await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
  }
  await nav('/inventory/manage/items.php?stock_id='+pid); await page.waitForLoadState('networkidle');
  await openVarTab();
  for(const cid of ['2','1']){
    await page.locator('select[name="assign_category_id"]').selectOption({value:cid});
    await page.click('button[name="assign_category_submit"],input[name="assign_category_submit"]');
    await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
  }
  await openVarTab();
  ajax.length = 0; // reset before the generate click
  await page.click('button[name="generate_combos"],input[name="generate_combos"] >> nth=0');
  await page.waitForTimeout(4000);
  console.log('AJAX RESPONSES captured:', ajax.length);
  for (const a of ajax) {
    // decode the post body to see what action fired
    let reqInfo = '?';
    try { reqInfo = decodeURIComponent(a.url); } catch(e){}
    console.log('--- RESP body len', a.body.length, 'url', a.url, '---');
    console.log(a.body.slice(0, 400));
    console.log('');
  }
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});