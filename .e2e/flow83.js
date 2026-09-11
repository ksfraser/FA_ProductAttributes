const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE='http://localhost:8080';
const EXE='/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async()=>{const b=await chromium.launch({executablePath:EXE,headless:false,args:['--no-sandbox','--disable-dev-shm-usage']});const p=await b.newPage();
const nav=(u)=>p.goto(BASE+u,{waitUntil:'networkidle',timeout:45000});const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
await nav('/');await p.fill('input[name="user_name_entry_field"]','opencode');await p.fill('input[name="password"]','opencode');await p.click('input[type="submit"]');await p.waitForLoadState('networkidle');
await nav('/inventory/manage/items.php?stock_id=auto-gas');await pause(1400);
await p.locator('button[name="tabs_product_variations"]').first().click();await pause(3000);await p.waitForLoadState('networkidle');

// Capture all responses and log their full js command structure
const log=[];
p.on('response',async r=>{if(r.url().includes('items.php')&&r.request().method()==='POST'){try{const t=await r.text();log.push({url:r.url().split('?')[0],len:t.length,jsCommands:t.match(/"n":"[a-z]+"/g)||[],body:t.slice(0,800)});}catch(e){}}});

// Click create_child_product
await p.locator('button[name="create_child_product"]').click();
await pause(4000);
console.log('=== create_child_product responses ===');
log.forEach(function(r,i){console.log(i,r.len,r.jsCommands,r.body.slice(0,300));});
log.length=0;
await pause(1000);

// Now click tab button to compare
await p.locator('button[name="tabs_product_attributes"]').first().click();
await pause(4000);
console.log('=== tab switch responses ===');
log.forEach(function(r,i){console.log(i,r.len,r.jsCommands,r.body.slice(0,300));});
await b.close();})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});
