const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const b=await chromium.launch({executablePath:EXE,headless:false,args:['--no-sandbox','--disable-dev-shm-usage']});const p=await b.newPage();
  const nav=(u)=>p.goto(BASE+u,{waitUntil:'networkidle',timeout:45000});const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
  await nav('/');await p.fill('input[name="user_name_entry_field"]','opencode');await p.fill('input[name="password"]','opencode');await p.click('input[type="submit"]');await p.waitForLoadState('networkidle');

  await nav('/inventory/manage/items.php?stock_id=BattlestarGalactica');await pause(1400);
  await p.locator('button[name="tabs_product_variations"]').first().click();await pause(3200);await p.waitForLoadState('networkidle');

  const txt=await p.evaluate(()=>document.body?document.body.innerText:'');
  console.log('=== VAR TAB BODY ===');console.log(txt.slice(0,2200));

  // capture responses from NOW on
  const respLog=[];
  p.on('response',async r=>{ if(r.url().includes('items.php') && r.request().method()==='POST'){ try{const t=await r.text(); respLog.push({status:r.status(),len:t.length,head:t.slice(0,400)});}catch(e){respLog.push({status:r.status(),err:'readfail'});} } });

  console.log('=== INVOKE create_child_product ===');
  await p.locator('button[name="create_child_product"]').click();
  await pause(3500); await p.waitForLoadState('networkidle');

  // switch tab away and back to force re-render
  await p.locator('button[name="tabs_product_attributes"]').first().click();await pause(2500);await p.waitForLoadState('networkidle');
  await p.locator('button[name="tabs_product_variations"]').first().click();await pause(3500);await p.waitForLoadState('networkidle');

  const txt2=await p.evaluate(()=>document.body?document.body.innerText:'');
  console.log('=== AFTER TAB SWITCH BACK, BODY (tail 1500) ===');console.log(txt2.slice(-1500));

  console.log('=== CAPTURED RESPONSES ===');console.log(JSON.stringify(respLog,null,1));
  await b.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});