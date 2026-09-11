const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE='http://localhost:8080';
const EXE='/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async()=>{const b=await chromium.launch({executablePath:EXE,headless:false,args:['--no-sandbox','--disable-dev-shm-usage']});const p=await b.newPage();
const nav=(u)=>p.goto(BASE+u,{waitUntil:'networkidle',timeout:45000});const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
await nav('/');await p.fill('input[name="user_name_entry_field"]','opencode');await p.fill('input[name="password"]','opencode');await p.click('input[type="submit"]');await p.waitForLoadState('networkidle');
await nav('/inventory/manage/items.php?stock_id=BattlestarGalactica');await pause(1400);
// record before this click
let navFired=0, ajax=0, raw='';
p.on('request',r=>{ if(r.url().includes('items.php')) { if(r.method()==='POST') navFired++; } });
p.on('response',r=>{ if(r.url().includes('items.php')) { ajax++; } });
const rp=p.waitForResponse(r=>r.url().includes('items.php'),{timeout:30000}).then(async r=>{try{raw=await r.text();}catch(e){raw='<rf>';}}).catch(()=>{raw='<noresp>';});

await p.locator('button[name="tabs_product_identifiers"]').first().click();
await pause(3000); await p.waitForLoadState('networkidle');

// now click save, capture reactive
let raw2='<none>'; const nav2=navFired; let posts2=0;
const rp2=p.waitForResponse(r=>r.url().includes('items.php')&&r.request().method()==='POST',{timeout:30000}).then(async r=>{try{raw2=await r.text();}catch(e){raw2='<rf>';}}).catch(()=>{raw2='<noresp>';});
p.on('response',r=>{if(r.url().includes('items.php')&&r.request().method()==='POST')posts2++;});
await p.locator('input[name="pa_identifiers_save"]').first().click();
await rp2; await pause(3000);

console.log('=== Identifiers SAVE ===');
console.log('posts fired:',posts2);
console.log('response first 500:',raw2.replace(/\s+/g,' ').slice(0,500));
const notif=await p.evaluate(()=>{const m=document.body?document.body.innerText.match(/Identifier[^\n]{0,60}|saved[^\n]{0,60}|Saved[^\n]{0,60}/i):null; return m?m[0]:null;});
console.log('notification-like text in body:',notif);
await b.close();})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});