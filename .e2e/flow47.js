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
  const detail = await page.evaluate(()=>{
    const btn=document.querySelector('input[name="generate_combos"]');
    const form=btn?btn.form:null;
    return {
      buttonOuter: btn? btn.outerHTML : null,
      buttonDisabled: btn? btn.disabled : null,
      formAction: form? form.getAttribute('action') : null,
      formMethod: form? form.getAttribute('method') : null,
      formId: form? form.id : null,
      formClass: form? form.className : null,
      formOuterStart: form? form.outerHTML.slice(0,400) : null,
      formHasOnerrorOnsubmit: form? (form.getAttribute('onsubmit')) : null,
      formsTotal: document.querySelectorAll('form').length,
      // is there any <base> or form action link weirdness
      isChildShown: !!document.querySelector('input[name="create_child"]'),
    };
  });
  console.log(JSON.stringify(detail,null,1));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});
