// Correct E2E: set stock_id select value, run assign + generate_combos + generate_child,
// verify persistence + read-only child tab.
const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  const strip=(h)=>h.replace(/<[^>]*>/g,' ').replace(/\s+/g,' ');

  // navigate to edit page, set stock_id select + _tabs_sel + fields, submit; capture the
  // POST response body so we can read display_notification on the action's own response.
  let lastPostText = '';
  async function act(stock_id, fields, _tabs='product_variations'){
    lastPostText='';
    page.on('response', r=>{ if(r.request().method()==='POST' && r.url().includes('items.php')){ r.text().then(t=>{lastPostText=t;}).catch(()=>{}); } });
    await nav('/inventory/manage/items.php?stock_id='+stock_id); await page.waitForLoadState('networkidle');
    await page.evaluate((o)=>{
      const f=document.querySelector('form'); if(!f) throw new Error('no form');
      const set=(n,v)=>{ let e=f.querySelector('[name="'+n+'"]'); if(!e){e=document.createElement('input');e.type='hidden';e.name=n;f.appendChild(e);}e.value=v; };
      const s=f.querySelector('select[name="stock_id"]'); if(s){ for(const op of s.options){ if(op.value===o.pid){ op.selected=true; } } }
      set('_tabs_sel',o._tabs);
      for(const k of Object.keys(o.fields||{})) set(k,o.fields[k]);
      f.submit();
    }, {pid:stock_id, _tabs, fields});
    await page.waitForTimeout(3500); await page.waitForLoadState('networkidle');
    return lastPostText || (await page.locator('body').innerText());
  }
  function msg(html){ const t=html.replace(/<[^>]*>/g,' ').replace(/\s+/g,' '); 
    const m=t.match(/(Combination set[^.]{0,80}|No categories[^.]{0,60}|No values[^.]{0,60}|Invalid stock[^.]{0,40}|Cannot generate[^.]{0,40}|No combination[^.]{0,60}|Generate Child[^.]{0,60}|instantiated[^.]{0,60})/g);
    return m? m.slice(0,4) : []; }

  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');

  const pk=Date.now().toString().slice(-5);
  const pid='E2E-E2-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E E2 '+pk);
  await page.click('button[name="addupdate"]'); await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');

  // assign Color(2)+Size(1)
  for(const cid of ['2','1']){
    await act(pid, { assign_category_submit:'Assign Category', assign_category_id:cid });
  }
  // generate_combos
  let b = await act(pid, { generate_combos:'Generate Combinations' });
  console.log('GENERATE_COMBOS message:', JSON.stringify(msg(b)));

  // generate_child
  b = await act(pid, { generate_child:'Generate Child' });
  console.log('GENERATE_CHILD message:', JSON.stringify(msg(b)));

  // verify: navigate fresh, click Variations tab, read panel
  await nav('/inventory/manage/items.php?stock_id='+pid); await page.waitForLoadState('networkidle');
  await page.evaluate(()=>{ const tb=document.querySelector('button[name="tabs_product_variations"]'); if(tb) tb.click(); });
  await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
  b = await page.locator('body').innerText();
  console.log('PANEL Existing Variations =', b.includes('Existing Variations'));
  const kids=[...new Set((b.match(/E2E-E2-[^\s'"]+/g)||[]))];
  console.log('PANEL item refs:', JSON.stringify(kids));
  console.log('PANEL has Generate Child button =', b.includes('Generate Child'));

  // read-only child tab: pick first child, check no generate buttons + parent shown
  if(kids.length){
    const child = kids[0];
    console.log('Checking child:', child);
    await nav('/inventory/manage/items.php?stock_id='+child); await page.waitForLoadState('networkidle');
    await page.evaluate(()=>{ const tb=document.querySelector('button[name="tabs_product_variations"]'); if(tb) tb.click(); });
    await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
    const cb = await page.locator('body').innerText();
    console.log('  child is variation of =', (cb.match(/variation of\s+\S+/i)||['n/a'])[0]);
    console.log('  child has Generate Combinations btn =', cb.includes('Generate Combinations'));
    console.log('  child has Generate Child btn =', cb.includes('Generate Child'));
    console.log('  child has Create Child btn =', cb.includes('Create Child Product'));
    console.log('  child has assign dropdown =', cb.includes('assign_category_id')||cb.includes('Assign Category'));
  } else {
    console.log('NO CHILDREN CREATED');
  }
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});