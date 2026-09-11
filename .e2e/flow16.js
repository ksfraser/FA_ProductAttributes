const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  async function loadAndSet(stock_id){ // GET edit page and set stock_id select to pid
    await nav('/inventory/manage/items.php?stock_id='+stock_id); await page.waitForLoadState('networkidle');
    await page.evaluate((pid)=>{
      const s=document.querySelector('select[name="stock_id"]');
      if(s){ for(const op of s.options){ if(op.value===pid){ op.selected=true; } } }
    }, stock_id);
  }
  async function submitFields(fields){
    await page.evaluate((o)=>{
      const f=document.querySelector('form'); if(!f) throw new Error('no form');
      const set=(n,v)=>{ let e=f.querySelector('[name="'+n+'"]'); if(!e){e=document.createElement('input');e.type='hidden';e.name=n;f.appendChild(e);}e.value=v; };
      set('_tabs_sel',o._tabs);
      for(const k of Object.keys(o.fields||{})) set(k,o.fields[k]);
      f.submit();
    }, {_tabs:'product_variations', fields});
    await page.waitForTimeout(3500); await page.waitForLoadState('networkidle');
  }
  async function varPanel(stock_id){
    await loadAndSet(stock_id);
    const before = await page.locator('body').innerText();
    if(!before.includes('Variations')) {
      await page.evaluate(()=>{ const tb=document.querySelector('button[name="tabs_product_variations"]'); if(tb) tb.click(); });
      await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
    }
    return page.locator('body').innerText();
  }

  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');

  const pk=Date.now().toString().slice(-5);
  const pid='E2E-FF-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E FF '+pk);
  await page.click('button[name="addupdate"]'); await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');

  for(const cid of ['2','1']){
    await loadAndSet(pid);
    await submitFields({ assign_category_submit:'Assign Category', assign_category_id:cid });
  }
  let bp = await varPanel(pid);
  console.log('after assign panel: Assigned Categories =', bp.includes('Assigned Categories'));

  await loadAndSet(pid);
  await submitFields({ generate_combos:'Generate Combinations' });
  await loadAndSet(pid);
  await submitFields({ generate_child:'Generate Child' });

  // verify persistence: panel + dropdown
  bp = await varPanel(pid);
  console.log('PANEL Existing Variations =', bp.includes('Existing Variations'));
  console.log('PANEL parent/child refs =', JSON.stringify([...new Set((bp.match(/E2E-FF-[^\s'"]+/g)||[]))]));

  await nav('/inventory/manage/items.php'); await page.waitForLoadState('networkidle');
  const opts = await page.evaluate(()=>{
    const sels=Array.from(document.querySelectorAll('select'));
    for(const s of sels){ const t=s.innerText||''; if(t.includes('E2E-FF-')) return t; } return null;
  });
  const dropKids = opts ? opts.split('\n').filter(o=>o.includes('E2E-FF-')) : [];
  console.log('DROPDOWN children count =', dropKids.length, JSON.stringify(dropKids.slice(0,10)));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});