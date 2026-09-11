// Deterministic full-POST driver: assign categories + generate via items.php form submission.
const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  async function postItemPage(opts){
    // load edit page then submit form with given field overrides
    await nav('/inventory/manage/items.php?stock_id='+opts.stock_id); await page.waitForLoadState('networkidle');
    await page.evaluate((o)=>{
      const f=document.querySelector('form'); if(!f) throw new Error('no form');
      const set=(name,val)=>{ let el=f.querySelector('[name="'+name+'"]'); if(!el){ el=document.createElement('input'); el.type='hidden'; el.name=name; f.appendChild(el);} el.value=val; };
      set('_tabs_sel', o._tabs_sel||'settings');
      for(const k of Object.keys(o.fields||{})) set(k, o.fields[k]);
      f.submit();
    }, opts);
    await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
  }
  const allText=()=>page.locator('body').innerText();

  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');

  const pk=Date.now().toString().slice(-5);
  const pid='E2E-U-'+pk;
  // create item
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E U '+pk);
  await page.click('button[name="addupdate"]'); await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');

  // assign categories via full post (set _tabs_sel=product_variations + assign_category_submit + assign_category_id)
  for(const cid of ['2','1']){
    await postItemPage({ stock_id: pid, _tabs_sel:'product_variations', fields:{ assign_category_submit:'Assign Category', assign_category_id:cid } });
  }
  let b = await allText();
  console.log('after assigns: has Assigned Categories =', b.includes('Assigned Categories'));
  console.log('after assigns: has Color row =', />?Color/.test(b)|| b.includes('Color'));
  console.log('after assigns: "No categories assigned" =', b.includes('No categories assigned'));

  // generate_combos full post
  await postItemPage({ stock_id: pid, _tabs_sel:'product_variations', fields:{ generate_combos:'Generate Combinations' } });
  b = await allText();
  for(const k of ['No categories assigned','No values found','Combination set saved','already up to date','No valid combinations']){
    if(b.includes(k)) console.log('>> GENERATE_COMBOS =>', k);
  }

  // generate_child full post
  await postItemPage({ stock_id: pid, _tabs_sel:'product_variations', fields:{ generate_child:'Generate Child' } });
  b = await allText();
  for(const k of ['Generate Child complete','No combination set','created']){
    if(b.includes(k)) console.log('>> GENERATE_CHILD =>', k);
  }
  console.log('has Existing Variations =', b.includes('Existing Variations'));
  console.log('gen child msg:', (b.match(/Generate Child complete[^.]*/)||['n/a'])[0]);
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});