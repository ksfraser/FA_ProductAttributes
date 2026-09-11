// Decisive E2E: full-POST actions, then verify persistent side effects via tab click + dropdown.
const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  async function postItem(stock_id, fields){
    await nav('/inventory/manage/items.php?stock_id='+stock_id); await page.waitForLoadState('networkidle');
    await page.evaluate((o)=>{
      const f=document.querySelector('form'); if(!f) throw new Error('no form');
      const set=(name,val)=>{ let el=f.querySelector('[name="'+name+'"]'); if(!el){ el=document.createElement('input'); el.type='hidden'; el.name=name; f.appendChild(el);} el.value=val; };
      set('_tabs_sel',o._tabs||'product_variations');
      for(const k of Object.keys(o.fields||{})) set(k,o.fields[k]);
      f.submit();
    }, {stock_id, _tabs:fields._tabs||'product_variations', fields});
    await page.waitForTimeout(3000); await page.waitForLoadState('networkidle');
  }
  async function varTabOnce(stock_id){
    // navigate and click the Variations tab for a fresh panel read
    await nav('/inventory/manage/items.php?stock_id='+stock_id); await page.waitForLoadState('networkidle');
    await page.evaluate(()=>{ const b=document.querySelector('button[name="tabs_product_variations"]'); if(b) b.click(); });
    await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
    return page.locator('body').innerText();
  }

  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');

  const pk=Date.now().toString().slice(-5);
  const pid='E2E-Y-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E Y '+pk);
  await page.click('button[name="addupdate"]'); await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');

  for(const cid of ['2','1']){
    await postItem(pid, { assign_category_submit:'Assign Category', assign_category_id:cid });
  }
  let b = await varTabOnce(pid);
  console.log('after assign (panel): Assigned Categories =', b.includes('Assigned Categories'));

  // generate_combos
  await postItem(pid, { generate_combos:'Generate Combinations' });
  b = await varTabOnce(pid);
  const gcm = (b.match(/(Combination set[^\n]*|No categories[^\n]*|No values[^\n]*|Invalid stock[^\n]*|Cannot generate[^\n]*)/g)||[]).slice(0,3);
  console.log('combos result lines:', JSON.stringify(gcm));

  // generate_child
  await postItem(pid, { generate_child:'Generate Child' });
  b = await varTabOnce(pid);
  const gch = (b.match(/(Generate Child[^\n]*|No combination[^\n]*|created[^\n]*|instantiated[^\n]*)/gi)||[]).slice(0,5);
  console.log('child result lines:', JSON.stringify(gch));
  console.log('Existing Variations present =', b.includes('Existing Variations'));
  const kids = (b.match(/E2E-Y-[^\s']+/g)||[]).slice(0,10);
  console.log('E2E-Y refs in panel:', JSON.stringify([...new Set(kids)]));

  // item select dropdown on plain index
  await nav('/inventory/manage/items.php'); await page.waitForLoadState('networkidle');
  const opts = await page.evaluate(()=>{
    const sels=Array.from(document.querySelectorAll('select'));
    for(const s of sels){ const t=s.innerText||''; if(t.includes('E2E-Y-')) return t; } return null;
  });
  console.log('dropdown children:', opts ? JSON.stringify(opts.split('\n').filter(o=>o.includes('E2E-Y-')).slice(0,12)) : 'NONE');
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});