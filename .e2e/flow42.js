const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: false, args: ['--no-sandbox','--disable-dev-shm-usage'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
  // capture POST responses
  page.on('response', async (resp)=>{
    if(resp.request().method()==='POST'){
      let t=''; try{ t=(await resp.text()).slice(0,300);}catch(e){t='<no-text>';}
      console.log('POSTRESP url='+resp.url().split('?')[0]+' status='+resp.status()+' len='+t.length);
    }
  });
  let navCount=0;
  page.on('framenavigated', f=>{ if(f===page.mainFrame()){ navCount++; } });
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
  // use an existing scratch child item
  const stock='auto-gas-L-11-36-Ind';
  await openVarTab(stock);
  const before = await page.content();
  const summaryBefore = await page.evaluate(()=>{
    const s=document.querySelector('input[name="assign_category_id"] select, select[name="assign_category_id"]');
    const rows=document.querySelectorAll('table.tablestyle2 tbody tr').length;
    return { rows, selectOpts: document.querySelector('select[name="assign_category_id"]')?document.querySelector('select[name="assign_category_id"]').options.length:null };
  });
  console.log('SUMMARY_BEFORE', JSON.stringify(summaryBefore));
  const beforeLen = before.length;
  // click generate_combos
  await page.locator('input[name="generate_combos"]').click();
  await pause(4000); await page.waitForLoadState('networkidle');
  const after = await page.content();
  const identical = after===before;
  console.log('GENCLICK beforeLen='+beforeLen+' afterLen='+after.length+' identical='+identical);
  const gc=await page.locator('input[name="generate_combos"]').count();
  console.log('gen_combos_present_after='+gc);
  console.log('NAVCOUNT='+navCount);
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});
