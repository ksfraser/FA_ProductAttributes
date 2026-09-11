const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE='http://localhost:8080';
const EXE='/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async()=>{const b=await chromium.launch({executablePath:EXE,headless:false,args:['--no-sandbox','--disable-dev-shm-usage']});const p=await b.newPage();
const nav=(u)=>p.goto(BASE+u,{waitUntil:'networkidle',timeout:45000});const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
await nav('/');await p.fill('input[name="user_name_entry_field"]','opencode');await p.fill('input[name="password"]','opencode');await p.click('input[type="submit"]');await p.waitForLoadState('networkidle');

// FULL page reload on BattlestarGalactica with _tabs_sel=product_variations in POST
await nav('/inventory/manage/items.php?stock_id=BattlestarGalactica');await pause(1400);

// Simulate a full page POST with _tabs_sel=product_variations to see if the hook renders on full POST
var resp=await p.evaluate(async function(){
  var fd=new FormData();
  fd.append('stock_id','BattlestarGalactica');
  fd.append('_tabs_sel','product_variations');
  fd.append('_token',document.querySelector('input[name="_token"]').value);
  var r=await fetch('/inventory/manage/items.php',{method:'POST',body:fd});
  return await r.text();
});

// Check if the response has the variations content
var hasVariations=/Assigned Categories|generate_combos|Existing Variations|Manage Values/.test(resp);
var hasContentBox=/id='_tabs_div'/.test(resp);
console.log('hasContentBox:',hasContentBox,'hasVariations:',hasVariations);
// Extract the _tabs_div content
var m=resp.match(/id='_tabs_div'[^>]*>([\s\S]*?)<\/div>/);
if(m){console.log('=== _tabs_div content (first 1500) ===');console.log(m[1].slice(0,1500));}
else{console.log('=== NO _tabs_div match, searching for Assigned ===');var i=resp.indexOf('Assigned');console.log(i>=0?resp.slice(Math.max(0,i-100),i+500):'NOT FOUND');}

await b.close();})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});