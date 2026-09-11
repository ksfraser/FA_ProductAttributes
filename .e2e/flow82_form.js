const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE='http://localhost:8080';
const EXE='/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async()=>{const b=await chromium.launch({executablePath:EXE,headless:false,args:['--no-sandbox','--disable-dev-shm-usage']});const p=await b.newPage();
const nav=(u)=>p.goto(BASE+u,{waitUntil:'networkidle',timeout:45000});const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
await nav('/');await p.fill('input[name="user_name_entry_field"]','opencode');await p.fill('input[name="password"]','opencode');await p.click('input[type="submit"]');await p.waitForLoadState('networkidle');
await nav('/inventory/manage/items.php?stock_id=auto-gas');await pause(1400);
await p.locator('button[name="tabs_product_variations"]').first().click();await pause(3000);await p.waitForLoadState('networkidle');

const els=await p.evaluate(()=>{
  var forms=Array.from(document.querySelectorAll('form'));
  var result={forms:forms.map(function(f){return {action:f.getAttribute('action'),name:f.getAttribute('name'),id:f.id,elements:f.elements.length};})};
  if(forms.length>0){
    var form=forms[0];
    result.formElements=Array.from(form.elements).map(function(e){return {name:e.name,type:e.type,value:(e.value||'').slice(0,50),tag:e.tagName};}).filter(function(e){return e.name;});
  }
  return result;
});
console.log(JSON.stringify(els,null,1));
await b.close();})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});