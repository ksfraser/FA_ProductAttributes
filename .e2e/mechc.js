const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  const nav = (p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');

  const pk = Date.now().toString().slice(-6);
  const pid = 'MECHC-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  console.log('addupdate button count:', await page.locator('button[name="addupdate"]').count());
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]', 'MechC '+pk);
  await page.click('button[name="addupdate"]');
  await page.waitForTimeout(2500);
  await page.waitForLoadState('networkidle');
  console.log('after ajaxsubmit URL:', page.url());
  // Did it create? try edit page via stock select
  await nav('/inventory/manage/items.php?stock_id='+pid); await page.waitForLoadState('networkidle');
  const body = await page.locator('body').innerText();
  console.log('edit page has stock:', body.includes(pid));
  const stockField = await page.evaluate(()=>{
    const f=document.querySelector('form'); if(!f) return 'NOFORM';
    const el=f.querySelector('input[name="stock_id"]')||f.querySelector('select[name="stock_id"]');
    return el?el.value:'NOSTOCKFIELD';
  });
  console.log('stock_id field value:', stockField);
  console.log('BODY:', body.slice(0,200).replace(/\n/g,' | '));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});