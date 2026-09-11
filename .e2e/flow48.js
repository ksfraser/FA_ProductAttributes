const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: false, args: ['--no-sandbox','--disable-dev-shm-usage'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
  let reqs=[];
  page.on('request', req=>{ reqs.push((req.method()==='POST'?'POST ':(req.method()==='GET'?'GET  ':'OTHER'))+req.url()); });
  let navCount=0;
  page.on('framenavigated', f=>{ if(f===page.mainFrame()){ navCount++; } });
  let popups=0;
  page.on('popup', p=>{ popups++; console.log('POPUP opened url='+p.url()); });
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
  const ctl = await page.evaluate(()=>{
    // find a core submit/button in the same form - prefer the "addupdate" save button
    let info={};
    const b=document.querySelector('button[name="addupdate"],input[name="addupdate"]');
    info.addupdate = b? (b.outerHTML.slice(0,120)) : null;
    // also list all submit/button in the form that contains generate_combos
    const gc=document.querySelector('input[name="generate_combos"]');
    const f=gc?gc.form:null;
    info.buttonsInForm = f? Array.from(f.querySelectorAll('input[type=submit],button')).map(x=>(x.name||'?')+'('+(x.value||'')+')') : [];
    return info;
  });
  console.log('CTRL', JSON.stringify(ctl,null,1));
  // click the core save button (control)
  reqs=[]; navCount=0; popups=0;
  const addupdate = await page.locator('button[name="addupdate"],input[name="addupdate"]').count();
  console.log('ADDUPdate_count='+addupdate);
  if(addupdate>0){
    await page.locator('button[name="addupdate"],input[name="addupdate"]').first().click();
    await pause(4500); await page.waitForLoadState('networkidle').catch(()=>{});
    console.log('AFTER_ADDUP_NET', JSON.stringify(reqs,null,1));
    console.log('AFTER_ADDUP_NAVCOUNT='+navCount+' POPUPS='+popups);
  }
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});
