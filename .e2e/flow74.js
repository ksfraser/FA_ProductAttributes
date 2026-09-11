const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: false, args: ['--no-sandbox','--disable-dev-shm-usage'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
  async function openVarTab(s){
    await nav('/inventory/manage/items.php?stock_id='+s); await page.waitForLoadState('networkidle');
    await pause(1400);
    await page.locator('button[name="tabs_product_variations"]').first().click();
    await pause(3200); await page.waitForLoadState('networkidle');
  }
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');

  await openVarTab('auto-gas');

  async function clickAndCapture(name){
    let postData=''; let respBody='<none>';
    const postP = page.waitForRequest(r=>r.method()==='POST' && r.url().includes('items.php'),{timeout:20000}).then(async req=>{postData=req.postData()||'';}).catch(()=>{postData='<no-post>';});
    page.once('response', async r=>{ if(r.url().includes('/inventory/manage/items.php')){ try{respBody=await r.text();}catch(e){respBody='<rf>';} } });
    try{ await page.locator('button[name="'+name+'"]').click(); }catch(e){ console.log(name,'CLICK FAIL',e.message.slice(0,80)); }
    await postP; await pause(2500);
    console.log('=== '+name+' ===');
    console.log('  POST has own key:', postData.includes('name="'+name+'"')||postData.includes(name+'='));
    // show whether the posted form carries a stock_id / _tabs_sel
    console.log('  POST bytes:', postData.length);
    const m = respBody.replace(/\s+/g,' ');
    const i=m.indexOf('"text":"');
    console.log('  RESP text-field:', i>=0? m.slice(i, i+200): m.slice(-200));
  }

  await clickAndCapture('generate_combos');
  await clickAndCapture('create_child_product');

  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});