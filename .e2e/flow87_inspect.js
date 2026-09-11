const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE='http://localhost:8080';
const EXE='/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async()=>{const b=await chromium.launch({executablePath:EXE,headless:false,args:['--no-sandbox','--disable-dev-shm-usage']});const p=await b.newPage();
const nav=(u)=>p.goto(BASE+u,{waitUntil:'networkidle',timeout:45000});const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
await nav('/');await p.fill('input[name="user_name_entry_field"]','opencode');await p.fill('input[name="password"]','opencode');await p.click('input[type="submit"]');await p.waitForLoadState('networkidle');
await nav('/inventory/manage/items.php?stock_id=BattlestarGalactica');await pause(1400);
await p.locator('button[name="tabs_product_variations"]').first().click();await pause(3000);await p.waitForLoadState('networkidle');

var rawResp='';
p.on('response',async function(r){if(r.url().includes('items.php')&&r.request().method()==='POST'&&!rawResp){try{rawResp=await r.text();}catch(e){}}});

// Click generate_combos
await p.locator('button[name="generate_combos"]').click();
await pause(5000); await p.waitForLoadState('networkidle');

// Parse the response JSON and inspect each command
var parsed=rawResp.replace(/^[^({]*\(/,'').replace(/\)\s*;?\s*$/,'').replace(/^top && top\.JsHttpRequestGlobal && top\.JsHttpRequestGlobal\.dataReady\(/,'').replace(/\)\s*\/\/-->\s*<\/script>\s*$/,'');
// Simpler: extract JSON between dataReady( and )
var m=rawResp.match(/dataReady\((\{.*\})\)/);
if(m){try{var j=JSON.parse(m[1]);var cmds=j.js||{};Object.keys(cmds).forEach(function(k){var c=cmds[k];console.log('cmd['+k+'] n='+c.n+' t='+c.t+' why='+c.why+' data_len='+(c.data?c.data.length:0));if(c.n==='up'&&c.data){console.log('  HAS generate_combos:',/generate_combos/.test(c.data));console.log('  HAS _tabs_div:',/_tabs_div/.test(c.data));console.log('  HAS Existing Variations:',/Existing Variations/.test(c.data));console.log('  FIRST 200:',c.data.replace(/\s+/g,' ').slice(0,200));console.log('  LAST 200:',c.data.replace(/\s+/g,' ').slice(-200));}});}catch(e){console.log('JSON parse err:',e.message);}}
else{console.log('NO MATCH');console.log('raw:',rawResp.slice(0,500));}

await b.close();})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});