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
  const pid='E2E-YY-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E YY '+pk);
  await page.click('button[name="addupdate"]'); await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
  await activateVar(pid);

  const ev=[]; const reset=()=>ev.length=0; const dump=(lbl)=>{ console.log(lbl, '=>', ev.length, 'events'); ev.slice(0,6).forEach(e=>console.log('   ',e)); };
  page.on('request', r=>{ if(!/\.(js|css|png|jpg|gif|ico)$/.test(r.url())) ev.push('REQ '+r.method()+' '+r.url().replace(BASE,'')); });
  page.on('response', r=>{ if(!/\.(js|css|png|jpg|gif|ico)$/.test(r.url())) ev.push('RESP '+r.status()+' '+r.url().replace(BASE,'')); });

  reset();
  await page.locator('select[name="assign_category_id"]').selectOption({value:'2'});
  await page.click('input[name="assign_category_submit"]');
  await page.waitForTimeout(3500);
  dump('ASSIGN CLICK');

  await activateVar(pid);
  reset();
  await page.click('input[name="generate_child"]');
  await page.waitForTimeout(3500);
  dump('GENERATE_CHILD CLICK');

  await activateVar(pid);
  reset();
  await page.click('input[name="generate_combos"]');
  await page.waitForTimeout(3500);
  dump('GENERATE_COMBOS CLICK');
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});