const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: false, args: ['--no-sandbox','--disable-dev-shm-usage'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
  async function openVarTab(stock_id){
    await nav('/inventory/manage/items.php?stock_id='+stock_id); await page.waitForLoadState('networkidle');
    await pause(1000);
    await page.locator('button[name="tabs_product_variations"]').first().click();
    await pause(3500); await page.waitForLoadState('networkidle');
  }
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');

  const stock='auto-gas';
  await openVarTab(stock);

  const rendered = await page.evaluate(()=>{
    const pick=(sel,a)=>document.querySelector(sel)?document.querySelector(sel).getAttribute(a):null;
    const count=(sel)=>document.querySelectorAll(sel).length;
    const names=Array.from(document.querySelectorAll('button')).map(b=>b.getAttribute('name')).filter(n=>n&&/generate|create_child/.test(n));
    return {
      ajaxsubmitButtons: names,
      eachHasAjaxsubmit: names.every(n=>{const b=document.querySelector('button[name="'+n+'"]');return b&&b.classList.contains('ajaxsubmit');}),
      hasOnclickWorkaround: (()=>{const b=document.querySelector('button[name="generate_combos"]');return b&&b.hasAttribute('onclick');})(),
      formsTotal: count('form'),
      nestedFormsInPanel: document.getElementById('_tabs_div')?document.getElementById('_tabs_div').querySelectorAll('form').length:null,
      generateCombosInSubmittable: !!document.querySelector('button[name="generate_combos"]'),
    };
  });
  console.log('RENDERED:', JSON.stringify(rendered,null,1));

  // Click Generate Combinations via ajaxsubmit (JsHttpRequest). Capture network.
  let fired=[]; let sawNotify=false;
  page.on('response', async r=>{ const u=r.url(); if(u.includes('/inventory/manage/items.php')) fired.push({status:r.status(),url:u}); });
  let body='';
  page.on('request', req=>{ if(req.method()==='POST' && req.url().includes('items.php')){ body=req.postData()||''; } });

  if(rendered.generateCombosInSubmittable){
    await Promise.all([
      page.waitForResponse(r=>r.url().includes('/inventory/manage/items.php'),{timeout:20000}).catch(()=>{}),
      page.locator('button[name="generate_combos"]').click(),
    ]);
    await pause(3000);
    // look for FA notification content on the page
    const text = await page.evaluate(()=>document.body ? document.body.innerText : '');
    sawNotify = /Generat/i.test(text) || /no/i.test(text) && /assignment|variation|combo/i.test(text);
    console.log('POST-ACTION: fired='+fired.length+' sawNotify='+sawNotify);
    const hasCombosBtn = /name="generate_combos"/.test(body);
    const hasTabSel = /product_variations/.test(body);
    console.log('postCarriesGenerateCombosBtn:', hasCombosBtn, ' postCarriesTabSel:', hasTabSel);
    console.log('postData snippet:', JSON.stringify(body.replace(/\r/g,'').split('\n').filter(l=>/name="|product_variations|generate_combos/ .test(l)).slice(0,25)));
  } else {
    console.log('generate_combos button not found');
  }

  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});