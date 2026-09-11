const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE='http://localhost:8080';
const EXE='/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async()=>{const b=await chromium.launch({executablePath:EXE,headless:false,args:['--no-sandbox','--disable-dev-shm-usage']});const p=await b.newPage();
const nav=(u)=>p.goto(BASE+u,{waitUntil:'networkidle',timeout:45000});const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
await nav('/');await p.fill('input[name="user_name_entry_field"]','opencode');await p.fill('input[name="password"]','opencode');await p.click('input[type="submit"]');await p.waitForLoadState('networkidle');
await nav('/inventory/manage/items.php?stock_id=BattlestarGalactica');await pause(1400);
await p.locator('button[name="tabs_product_identifiers"]').first().click();await pause(3000);await p.waitForLoadState('networkidle');

const respLog=[];
p.on('response',async r=>{if(r.url().includes('items.php')&&r.request().method()==='POST'){try{const t=await r.text();respLog.push({len:t.length,head:t.replace(/\s+/g,' ').slice(0,300)});}catch(e){}}}); 

// Save via the plain submit input
await p.locator('input[name="pa_identifiers_save"]').first().click();
await pause(3500); await p.waitForLoadState('networkidle');

const tail=await p.evaluate(()=>document.body?document.body.innerText.slice(-400):'');
console.log('=== Identifiers save captured responses ===');console.log(JSON.stringify(respLog,null,1));
console.log('=== body tail after save ===');console.log(tail);
await b.close();})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});