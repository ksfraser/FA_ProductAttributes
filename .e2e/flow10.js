// Full E2E via deterministic full form POSTs.
// Verifies DB effects through browser-observable UI (item list dropdown / variations panel children list).
const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});

  // full-form POST to items page with overrides
  async function postItem(stock_id, fields){
    await nav('/inventory/manage/items.php?stock_id='+stock_id); await page.waitForLoadState('networkidle');
    await page.evaluate((o)=>{
      const f=document.querySelector('form'); if(!f) throw new Error('no form');
      const set=(name,val)=>{ let el=f.querySelector('[name="'+name+'"]'); if(!el){ el=document.createElement('input'); el.type='hidden'; el.name=name; f.appendChild(el);} el.value=val; };
      set('_tabs_sel',o._tabs||'settings');
      for(const k of Object.keys(o.fields||{})) set(k,o.fields[k]);
      f.submit();
    }, {stock_id, _tabs:fields._tabs||'product_variations', fields});
    await page.waitForTimeout(3000); await page.waitForLoadState('networkidle');
  }

  // grab the item list select (sidebar) — lists all items incl children
  async function itemListText(){
    const sel = page.locator('select#stock_id, select[name="stock_id"], #sidebar_workingtal select, form select').last();
    return sel.count().then(async c=> c? await sel.innerText() || '' : '');
  }

  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');

  const pk=Date.now().toString().slice(-5);
  const pid='E2E-X-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E X '+pk);
  await page.click('button[name="addupdate"]'); await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');

  // assign Color(2)+Size(1)
  for(const cid of ['2','1']){
    await postItem(pid, { assign_category_submit:'Assign Category', assign_category_id:cid, _tabs:'product_variations' });
  }
  let b = await page.locator('body').innerText();
  console.log('after assign: Assigned Categories =', b.includes('Assigned Categories'));

  // generate_combos (full post)
  await postItem(pid, { generate_combos:'Generate Combinations', _tabs:'product_variations' });

  // generate_child (full post)
  await postItem(pid, { generate_child:'Generate Child', _tabs:'product_variations' });
  b = await page.locator('body').innerText();
  console.log('after generate_child: has Existing Variations =', b.includes('Existing Variations'));
  // child stock ids in panel snippet (prefix E2E-X-)
  const snippet = (b.match(new RegExp(pid.replace(/[-\\\/]/g,'\\$&')+'[^\\n]*','g'))||[]).slice(0,8);
  console.log('panel mentions of parent/children:', JSON.stringify(snippet));

  // go to plain item index and fetch the item-list dropdown to look for generated children
  await nav('/inventory/manage/items.php?stock_id='+pid); await page.waitForLoadState('networkidle');
  // search the item select options
  const selOpts = await page.evaluate(()=>{
    const sels = Array.from(document.querySelectorAll('select'));
    for(const s of sels){ const txt=s.innerText||''; if(txt.includes('E2E-X-')) return {name:s.name, id:s.id, opts:s.innerText}; }
    return null;
  });
  console.log('item dropdown with E2E-X:', selOpts ? JSON.stringify(selOpts.opts.split('\n').filter(o=>o.includes('E2E-X-')).slice(0,10)) : 'NOT FOUND');
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});