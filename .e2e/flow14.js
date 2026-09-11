const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  const navs=[];
  page.on('response', r=>{ if(r.url().includes('items.php')) navs.push(r); });
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');
  const pk=Date.now().toString().slice(-5);
  const pid='E2E-DC-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E DC '+pk);
  await page.click('button[name="addupdate"]'); await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');

  // set the stock_id select to pid and inspect
  const setResult = await page.evaluate((pid)=>{
    const s=document.querySelector('select[name="stock_id"]');
    if(!s) return 'no select';
    let found=false;
    for(const op of s.options){ if(op.value===pid){ op.selected=true; found=true; } }
    // also check if there's a text field variant
    return {found, tag:'select', selected:s.value};
  }, pid);
  console.log('setResult:', JSON.stringify(setResult));

  navs.length=0;
  await page.evaluate((o)=>{
    const f=document.querySelector('form');
    const set=(n,v)=>{ let e=f.querySelector('[name="'+n+'"]'); if(!e){e=document.createElement('input');e.type='hidden';e.name=n;f.appendChild(e);}e.value=v; };
    set('_tabs_sel','product_variations');
    set('generate_combos','Generate Combinations');
    f.submit();
  }, {});
  await page.waitForTimeout(3500);
  console.log('navs captured:', navs.length);
  for(const r of navs){
    const t = await r.text().catch(()=>'');
    const st = t.replace(/<[^>]*>/g,' ').replace(/\s+/g,' ');
    const hasGC = st.includes('Combination set') || st.includes('No categories') || st.includes('No values') || st.includes('Invalid stock') || st.includes('Cannot generate');
    console.log('method', r.request().method(), 'hasResult', hasGC, 'sample:', st.slice(0,200));
  }
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});