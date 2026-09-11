const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE='http://localhost:8080';
const EXE='/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async()=>{const b=await chromium.launch({executablePath:EXE,headless:false,args:['--no-sandbox','--disable-dev-shm-usage']});const p=await b.newPage();
const nav=(u)=>p.goto(BASE+u,{waitUntil:'networkidle',timeout:45000});const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
await nav('/');await p.fill('input[name="user_name_entry_field"]','opencode');await p.fill('input[name="password"]','opencode');await p.click('input[type="submit"]');await p.waitForLoadState('networkidle');
await nav('/inventory/manage/items.php?stock_id=BattlestarGalactica');await pause(1400);
await p.locator('button[name="tabs_product_variations"]').first().click();await pause(3200);await p.waitForLoadState('networkidle');

async function fire(name){
  // capture the FIRST items.php POST+response carrying this key
  let rw='<noresp>';
  const rp = p.waitForResponse(r=>r.request().method()==='POST' && r.url().includes('items.php'),{timeout:25000}).then(async r=>{try{rw=await r.text();}catch(e){rw='<rf>';}}).catch(()=>{rw='<noresp>';});
  try{ await p.locator('button[name="'+name+'"]').first().click(); }catch(e){ console.log(name,'no such button'); return; }
  await rp; await pause(2800);
  console.log('### '+name+' ###');
  console.log('  text-field:', rw.includes('"text":')? rw.split('"text":')[1].slice(0,120): rw.slice(0,200));
  console.log('  has-notification-error:', /\berror\b|Exception|Fatal|Catch|Stack/i.test(rw));
  let notif=await p.evaluate(()=>{const m=document.body?document.body.innerText.match(/[^\n]*(Category[^\n]{0,40}|combin[^\n]{0,40}|saved[^\n]{0,40}|created[^\n]{0,40}|value[^\n]{0,40})[^\n]*/i):null;return m?m[0].slice(0,140):null;});
  console.log('  body-notif-like:',notif);
}
await fire('unassign_category_submit');
await fire('generate_combos');
await b.close();})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});