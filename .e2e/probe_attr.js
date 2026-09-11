const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const b=await chromium.launch({executablePath:EXE,headless:false,args:['--no-sandbox','--disable-dev-shm-usage']});const p=await b.newPage();
  const nav=(u)=>p.goto(BASE+u,{waitUntil:'networkidle',timeout:45000});const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
  await nav('/');await p.fill('input[name="user_name_entry_field"]','opencode');await p.fill('input[name="password"]','opencode');await p.click('input[type="submit"]');await p.waitForLoadState('networkidle');
  await nav('/inventory/manage/items.php?stock_id=auto-gas');await pause(1200);
  await p.locator('button[name="tabs_product_attributes"]').first().click();await pause(3000);await p.waitForLoadState('networkidle');
  const inputs = await p.evaluate(()=>Array.from(document.querySelectorAll('select,input,textarea')).map(e=>({tag:e.tagName,name:e.getAttribute('name'),val:(e.value||'').slice(0,20),type:e.getAttribute('type')})).filter(o=>o.name||o.type==='submit').slice(0,80));
  console.log(JSON.stringify(inputs,null,1));
  const body=await p.evaluate(()=>document.body?document.body.innerText:'');
  console.log('=== attributes tab body (first 1500) ===');console.log(body.slice(0,1500));
  await b.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});