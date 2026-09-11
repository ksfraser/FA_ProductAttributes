const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: false, args: ['--no-sandbox','--disable-dev-shm-usage'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
  let net=[];
  page.on('request', req=>{
    const isPost = req.method()==='POST';
    net.push((isPost?'POST':'GET ')+req.url());
  });
  page.on('console', m=>{ const t=m.text(); if(t.includes('CTX')||t.includes('SUBMIT')||t.includes('FORM')) console.log('CONSOLE', t); });
  // instrument click + submit with capture-phase logging directly in page
  await page.addInitScript(()=>{
    window.__ctx=[];
    document.addEventListener('click', (e)=>{
      const el=e.target;
      const md=el.closest? el.closest('input,button,a'):null;
      window.__ctx.push('CLICK capture target='+(md?md.name||md.value||md.tagName:'?')+' tag='+el.tagName);
    }, true);
    document.addEventListener('submit', (e)=>{
      window.__ctx.push('SUBMIT capture defaultPrevented='+e.defaultPrevented+' form='+(e.target.name||'?'));
    }, true);
    document.addEventListener('submit', (e)=>{
      window.__ctx.push('SUBMIT bubble defaultPrevented='+e.defaultPrevented);
    }, false);
    // watch for HTMLFormElement.prototype.submit overrides
    const orig=HTMLFormElement.prototype.submit;
    if(!window.__submitPatched){
      window.__submitPatched=true;
      HTMLFormElement.prototype.submit=function(){
        window.__ctx.push('FMT.SUBMIT() called on '+this.name);
        return orig.apply(this, arguments);
      };
    }
  });
  async function openVarTab(stock_id){
    await nav('/inventory/manage/items.php?stock_id='+stock_id); await page.waitForLoadState('networkidle');
    await pause(1200);
    await page.locator('button[name="tabs_product_variations"]').first().click();
    await pause(3500); await page.waitForLoadState('networkidle');
  }
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');
  const stock='auto-gas-L-11-36-Ind';
  await openVarTab(stock);
  // reset context
  await page.evaluate(()=>{ window.__ctx=[]; });
  const ctx=[];
  page.on('framenavigated', f=>{ if(f===page.mainFrame()){ ctx.push('NAV '+(new Date()).toISOString()); } });
  net=[];
  await page.locator('input[name="generate_combos"]').click();
  await pause(4500);
  const dump = await page.evaluate(()=>window.__ctx);
  console.log('CTX_EVENTS', JSON.stringify(dump,null,1));
  console.log('NET', JSON.stringify(net,null,1));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});
