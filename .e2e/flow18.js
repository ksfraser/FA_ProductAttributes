const { chromium } = require('/home/kevin/Documents/ksf_FA_Square/node_modules/playwright');
const BASE = 'http://localhost:8080';
const EXE = '/home/kevin/.cache/ms-playwright/chromium-1234/chrome-linux64/chrome';
(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true, args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'] });
  const page = await browser.newPage();
  const nav=(p)=>page.goto(BASE+p,{waitUntil:'networkidle',timeout:45000});
  const strip=(h)=>h.replace(/<[^>]*>/g,' ').replace(/\s+/g,' ');
  // submit the form and await the POST navigation; return the response page text
  async function submitFields(pid, fields){
    await page.evaluate((o)=>{
      const f=document.querySelector('form');
      const set=(n,v)=>{ let e=f.querySelector('[name="'+n+'"]'); if(!e){e=document.createElement('input');e.type='hidden';e.name=n;f.appendChild(e);}e.value=v; };
      const s=f.querySelector('select[name="stock_id"]'); for(const op of s.options){ if(op.value===o.pid){ op.selected=true; } }
      set('_tabs_sel','product_variations');
      for(const k of Object.keys(o.fields||{})) set(k,o.fields[k]);
      f.submit();
    }, {pid, fields});
    await page.waitForTimeout(3500); await page.waitForLoadState('networkidle');
    return strip(await page.content());
  }
  await nav('/');
  await page.fill('input[name="user_name_entry_field"]','opencode');
  await page.fill('input[name="password"]','opencode');
  await page.click('input[type="submit"]'); await page.waitForLoadState('networkidle');
  const pk=Date.now().toString().slice(-5);
  const pid='E2E-HH-'+pk;
  await nav('/inventory/manage/items.php?NewItem=1'); await page.waitForLoadState('networkidle');
  await page.fill('input[name="NewStockID"]', pid);
  await page.fill('input[name="description"]','E2E HH '+pk);
  await page.click('button[name="addupdate"]'); await page.waitForTimeout(2500); await page.waitForLoadState('networkidle');

  for(const cid of ['2','1']){
    await nav('/inventory/manage/items.php?stock_id='+pid); await page.waitForLoadState('networkidle');
    await submitFields(pid, { assign_category_submit:'x', assign_category_id:cid });
  }
  await nav('/inventory/manage/items.php?stock_id='+pid); await page.waitForLoadState('networkidle');
  let t = await submitFields(pid, { generate_combos:'Generate Combinations' });
  console.log('GC page says Assigned Categories =', t.includes('Assigned Categories'));
  console.log('GC body (last 700):');
  console.log(t.slice(-700));
  await browser.close();
})().catch(e=>{console.error('FATAL',e.message);process.exit(1);});