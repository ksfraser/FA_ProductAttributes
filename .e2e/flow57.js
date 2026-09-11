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
  const stock='auto-gas-L-11-36-Ind';
  await openVarTab(stock);
  const info = await page.evaluate(()=>{
    const forms=Array.from(document.querySelectorAll('form'));
    const gc=document.querySelector('input[name="generate_combos"]');
    const cc=document.querySelector('input[name="create_child"]');
    const as=document.querySelector('input[name="assign_category_submit"]');
    const ts=document.querySelector('input[name="_tabs_sel"]');
    const f=(el)=>el&&el.form? {action:el.form.getAttribute('action'),method:el.form.getAttribute('method'),target:el.form.getAttribute('target'),onsubmit:el.form.getAttribute('onsubmit'),idx:forms.indexOf(el.form)} : null;
    return {
      formsTotal: forms.length,
      genCombos: gc? f(gc):null,
      createChild: cc? f(cc):null,
      assign: as? f(as):null,
      tabs_selInGC: gc&&ts && gc.form===ts.form,
      tabPanelParentTag: gc? (gc.closest('#_tabs_div')?'#_tabs_div':gc.closest('form').tagName):null,
      // is there a <form> ancestor closer to the button than the page form?
      gcFormAncestors: gc? (function(){let a=[],d=gc.parentElement;while(d&&d!==document.body){if(d.tagName==='FORM')a.push('FORM');d=d.parentElement;}return a;})() : null,
    };
  });
  console.log(JSON.stringify(info,null,1));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});