const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: false, args: ['--no-sandbox','--disable-dev-shm-usage'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
  let finalBodies=[];
  page.on('response', async resp=>{
    if(resp.request().method()==='POST' && resp.url().includes('items.php')){
      if(resp.status()===200){
        let t=''; try{ t=await resp.text(); }catch(e){}
        finalBodies.push(t);
      }
    }
  });
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
  await page.evaluate(()=>{
    const f=document.querySelector('input[name="generate_combos"]').form;
    f.removeAttribute('onsubmit'); f.removeAttribute('target');
  });
  finalBodies=[];
  await page.locator('input[name="generate_combos"]').click();
  await pause(5000); await page.waitForLoadState('networkidle').catch(()=>{});
  for(const b of finalBodies){
    const marker=(b.match(/VAR_TAB_POST[^>]*/)||[])[0];
    const notif=(b.match(/Combination[^<]{0,60}/)||[])[0];
    const err=(b.match(/class="error"[^>]*>([^<]{0,120})/)||[])[1];
    console.log('RESP marker='+(marker||'(none)'));
    console.log('RESP notif='+(notif||'(none)'));
    console.log('RESP err='+(err||'(none)'));
    // count combos table rows if present
  }
  const finalHtml = finalBodies[finalBodies.length-1]||'';
  console.log('RESP_LEN='+finalHtml.length);
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});
