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
    const tabDiv=document.getElementById('_tabs_div');
    const gc=document.querySelector('input[name="generate_combos"]');
    const ts=document.querySelector('input[name="_tabs_sel"]');
    // is the tab panel a descendant of the FORM?
    const isDescendant=(el,anc)=>{let d=el.parentElement;while(d){if(d===anc)return true;d=d.parentElement;}return false;};
    let form=null; let d=tabDiv; while(d){ if(d.tagName==='FORM'){form=d;break;} d=d.parentElement; }
    return {
      tabPanelInsideForm: form? true:false,
      formAction: form? form.getAttribute('action'):null,
      tabPanelParentTag: tabDiv? tabDiv.closest('#_tabs_div').parentElement.tagName:null,
      // what wraps the tab bar (ul.ajaxtabs) - sibling structure inside form?
      gcInSameFormAsTabsSel: gc&&ts? (gc.form===ts.form):null,
      // count how many <form> open tags are inside _tabs_div (would be nested)
      nestedFormsInPanel: tabDiv? tabDiv.querySelectorAll('form').length:null,
    };
  });
  console.log(JSON.stringify(info,null,1));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});