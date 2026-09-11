const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const b=await chromium.launch({executablePath:EXE,headless:false,args:['--no-sandbox','--disable-dev-shm-usage']});const p=await b.newPage();
  const nav=(u)=>p.goto(BASE+u,{waitUntil:'networkidle',timeout:45000});const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
  await nav('/');await p.fill('input[name="user_name_entry_field"]','opencode');await p.fill('input[name="password"]','opencode');await p.click('input[type="submit"]');await p.waitForLoadState('networkidle');
  const opts=await p.evaluate(async()=>{
    // Find a select with stock items; pick one whose text mentions Battlestar
    const out=[];
    for(const s of document.querySelectorAll('select')){
      const arr=Array.from(s.options);
      const hit=arr.filter(o=>/battle.*galactic/i.test(o.textContent||'').trim());
      if(hit.length){out.push({sel:s.name,opts:hit.slice(0,10).map(o=>({v:o.value,t:(o.textContent||'').trim()}))});}
    }
    return out;
  });
  await nav('/inventory/manage/items.php');await pause(1500);
  const opts2=await p.evaluate(()=>Array.from(document.querySelectorAll('select')).map(s=>({name:s.name,n:s.options.length})).filter(s=>s.n>1));
  console.log('selects:',JSON.stringify(opts2));
  const body=await p.evaluate(()=>document.body?document.body.innerText:'');
  const m=body.match(/[a-z0-9_-]*-?battle[a-z0-9_-]*/i);
  console.log('battlestar-match-in-body:',m?m[0]:null);
  console.log('body contains Battlestar:',/battlestar/i.test(body));
  await b.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});