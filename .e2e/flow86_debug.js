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

await openVariations('BattlestarGalactica');

var log=[];
p.on('response',async function(r){if(r.url().includes('items.php')&&r.request().method()==='POST'){try{var t=await r.text();log.push({len:t.length,body:t});}catch(e){}}});

// Click generate_combos
try{await p.locator('button[name="generate_combos"]').click({timeout:5000});}catch(e){console.log('gen err:',e.message.slice(0,60));}
await pause(5000); await p.waitForLoadState('networkidle');

console.log('=== generate_combos response count:',log.length,'===');
if(log.length>0){
  var r=log[0];
  console.log('len='+r.len);
  // extract the content data from the "up" commands
  var m=r.body.match(/"data":"((?:[^"\\]|\\.)*)"/g);
  if(m)m.forEach(function(d,i){console.log('  data['+i+'] len='+d.length+' preview:',d.slice(0,300));});
}
log.length=0;

// Check DOM state after re-render
var btns=await p.evaluate(function(){return Array.from(document.querySelectorAll('button[name="generate_combos"],button[name="create_child_product"]')).map(function(b){return {name:b.name,visible:b.offsetParent!==null,disabled:b.disabled};});});
console.log('=== buttons in DOM after generate_combos ===',JSON.stringify(btns));

var tabs=await p.evaluate(function(){return Array.from(document.querySelectorAll('button[name^="tabs_"]')).map(function(b){return b.getAttribute('name');});});
console.log('=== tab buttons in DOM ===',JSON.stringify(tabs));

// Now try create_child_product
var createBtn=p.locator('button[name="create_child_product"]');
var visible=await createBtn.isVisible().catch(function(){return false;});
console.log('=== create_child_product visible ===',visible);

if(visible){
  try{await createBtn.click({timeout:5000});}catch(e){console.log('create err:',e.message.slice(0,60));}
  await pause(4000);
  console.log('=== create_child_product response count:',log.length,'===');
  if(log.length>0){console.log('len='+log[0].len);console.log('  snip:',log[0].body.replace(/\s+/g,' ').slice(0,400));}
}

var msgbox=await p.evaluate(function(){var el=document.getElementById('msgbox'); return el?el.innerText.trim():'no-msgbox';});
console.log('=== msgbox ===',msgbox);
var bodyLines=await p.evaluate(function(){return document.body?document.body.innerText.split('\n').filter(function(l){return /saved|created|generated|error|combin|No combination|already up to date|Generate Child|invalid|not found/i.test(l);}).slice(0,10):[];});
console.log('=== body notif ===',JSON.stringify(bodyLines));

await b.close();})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});