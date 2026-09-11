const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  const strip=(h)=>h.replace(/<[^>]*>/g,' ').replace(/\s+/g,' ');
  // full-form POST with EXPLICIT _tabs_sel; returns response page text
  async function submit(pid, fields, _tabs='product_variations'){
    await nav('/inventory/manage/items.php?stock_id='+pid); await page.waitForLoadState('networkidle');
    await page.evaluate((o)=>{
      const f=document.querySelector('form');
      const set=(n,v)=>{ let e=f.querySelector('[name="'+n+'"]'); if(!e){e=document.createElement('input');e.type='hidden';e.name=n;f.appendChild(e);}e.value=v; };
      const s=f.querySelector('select[name="stock_id"]'); if(s){ for(const op of s.options){ if(op.value===o.pid){ op.selected=true; } } }
      set('_tabs_sel',o._tabs);
      for(const k of Object.keys(o.fields||{})) set(k,o.fields[k]);
      f.submit();
    }, {pid, _tabs, fields});
    await page.waitForTimeout(3500); await page.waitForLoadState('networkidle');
    return strip(await page.content());
  }
  const show=(label,t,re)=>{ const m=(t.match(re)||[]); console.log(label, JSON.stringify(m.slice(0,4))); };

  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');
  const pk=Date.now().toString().slice(-5);
  const pid='E2E-OO-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E OO '+pk);
  await page.click('button[name="addupdate"]'); await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
  // assign via real UI (persists)
  for(const value of [2,1]){
    await nav('/inventory/manage/items.php?stock_id='+pid); await page.waitForLoadState('networkidle');
    await page.evaluate(()=>{ const tb=document.querySelector('button[name="tabs_product_variations"]'); if(tb) tb.click(); });
    await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
    await page.locator('select[name="assign_category_id"]').selectOption({value:String(value)});
    await page.click('input[name="assign_category_submit"],button[name="assign_category_submit"]');
    await page.waitForTimeout(3000); await page.waitForLoadState('networkidle');
  }
  // generate_combos via full POST with explicit tab
  let t = await submit(pid, { generate_combos:'Generate Combinations' });
  show('GC:', t, /(Combination set[^.]{0,90}|No categories[^.]{0,50}|No values[^.]{0,50}|Invalid stock[^.]{0,40})/g);
  // generate_child via full POST
  t = await submit(pid, { generate_child:'Generate Child' });
  show('GCh:', t, /(Generate Child[^.]{0,90}|No combination[^.]{0,50}|instantiated[^.]{0,80})/gi);
  // children in dropdown
  await nav('/inventory/manage/items.php'); await page.waitForLoadState('networkidle');
  const opts = await page.evaluate(()=>{
    const sels=Array.from(document.querySelectorAll('select'));
    for(const s of sels){ const tt=s.innerText||''; if(tt.includes('E2E-OO-')) return tt; } return null;
  });
  const kids = opts? opts.split('\n').map(x=>x.trim()).filter(x=>x.includes('E2E-OO-')) : [];
  console.log('DROPDOWN children =', kids.length, JSON.stringify(kids.slice(0,10)));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});