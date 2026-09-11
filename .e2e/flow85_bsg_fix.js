const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE='http://localhost:8080';
const EXE='/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async()=>{const b=await chromium.launch({executablePath:EXE,headless:false,args:['--no-sandbox','--disable-dev-shm-usage']});const p=await b.newPage();
const nav=(u)=>p.goto(BASE+u,{waitUntil:'networkidle',timeout:45000});const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
const openVariations=async function(stockId){
  await nav('/inventory/manage/items.php?stock_id='+stockId);await pause(1400);
  await p.locator('button[name="tabs_product_variations"]').first().click();await pause(3000);await p.waitForLoadState('networkidle');
};
await nav('/');await p.fill('input[name="user_name_entry_field"]','opencode');await p.fill('input[name="password"]','opencode');await p.click('input[type="submit"]');await p.waitForLoadState('networkidle');

// Test on BattlestarGalactica
await openVariations('BattlestarGalactica');

var log=[];
p.on('response',async function(r){if(r.url().includes('items.php')&&r.request().method()==='POST'){try{var t=await r.text();log.push({len:t.length,cmds:t.match(/"n":"[a-z]+"/g)||[],snip:t.replace(/\s+/g,' ').slice(0,500)});}catch(e){}}});

// Click generate_combos
try{await p.locator('button[name="generate_combos"]').click({timeout:5000});}catch(e){console.log('generate_combos click err:',e.message.slice(0,60));}
await pause(4000); await p.waitForLoadState('networkidle');
console.log('=== generate_combos ===');
log.forEach(function(r,i){console.log(i,'len='+r.len,'cmds='+JSON.stringify(r.cmds));console.log('  ',r.snip.slice(0,400));});
log.length=0;

// Wait for button to reappear after re-render
await pause(1000);
try{await p.locator('button[name="create_child_product"]').waitFor({timeout:8000});}catch(e){console.log('waiting for create_child_product...');}
await pause(1000);

// Click create_child_product
try{await p.locator('button[name="create_child_product"]').click({timeout:5000});}catch(e){console.log('create_child_product click err:',e.message.slice(0,60));}
await pause(4000); await p.waitForLoadState('networkidle');
console.log('=== create_child_product ===');
log.forEach(function(r,i){console.log(i,'len='+r.len,'cmds='+JSON.stringify(r.cmds));console.log('  ',r.snip.slice(0,400));});

// Check msgbox + notification
var msgbox=await p.evaluate(function(){var el=document.getElementById('msgbox'); return el?el.innerText.trim():'no-msgbox';});
console.log('=== msgbox ===',msgbox);

var bodyLines=await p.evaluate(function(){return document.body?document.body.innerText.split('\n').filter(function(l){return /saved|created|generated|error|combin|No combination|already up to date|Generate Child|invalid|not found/i.test(l);}).slice(0,10):[];});
console.log('=== body notif ===',JSON.stringify(bodyLines));

// Check Existing Variations table
var variations=await p.evaluate(function(){return Array.from(document.querySelectorAll('table')).flatMap(function(t){return Array.from(t.querySelectorAll('tr'));}).map(function(r){return Array.from(r.querySelectorAll('td')).map(function(c){return c.innerText.trim();});}).filter(function(c){return c.length>=2 && /BattlestarGalactica/.test(c[0]);}).map(function(r){return r[0];}).filter(Boolean);});
console.log('=== existing variations count:',variations.length,'===');
console.log('=== variations ===',JSON.stringify(variations.slice(0,10)));

await b.close();})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});