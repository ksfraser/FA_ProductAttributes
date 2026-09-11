const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE='http://localhost:8080';
const EXE='/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async()=>{const b=await chromium.launch({executablePath:EXE,headless:false,args:['--no-sandbox','--disable-dev-shm-usage']});const p=await b.newPage();
const nav=(u)=>p.goto(BASE+u,{waitUntil:'networkidle',timeout:45000});const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
await nav('/');await p.fill('input[name="user_name_entry_field"]','opencode');await p.fill('input[name="password"]','opencode');await p.click('input[type="submit"]');await p.waitForLoadState('networkidle');

// Test on auto-gas first (known state)
await nav('/inventory/manage/items.php?stock_id=auto-gas');await pause(1400);
await p.locator('button[name="tabs_product_variations"]').first().click();await pause(3000);await p.waitForLoadState('networkidle');

const log=[];
p.on('response',async r=>{if(r.url().includes('items.php')&&r.request().method()==='POST'){try{const t=await r.text();log.push({len:t.length,cmds:t.match(/"n":"[a-z]+"/g)||[],snip:t.replace(/\s+/g,' ').slice(0,400)});}catch(e){}}});

// Click generate_combos
await p.locator('button[name="generate_combos"]').click();await pause(4000);
console.log('=== generate_combos ===');
log.forEach(function(r,i){console.log(i,'len='+r.len,'cmds='+JSON.stringify(r.cmds));console.log('  ',r.snip.slice(0,300));});
log.length=0;

// Now click create_child_product
await p.locator('button[name="create_child_product"]').click();await pause(4000);
console.log('=== create_child_product ===');
log.forEach(function(r,i){console.log(i,'len='+r.len,'cmds='+JSON.stringify(r.cmds));console.log('  ',r.snip.slice(0,300));});

// Check DOM for notification
var notif=await p.evaluate(function(){var el=document.getElementById('msgbox'); return el?el.innerText.trim():'no-msgbox';});
console.log('=== msgbox after actions ===',notif);

// Check body for any notification text
var bodyNotif=await p.evaluate(function(){var text=document.body?document.body.innerText:''; var lines=text.split('\n').filter(function(l){return /saved|created|generated|error|Error|combin|No combination|already up to date/i.test(l);}); return lines.slice(0,10);});
console.log('=== body notification lines ===',JSON.stringify(bodyNotif));

await b.close();})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});