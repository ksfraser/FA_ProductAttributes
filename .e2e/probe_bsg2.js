const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const b=await chromium.launch({executablePath:EXE,headless:false,args:['--no-sandbox','--disable-dev-shm-usage']});const p=await b.newPage();
  const nav=(u)=>p.goto(BASE+u,{waitUntil:'networkidle',timeout:45000});const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
  await nav('/');await p.fill('input[name="user_name_entry_field"]','opencode');await p.fill('input[name="password"]','opencode');await p.click('input[type="submit"]');await p.waitForLoadState('networkidle');
  await nav('/inventory/manage/items.php');await pause(1800);
  const hit=await p.evaluate(()=>Array.from(document.querySelectorAll('select[name="stock_id"] option')).filter(o=>/battlestar/i.test(o.textContent||'')).map(o=>({v:o.value,t:(o.textContent||'').trim()})));
  console.log('Battlestar options:',JSON.stringify(hit,null,1));
  await b.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});