const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  async function postItemPage(stock_id, fields){
    await nav('/inventory/manage/items.php?stock_id='+stock_id); await page.waitForLoadState('networkidle');
    await page.evaluate((o)=>{
      const f=document.querySelector('form'); if(!f) throw new Error('no form');
      const set=(name,val)=>{ let el=f.querySelector('[name="'+name+'"]'); if(!el){ el=document.createElement('input'); el.type='hidden'; el.name=name; f.appendChild(el);} el.value=val; };
      set('_tabs_sel',o._tabs_sel||'settings');
      for(const k of Object.keys(o.fields||{})) set(k,o.fields[k]);
      f.submit();
    }, {stock_id, _tabs_sel:(fields._tabs||'product_variations'), fields});
    await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');
  }
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');

  const pk=Date.now().toString().slice(-5);
  const pid='E2E-V-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E V '+pk);
  await page.click('button[name="addupdate"]'); await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');

  for(const cid of ['2','1']){
    await postItemPage(pid, { _tabs:'product_variations', assign_category_submit:'Assign Category', assign_category_id:cid });
  }
  // generate_combos
  await postItemPage(pid, { _tabs:'product_variations', generate_combos:'Generate Combinations' });
  let b = await page.locator('body').innerText();
  console.log('=== AFTER GENERATE_COMBOS (body, trimmed) ===');
  console.log(b.split('\n').map(s=>s.trim()).filter(s=>s && /combinations|Combination|saved|values|categor|generate|variation|child/i.test(s)).slice(0,25).join(' | '));
  console.log('HAS CONFIRMATION ANY:', /saved|up to date|No values|No categor/i.test(b));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});