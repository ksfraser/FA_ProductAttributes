const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: false, args: ['--no-sandbox','--disable-dev-shm-usage'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  const pause=(ms)=>new Promise(r=>setTimeout(r,ms));
  let postTexts = [];
  page.on('response', async (resp)=>{
    if(resp.request().method()==='POST' && resp.url().includes('items.php')){
      let t=''; try{ t= await resp.text(); }catch(e){ t='<no-text>'; }
      const m = t.match(/VAR_TAB_POST[^>]*/);
      postTexts.push({status:resp.status(), marker: m?m[0]:'(no VAR_TAB_POST)', hasInstr: t.includes('VAR_TAB_INSTRUMENT')});
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
  // confirm my edit is served
  const instr = await page.evaluate(()=>({
    v2: document.documentElement.outerHTML.includes('VAR_TAB_INSTRUMENT v2'),
    post: (document.documentElement.outerHTML.match(/VAR_TAB_POST[^>]*/)||[])[0]||'(none on parse)',
  }));
  console.log('SERVED', JSON.stringify(instr));
  // capture the CURRENT VARIABLES TAB innerHTML as baseline by grabbing the container
  const tabHtmlBefore = await page.evaluate(()=>{
    const tabBtn=document.querySelector('button[name="tabs_product_variations"]');
    const tabId=tabBtn?tabBtn.getAttribute('id')||tabBtn.getAttribute('data-tab')||'' : '';
    // FA uses tabbed_content with <div id="tab_product_variations"> typically
    const div=document.getElementById('tab_product_variations')||document.querySelector('.tabpage');
    return div?div.innerHTML:'NO_DIV';
  });
  console.log('TAB_BEFORE_LEN', tabHtmlBefore.length);
  await page.locator('input[name="generate_combos"]').click();
  await pause(4500); await page.waitForLoadState('networkidle');
  const tabHtmlAfter = await page.evaluate(()=>{
    const div=document.getElementById('tab_product_variations')||document.querySelector('.tabpage');
    return div?div.innerHTML:'NO_DIV';
  });
  console.log('TAB_AFTER_LEN', tabHtmlAfter.length, 'IDENTICAL', tabHtmlAfter===tabHtmlBefore);
  console.log('POST_TEXTS', JSON.stringify(postTexts,null,1));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});
