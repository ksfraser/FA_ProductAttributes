const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  const responses=[];
  page.on('response', r=>{ if(r.url().includes('items.php')){ responses.push(r); } });
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');
  const pk=Date.now().toString().slice(-5);
  const pid='E2E-DB-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E DB '+pk);
  await page.click('button[name="addupdate"]'); await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');

  // Inspect form fields on the edit page
  const fields = await page.evaluate(()=>{
    const f=document.querySelector('form'); if(!f) return 'NO FORM';
    const out=[];
    for(const el of f.elements){ if(el.name) out.push({name:el.name, tag:el.tagName.toLowerCase(), type:el.type||'', val:(el.tagName.toLowerCase()==='select'? el.value : el.value)}); }
    return out;
  });
  console.log('FORM FIELDS (first 40):');
  console.log(JSON.stringify(fields.slice(0,40), null, 1));
  // which select is 'stock_id'?
  const stk = await page.evaluate(()=>{
    const f=document.querySelector('form');
    const s=f.querySelector('[name="stock_id"]');
    return s? {tag:s.tagName, selected:s.value, opts:(s.tagName.toLowerCase()==='select'? s.selectedIndex : null)} : 'no field named stock_id';
  });
  console.log('stock_id field state:', JSON.stringify(stk));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});