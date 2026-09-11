const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  async function activateVar(stock_id){
    await nav('/inventory/manage/items.php?stock_id='+stock_id); await page.waitForLoadState('networkidle');
    await page.evaluate(()=>{ const tb=document.querySelector('button[name="tabs_product_variations"]'); if(tb) tb.click(); });
    await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
  }
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');
  const pk=Date.now().toString().slice(-5);
  const pid='E2E-UU-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E UU '+pk);
  await page.click('button[name="addupdate"]'); await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
  await activateVar(pid);

  const diag = await page.evaluate(()=>{
    const gcAnc=(el)=>{ const a=[]; let n=el; for(let i=0;i<7&&n;i++){ a.push(n.tagName+(n.id?('#'+n.id):'')+(n.className?('.'+String(n.className).split(' ')[0]):'')); n=n.parentElement;} return a; };
    const forms = Array.from(document.querySelectorAll('form'));
    const gc = document.querySelector('input[name="generate_combos"]');
    const as = document.querySelector('input[name="assign_category_submit"]');
    const gcForm = gc? gc.form : null;
    const asForm = as? as.form : null;
    return {
      numForms: forms.length,
      gcFormSameAsFirst: gcForm===forms[0],
      asFormSameAsFirst: asForm===forms[0],
      gcFormId: gcForm? (gcForm.id||gcForm.name) : null,
      asFormId: asForm? (asForm.id||asForm.name) : null,
      gcPath: gc? gcAnc(gc): null,
      asPath: as? gcAnc(as): null,
    };
  });
  console.log(JSON.stringify(diag,null,1));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});