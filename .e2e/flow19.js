// RELIABLE E2E: click the real Variations tab (sets _tabs_sel correctly), then submit
// generate actions from within that active tab with stock_id select set.
const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  const strip=(h)=>h.replace(/<[^>]*>/g,' ').replace(/\s+/g,' ');
  const has=(t,k)=>t.includes(k);

  // Load edit page, click Variations tab to activate it, then return body text
  async function activateVariations(stock_id){
    await nav('/inventory/manage/items.php?stock_id='+stock_id); await page.waitForLoadState('networkidle');
    await page.evaluate(()=>{ const tb=document.querySelector('button[name="tabs_product_variations"]'); if(tb) tb.click(); });
    await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
    return page.locator('body').innerText();
  }
  // From the ACTIVE variations form, set stock_id + fields and submit; return response page text
  async function submitActive(fields){
    await page.evaluate((o)=>{
      const f=document.querySelector('form');
      const set=(n,v)=>{ let e=f.querySelector('[name="'+n+'"]'); if(!e){e=document.createElement('input');e.type='hidden';e.name=n;f.appendChild(e);}e.value=v; };
      const s=f.querySelector('select[name="stock_id"]'); if(s){ for(const op of s.options){ if(o.pid && op.value===o.pid){ op.selected=true; } } }
      for(const k of Object.keys(o.fields||{})) set(k,o.fields[k]);
      f.submit();
    }, {pid:globalPid, fields});
    await page.waitForTimeout(3500);
    await page.waitForLoadState('networkidle');
    return strip(await page.content());
  }

  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');

  const pk=Date.now().toString().slice(-5);
  const pid=globalPid='E2E-II-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E II '+pk);
  await page.click('button[name="addupdate"]'); await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');

  // assign categories
  for(const cid of ['2','1']){
    await activateVariations(pid);
    await submitActive({ assign_category_submit:'Assign Category', assign_category_id:cid });
  }
  let t = await activateVariations(pid);
  console.log('after assign: Assigned Categories =', has(t,'Assigned Categories'));

  // generate_combos
  await activateVariations(pid);
  t = await submitActive({ generate_combos:'Generate Combinations' });
  console.log('GC response has message =', has(t,'Combination set')||has(t,'No categories')||has(t,'No values')||has(t,'Invalid stock')||has(t,'Cannot generate'));
  console.log('GC tail:', t.slice(-500));

  // generate_child
  await activateVariations(pid);
  t = await submitActive({ generate_child:'Generate Child' });

  // verify children in dropdown
  await nav('/inventory/manage/items.php'); await page.waitForLoadState('networkidle');
  const opts = await page.evaluate(()=>{
    const sels=Array.from(document.querySelectorAll('select'));
    for(const s of sels){ const tt=s.innerText||''; if(tt.includes('E2E-II-')) return tt; } return null;
  });
  const kids = opts? opts.split('\n').filter(o=>o.includes('E2E-II-')) : [];
  console.log('DROPDOWN children =', kids.length, JSON.stringify(kids.slice(0,10)));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});